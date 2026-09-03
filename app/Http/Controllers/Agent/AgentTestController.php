<?php

namespace App\Http\Controllers\Agent;

use App\Exceptions\GeminiApiException;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\Agent\AgentOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentTestController extends Controller
{
    /**
     * Lista de clientes disponibles para simular en el harness de prueba,
     * más la opción explícita de "número no registrado".
     */
    public function clientes(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;

        $query = Client::query()->orderBy('name');

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $clientes = $query->get(['id', 'name', 'phone'])
            ->map(fn (Client $client): array => [
                'cliente_id' => $client->id,
                'nombre' => $client->name,
                'telefono' => $client->phone,
            ]);

        return response()->json([
            'clientes' => $clientes,
            'no_registrado' => [
                'telefono' => '+00000000000',
                'etiqueta' => 'Número no registrado',
            ],
        ]);
    }

    /**
     * Mismo pipeline que /webhook/whatsapp, sin efectos hacia WhatsApp real.
     */
    public function mensaje(Request $request, AgentOrchestrator $orchestrator): JsonResponse
    {
        $data = $request->validate([
            'telefono' => ['required', 'string'],
            'mensaje' => ['required', 'string', 'max:2000'],
        ]);

        try {
            return response()->json(
                $orchestrator->procesarMensaje($data['telefono'], $data['mensaje'], 'test')
            );
        } catch (GeminiApiException $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }
}
