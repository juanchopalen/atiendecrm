<?php

namespace App\Services\Agent\Tools;

use App\Models\Payment;

class GetEstadoPagoTool
{
    /**
     * Obtiene el estado de pagos de un cliente ya verificado, opcionalmente por póliza.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtener(int $clienteId, ?int $polizaId = null): array
    {
        $query = Payment::query()->where('client_id', $clienteId);

        if ($polizaId !== null) {
            $query->where('policy_id', $polizaId);
        }

        return $query->orderByDesc('fecha_pago')
            ->get()
            ->map(fn (Payment $pago): array => [
                'pago_id' => $pago->id,
                'poliza_id' => $pago->policy_id,
                'monto' => $pago->monto,
                'fecha_pago' => $pago->fecha_pago?->toDateString(),
                'estado' => $pago->estado,
                'metodo' => $pago->metodo,
            ])
            ->all();
    }
}
