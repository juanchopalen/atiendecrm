<?php

namespace App\Console\Commands\WhatsApp;

use App\Models\Inbox;
use App\Models\Tenant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Provisions the "Bandeja general (número compartido)" for a tenant so it's
 * visible in the panel immediately, instead of waiting for the first
 * inbound/outbound message on the shared number to create it lazily.
 */
#[Signature('whatsapp:ensure-shared-inbox {tenant? : Tenant ID (defaults to the shared-number fallback tenant, id 1)}')]
#[Description('Ensure the shared-number virtual inbox exists for a tenant')]
class EnsureSharedInboxCommand extends Command
{
    public function handle(): int
    {
        $tenantId = (int) ($this->argument('tenant') ?? 1);

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error("No existe un tenant con id {$tenantId}.");

            return self::FAILURE;
        }

        $inbox = Inbox::virtualForTenant($tenant->id);

        $this->info("Bandeja lista para \"{$tenant->name}\" (id {$inbox->id}).");

        return self::SUCCESS;
    }
}
