<?php

namespace App\Services\Agent;

use App\Models\AgentAuditLog;
use App\Models\Client;
use App\Services\Agent\Tools\GetEstadoPagoTool;
use App\Services\Agent\Tools\GetPolizaPorClienteTool;
use App\Services\Agent\Tools\VerificarClienteTool;
use App\Services\Gemini\GeminiClient;

class AgentOrchestrator
{
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
        $clasificacion = $this->clasificar($mensaje);
        $toolCalls = [];
        $clienteId = null;

        if (($clasificacion['tipo_intencion'] ?? null) === 'fuera_de_alcance') {
            $respuestaFinal = $this->respuestaFueraDeAlcance();
        } elseif ($clasificacion['requiere_datos_cliente'] ?? false) {
            [$respuestaFinal, $toolCalls, $clienteId] = $this->resolverConsultaCliente($telefono, $mensaje, $clasificacion);
        } elseif (($clasificacion['tipo_intencion'] ?? null) === 'kb_categoria') {
            $docs = $this->kb->buscarPorCategoria($clasificacion['categoria_kb'] ?? null, $mensaje);
            $respuestaFinal = $docs === [] ? $this->respuestaSinResultados('kb') : $this->generar($mensaje, $docs, 'kb');
        } else {
            $docs = $this->kb->buscarFaq($mensaje);
            $respuestaFinal = $docs === [] ? $this->respuestaSinResultados('faq') : $this->generar($mensaje, $docs, 'faq');
        }

        $this->registrarAuditoria($telefono, $mensaje, $canal, $clasificacion, $toolCalls, $respuestaFinal, $clienteId);

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
            return [$this->respuestaClienteNoRegistrado(), $toolCalls, null];
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
     * @return array<string, mixed>
     */
    protected function respuestaFueraDeAlcance(): array
    {
        return [
            'respuesta' => 'Lo siento, no puedo ayudarte con esa consulta. Un asesor humano se pondrá en contacto contigo.',
            'fuente' => 'sistema',
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
            'fuente' => 'sistema',
            'requiere_seguimiento_humano' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function respuestaSinResultados(string $fuente): array
    {
        return [
            'respuesta' => 'No encontré información sobre tu consulta en nuestra base de conocimiento. Un asesor la revisará contigo.',
            'fuente' => $fuente,
            'requiere_seguimiento_humano' => true,
        ];
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
        ]);
    }
}
