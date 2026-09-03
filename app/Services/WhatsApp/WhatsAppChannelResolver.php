<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsappChannel;

class WhatsAppChannelResolver
{
    /**
     * Resolve the channel a webhook event belongs to. This must always be
     * derived from the receiving phone_number_id, never from message
     * content or sender data — the same principle used for client
     * verification (security by channel, not by content).
     */
    public function resolveByPhoneNumberId(string $phoneNumberId): ?WhatsappChannel
    {
        return WhatsappChannel::query()
            ->withoutGlobalScopes()
            ->where('phone_number_id', $phoneNumberId)
            ->first();
    }

    /**
     * Resolve the active, non-demo channel a tenant should send a
     * notification for a given department from.
     */
    public function resolveForTenant(int $tenantId, string $departamento): ?WhatsappChannel
    {
        return WhatsappChannel::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('departamento', $departamento)
            ->where('estado', 'active')
            ->where('solo_demo', false)
            ->first();
    }
}
