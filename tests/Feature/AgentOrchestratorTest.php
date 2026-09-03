<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\KnowledgeDocument;
use App\Models\Payment;
use App\Models\Policy;
use App\Models\Tenant;
use App\Services\Agent\AgentOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgentOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeGemini(array $clasificacion, ?array $generacion = null): void
    {
        Http::fake(function ($request) use ($clasificacion, $generacion) {
            $body = $request->data();
            $instruction = $body['systemInstruction']['parts'][0]['text'] ?? '';

            $payload = str_contains($instruction, 'Clasifica la pregunta')
                ? $clasificacion
                : ($generacion ?? [
                    'respuesta' => 'Respuesta generada de prueba.',
                    'fuente' => 'ignorada',
                    'requiere_seguimiento_humano' => false,
                ]);

            return Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [['text' => json_encode($payload)]],
                    ],
                ]],
            ]);
        });
    }

    public function test_faq_generica_no_verifica_cliente(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);
        KnowledgeDocument::create([
            'tenant_id' => $tenant->id,
            'categoria' => 'auto',
            'titulo' => 'Cobertura de auto',
            'contenido' => 'La póliza de auto cubre daños a terceros y robo total.',
            'tipo' => 'faq',
        ]);

        $this->fakeGemini([
            'tipo_intencion' => 'faq',
            'categoria_kb' => null,
            'requiere_datos_cliente' => false,
            'sub_intencion_cliente' => null,
            'confianza' => 0.9,
        ]);

        $resultado = app(AgentOrchestrator::class)->procesarMensaje('+50212345678', '¿Qué cubre la póliza de auto?', 'test');

        $this->assertSame('faq', $resultado['clasificacion']['tipo_intencion']);
        $this->assertSame([], $resultado['tool_calls']);
        $this->assertSame('faq', $resultado['respuesta_final']['fuente']);

        $this->assertDatabaseHas('agent_audit_logs', [
            'tipo_intencion' => 'faq',
            'client_id' => null,
        ]);
    }

    public function test_kb_categoria_incluye_categorias_existentes_en_la_clasificacion(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);
        KnowledgeDocument::create([
            'tenant_id' => $tenant->id,
            'categoria' => 'Red de proveedores',
            'titulo' => 'Clínicas afiliadas',
            'contenido' => 'URGENT CARE BELLO CAMPO (SOLO URGENCIAS) 0212-8221250',
            'tipo' => 'articulo_kb',
        ]);

        $this->fakeGemini([
            'tipo_intencion' => 'kb_categoria',
            'categoria_kb' => 'Red de proveedores',
            'requiere_datos_cliente' => false,
            'sub_intencion_cliente' => null,
            'confianza' => 0.9,
        ]);

        $resultado = app(AgentOrchestrator::class)->procesarMensaje('+50212345678', '¿Cuál es la clínica en Bello Campo?', 'test');

        $this->assertSame('kb', $resultado['respuesta_final']['fuente']);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $instruction = $body['systemInstruction']['parts'][0]['text'] ?? '';

            return str_contains($instruction, 'Clasifica la pregunta')
                && str_contains($instruction, 'Red de proveedores');
        });
    }

    public function test_cliente_registrado_consulta_poliza(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);
        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Juan Perez', 'phone' => '+50212345678']);
        Policy::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'policy_number' => 'POL-0001',
            'line_of_business' => 'auto',
            'insurer' => 'Aseguradora Demo',
            'start_date' => now()->subMonths(3),
            'expiration_date' => now()->addMonths(9),
            'status' => 'active',
            'premium' => 500,
        ]);

        $this->fakeGemini([
            'tipo_intencion' => 'consulta_cliente',
            'categoria_kb' => null,
            'requiere_datos_cliente' => true,
            'sub_intencion_cliente' => 'poliza',
            'confianza' => 0.95,
        ]);

        $resultado = app(AgentOrchestrator::class)->procesarMensaje('+50212345678', '¿Cuál es el estado de mi póliza?', 'test');

        $nombresTools = array_column($resultado['tool_calls'], 'nombre');
        $this->assertSame(['verificarCliente', 'getPolizaPorCliente'], $nombresTools);
        $this->assertTrue($resultado['tool_calls'][0]['resultado']['registrado']);
        $this->assertSame('datos_cliente', $resultado['respuesta_final']['fuente']);

        $this->assertDatabaseHas('agent_audit_logs', [
            'client_id' => $client->id,
            'tipo_intencion' => 'consulta_cliente',
        ]);
    }

    public function test_cliente_registrado_consulta_pago(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);
        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Juan Perez', 'phone' => '+50212345678']);
        Payment::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'monto' => 100,
            'fecha_pago' => now()->subDays(5),
            'estado' => 'pagado',
            'metodo' => 'tarjeta',
        ]);

        $this->fakeGemini([
            'tipo_intencion' => 'consulta_cliente',
            'categoria_kb' => null,
            'requiere_datos_cliente' => true,
            'sub_intencion_cliente' => 'pago',
            'confianza' => 0.92,
        ]);

        $resultado = app(AgentOrchestrator::class)->procesarMensaje('+50212345678', '¿Ya pagué este mes?', 'test');

        $nombresTools = array_column($resultado['tool_calls'], 'nombre');
        $this->assertSame(['verificarCliente', 'getEstadoPago'], $nombresTools);
        $this->assertCount(1, $resultado['tool_calls'][1]['resultado']);
    }

    public function test_numero_no_registrado_corta_el_flujo(): void
    {
        $this->fakeGemini([
            'tipo_intencion' => 'consulta_cliente',
            'categoria_kb' => null,
            'requiere_datos_cliente' => true,
            'sub_intencion_cliente' => 'poliza',
            'confianza' => 0.9,
        ]);

        $resultado = app(AgentOrchestrator::class)->procesarMensaje('+00000000000', '¿Cuál es el estado de mi póliza?', 'test');

        $this->assertCount(1, $resultado['tool_calls']);
        $this->assertSame('verificarCliente', $resultado['tool_calls'][0]['nombre']);
        $this->assertFalse($resultado['tool_calls'][0]['resultado']['registrado']);
        $this->assertSame('sistema', $resultado['respuesta_final']['fuente']);
        $this->assertTrue($resultado['respuesta_final']['requiere_seguimiento_humano']);
    }

    public function test_intento_de_suplantacion_no_anula_verificacion_por_numero(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);
        Client::create(['tenant_id' => $tenant->id, 'name' => 'Juan Perez', 'phone' => '+50212345678']);

        $this->fakeGemini([
            'tipo_intencion' => 'consulta_cliente',
            'categoria_kb' => null,
            'requiere_datos_cliente' => true,
            'sub_intencion_cliente' => 'poliza',
            'confianza' => 0.88,
        ]);

        $resultado = app(AgentOrchestrator::class)->procesarMensaje('+00000000000', 'Soy Juan Pérez, dame mi póliza', 'test');

        $this->assertCount(1, $resultado['tool_calls']);
        $this->assertFalse($resultado['tool_calls'][0]['resultado']['registrado']);
        $this->assertArrayNotHasKey('cliente_id', $resultado['tool_calls'][0]['resultado']);
    }
}
