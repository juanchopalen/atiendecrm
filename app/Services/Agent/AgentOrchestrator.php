<?php

namespace App\Services\Agent;

use App\Exceptions\GeminiApiException;
use App\Models\AgentAuditLog;
use App\Models\Client;
use App\Notifications\AgentEscalationRequired;
use App\Services\Agent\Tools\GetEstadoPagoTool;
use App\Services\Agent\Tools\GetPolizaPorClienteTool;
use App\Services\Agent\Tools\VerificarClienteTool;
use App\Services\Gemini\GeminiClient;

class AgentOrchestrator
{
    /**
     * Ventana mínima antes de repetirle al mismo número una respuesta
     * genérica (fuera de alcance, cliente no registrado, sin resultados en
     * KB/FAQ). Repetirla en cada consulta no aporta información nueva y se
     * siente robotizado; las respuestas con datos reales (KB/FAQ con
     * resultados, datos del cliente) no están sujetas a este límite.
     */
    protected const THROTTLE_HOURS = 48;

    /**
     * Ventana mínima antes de volver a notificarle al agente asignado sobre
     * el mismo ticket. Sin esto, un cliente insistiendo con preguntas que el
     * agente automático no puede responder saturaría de correos/notificaciones
     * al agente humano por cada mensaje.
     */
    protected const ESCALATION_THROTTLE_HOURS = 6;

    public function __construct(
        protected GeminiClient $gemini,
        protected VerificarClienteTool $verificarCliente,
        protected GetPolizaPorClienteTool $getPoliza,
        protected GetEstadoPagoTool $getPago,
        protected KnowledgeBaseSearch $kb,
    ) {}

    /**
     * Pipeline compartido por el webhook real de WhatsApp y el endpoint de prueba.
     * El canal (WhatsApp real o harness de prueba) nunca influye en la lógica.
     *
     * @return array{clasificacion: array<string, mixed>, tool_calls: array<int, array<string, mixed>>, respuesta_final: array<string, mixed>}
     */
    public function procesarMensaje(string $telefono, string $mensaje, string $canal): array
    {
        $clasificacion = [];
        $toolCalls = [];
        $clienteId = null;
        $error = null;

        try {
            $clasificacion = $this->clasificar($mensaje);

            if (($clasificacion['tipo_intencion'] ?? null) === 'fuera_de_alcance') {
                $respuestaFinal = $this->respuestaThrottleable($telefono, 'fuera_de_alcance', fn () => $this->respuestaFueraDeAlcance());
            } elseif ($clasificacion['requiere_datos_cliente'] ?? false) {
                [$respuestaFinal, $toolCalls, $clienteId] = $this->resolverConsultaCliente($telefono, $mensaje, $clasificacion);
            } elseif (($clasificacion['tipo_intencion'] ?? null) === 'kb_categoria') {
                $docs = $this->kb->buscarPorCategoria($clasificacion['categoria_kb'] ?? null, $mensaje);
                $respuestaFinal = $docs === []
                    ? $this->respuestaThrottleable($telefono, 'kb_sin_resultados', fn () => $this->respuestaSinResultados('kb_sin_resultados'))
                    : $this->generar($mensaje, $docs, 'kb');
            } else {
                $docs = $this->kb->buscarFaq($mensaje);
                $respuestaFinal = $docs === []
                    ? $this->respuestaThrottleable($telefono, 'faq_sin_resultados', fn () => $this->respuestaSinResultados('faq_sin_resultados'))
                    : $this->generar($mensaje, $docs, 'faq');
            }
        } catch (GeminiApiException $e) {
            // Sin esto, un fallo de Gemini (timeout, cuota, modelo caído) se
            // perdía en el trace de la excepción y el usuario se quedaba sin
            // ninguna respuesta y sin rastro en la auditoría. Se degrada a un
            // mensaje honesto y se deja constancia del error para poder verlo
            // en el panel en vez de tener que buscar en los logs del servidor.
            $error = $e->getMessage();
            $respuestaFinal = $this->respuestaErrorTecnico();
        }

        $this->registrarAuditoria($telefono, $mensaje, $canal, $clasificacion, $toolCalls, $respuestaFinal, $clienteId, $error);
        $this->escalarSiCorresponde($canal, $clienteId, $mensaje, $respuestaFinal);

        return [
            'clasificacion' => $clasificacion,
            'tool_calls' => $toolCalls,
            'respuesta_final' => $respuestaFinal,
        ];
    }

    /**
     * @param  array<string, mixed>  $clasificacion
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>, 2: ?int}
     */
    protected function resolverConsultaCliente(string $telefono, string $mensaje, array $clasificacion): array
    {
        $toolCalls = [];

        $verificacion = $this->verificarCliente->verificar($telefono);
        $toolCalls[] = ['nombre' => 'verificarCliente', 'resultado' => $verificacion];

        if (! ($verificacion['registrado'] ?? false)) {
            $respuesta = $this->respuestaThrottleable($telefono, 'cliente_no_registrado', fn () => $this->respuestaClienteNoRegistrado());

            return [$respuesta, $toolCalls, null];
        }

        $clienteId = $verificacion['cliente_id'];
        $subIntencion = $clasificacion['sub_intencion_cliente'] ?? 'estado_general';
        $contexto = [];

        if (in_array($subIntencion, ['poliza', 'estado_general'], true)) {
            $polizas = $this->getPoliza->obtener($clienteId);
            $toolCalls[] = ['nombre' => 'getPolizaPorCliente', 'resultado' => $polizas];
            $contexto['polizas'] = $polizas;
        }

        if (in_array($subIntencion, ['pago', 'estado_general'], true)) {
            $pagos = $this->getPago->obtener($clienteId);
            $toolCalls[] = ['nombre' => 'getEstadoPago', 'resultado' => $pagos];
            $contexto['pagos'] = $pagos;
        }

        return [$this->generar($mensaje, $contexto, 'datos_cliente'), $toolCalls, $clienteId];
    }

    /**
     * @return array<string, mixed>
     */
    protected function clasificar(string $mensaje): array
    {
        $categorias = $this->kb->categoriasDisponibles();

        $categoriaKbProperty = ['type' => 'STRING', 'nullable' => true];

        if ($categorias !== []) {
            // Restringe al modelo a categorías que realmente existen, en vez de
            // dejarlo adivinar un string libre que nunca va a matchear en la KB.
            $categoriaKbProperty['enum'] = $categorias;
        }

        $instruccionCategorias = $categorias === []
            ? ' Por ahora no hay categorías de artículos de KB registradas, así que no uses tipo_intencion "kb_categoria".'
            : ' Las categorías de artículos de KB disponibles son: '.implode(', ', $categorias)
                .'. Si la pregunta encaja en una de ellas, usa tipo_intencion "kb_categoria" y copia el nombre '
                .'exacto de la categoría en categoria_kb.';

        $clasificacion = $this->gemini->generateJson(
            systemInstruction: 'Clasifica la pregunta del usuario en una de las categorías definidas. '
                .'No respondas la pregunta, solo clasifícala. Si la pregunta menciona pólizas, pagos, '
                .'cobertura, estado de cuenta o datos personales del cliente, marca requiere_datos_cliente: true.'
                .$instruccionCategorias,
            contents: [[
                'role' => 'user',
                'parts' => [['text' => $mensaje]],
            ]],
            responseSchema: [
                'type' => 'OBJECT',
                'properties' => [
                    'tipo_intencion' => [
                        'type' => 'STRING',
                        'enum' => ['faq', 'kb_categoria', 'consulta_cliente', 'fuera_de_alcance'],
                    ],
                    'categoria_kb' => $categoriaKbProperty,
                    'requiere_datos_cliente' => ['type' => 'BOOLEAN'],
                    'sub_intencion_cliente' => [
                        'type' => 'STRING',
                        'enum' => ['poliza', 'pago', 'estado_general'],
                        'nullable' => true,
                    ],
                    'confianza' => ['type' => 'NUMBER'],
                ],
                'required' => ['tipo_intencion', 'requiere_datos_cliente', 'confianza'],
            ],
            temperature: 0.0,
        );

        $clasificacion['requiere_datos_cliente'] = ($clasificacion['tipo_intencion'] ?? null) === 'consulta_cliente'
            || ($clasificacion['requiere_datos_cliente'] ?? false);

        return $clasificacion;
    }

    /**
     * @param  array<string, mixed>  $contexto
     * @return array<string, mixed>
     */
    protected function generar(string $mensaje, array $contexto, string $fuente): array
    {
        $respuesta = $this->gemini->generateJson(
            systemInstruction: 'Responde solo con base en el contexto proporcionado. Si el contexto no '
                .'contiene la respuesta, indícalo explícitamente. No inventes datos ni cifras.',
            contents: [[
                'role' => 'user',
                'parts' => [[
                    'text' => "Contexto:\n".json_encode($contexto, JSON_UNESCAPED_UNICODE)."\n\nPregunta: {$mensaje}",
                ]],
            ]],
            responseSchema: [
                'type' => 'OBJECT',
                'properties' => [
                    'respuesta' => ['type' => 'STRING'],
                    'fuente' => ['type' => 'STRING'],
                    'requiere_seguimiento_humano' => ['type' => 'BOOLEAN'],
                ],
                'required' => ['respuesta', 'fuente', 'requiere_seguimiento_humano'],
            ],
            temperature: 0.2,
        );

        // La fuente la determina el enrutamiento, no el modelo.
        $respuesta['fuente'] = $fuente;

        return $respuesta;
    }

    /**
     * Evita repetirle al mismo número una respuesta genérica (que no
     * responde nada concreto) más de una vez cada {@see self::THROTTLE_HOURS}
     * horas. Si ya se envió una con esta $fuente en la ventana, la consulta
     * se registra igualmente en la auditoría pero no se envía mensaje por
     * WhatsApp (ProcessWhatsAppWebhookEvent omite el envío si la respuesta
     * viene vacía).
     *
     * @param  \Closure(): array<string, mixed>  $generarRespuesta
     * @return array<string, mixed>
     */
    protected function respuestaThrottleable(string $telefono, string $fuente, \Closure $generarRespuesta): array
    {
        $enviadaRecientemente = AgentAuditLog::query()
            ->where('telefono', $telefono)
            ->where('fuente', $fuente)
            ->where('created_at', '>=', now()->subHours(self::THROTTLE_HOURS))
            ->exists();

        if ($enviadaRecientemente) {
            return [
                'respuesta' => '',
                'fuente' => $fuente,
                'requiere_seguimiento_humano' => true,
            ];
        }

        return $generarRespuesta();
    }

    /**
     * Se usa cuando Gemini falla (timeout, cuota, modelo caído). No debe
     * delatar que hubo un problema técnico interno; de cara al cliente se ve
     * igual que cualquier otro caso que requiere seguimiento humano.
     *
     * @return array<string, mixed>
     */
    protected function respuestaErrorTecnico(): array
    {
        return [
            'respuesta' => 'Gracias por contactarnos, un agente se pondrá en contacto contigo.',
            'fuente' => 'error',
            'requiere_seguimiento_humano' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function respuestaFueraDeAlcance(): array
    {
        return [
            'respuesta' => 'Gracias por contactarnos, un agente se pondrá en contacto contigo.',
            'fuente' => 'fuera_de_alcance',
            'requiere_seguimiento_humano' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function respuestaClienteNoRegistrado(): array
    {
        return [
            'respuesta' => 'No encuentro este número registrado como cliente. Si ya eres cliente, contáctanos '
                .'desde el número telefónico registrado en tu póliza.',
            'fuente' => 'cliente_no_registrado',
            'requiere_seguimiento_humano' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function respuestaSinResultados(string $fuente): array
    {
        return [
            'respuesta' => 'Gracias por contactarnos, un agente se pondrá en contacto contigo.',
            'fuente' => $fuente,
            'requiere_seguimiento_humano' => true,
        ];
    }

    /**
     * Notifica al agente asignado al ticket más reciente del cliente cuando
     * el agente automático no pudo resolver la consulta. No aplica al canal
     * de prueba (evita notificaciones por pruebas del harness), a clientes
     * no identificados (no hay a quién asignarle el ticket) ni a respuestas
     * genéricas ya suprimidas por el throttle de {@see respuestaThrottleable}.
     *
     * @param  array<string, mixed>  $respuestaFinal
     */
    protected function escalarSiCorresponde(string $canal, ?int $clienteId, string $mensaje, array $respuestaFinal): void
    {
        if ($canal === 'test' || $clienteId === null) {
            return;
        }

        if (! ($respuestaFinal['requiere_seguimiento_humano'] ?? false) || $respuestaFinal['respuesta'] === '') {
            return;
        }

        $ticket = Client::query()->find($clienteId)?->tickets()->latest()->first();

        if (! $ticket || ! $ticket->agent_id) {
            return;
        }

        if ($ticket->agent_escalated_at?->gt(now()->subHours(self::ESCALATION_THROTTLE_HOURS))) {
            return;
        }

        $ticket->agent?->notify(new AgentEscalationRequired($ticket, $mensaje, $respuestaFinal['fuente'] ?? 'desconocido'));
        $ticket->update(['agent_escalated_at' => now()]);
    }

    /**
     * @param  array<string, mixed>  $clasificacion
     * @param  array<int, array<string, mixed>>  $toolCalls
     * @param  array<string, mixed>  $respuestaFinal
     */
    protected function registrarAuditoria(
        string $telefono,
        string $mensaje,
        string $canal,
        array $clasificacion,
        array $toolCalls,
        array $respuestaFinal,
        ?int $clienteId,
        ?string $error = null,
    ): void {
        AgentAuditLog::create([
            'tenant_id' => $clienteId !== null ? Client::query()->find($clienteId)?->tenant_id : null,
            'client_id' => $clienteId,
            'telefono' => $telefono,
            'canal' => $canal,
            'mensaje' => $mensaje,
            'tipo_intencion' => $clasificacion['tipo_intencion'] ?? null,
            'confianza' => $clasificacion['confianza'] ?? null,
            'tool_calls' => $toolCalls,
            'fuente' => $respuestaFinal['fuente'] ?? null,
            'requiere_seguimiento_humano' => $respuestaFinal['requiere_seguimiento_humano'] ?? false,
            'error' => $error,
        ]);
    }
}
