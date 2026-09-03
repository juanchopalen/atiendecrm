<?php

namespace App\Services\Agent\Tools;

use App\Models\Policy;

class GetPolizaPorClienteTool
{
    /**
     * Obtiene las pólizas activas de un cliente ya verificado.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtener(int $clienteId): array
    {
        return Policy::query()
            ->where('client_id', $clienteId)
            ->where('status', 'active')
            ->get()
            ->map(fn (Policy $policy): array => [
                'poliza_id' => $policy->id,
                'numero_poliza' => $policy->policy_number,
                'tipo' => $policy->line_of_business,
                'estado' => $policy->status,
                'fecha_vencimiento' => $policy->expiration_date?->toDateString(),
                'monto_cobertura' => $policy->premium,
            ])
            ->all();
    }
}
