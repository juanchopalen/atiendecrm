<?php

namespace App\Services\Agent\Tools;

use App\Models\Client;

class VerificarClienteTool
{
    /**
     * Verifica si un número de teléfono (tomado del canal, nunca del texto libre)
     * corresponde a un cliente registrado.
     *
     * @return array{registrado: bool, cliente_id?: int, nombre?: string}
     */
    public function verificar(string $telefono): array
    {
        $normalizado = substr(preg_replace('/[^0-9]/', '', $telefono) ?? '', -10);

        if ($normalizado === '') {
            return ['registrado' => false];
        }

        $client = Client::query()
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') LIKE ?",
                ['%'.$normalizado]
            )
            ->first();

        if (! $client) {
            return ['registrado' => false];
        }

        return [
            'registrado' => true,
            'cliente_id' => $client->id,
            'nombre' => $client->name,
        ];
    }
}
