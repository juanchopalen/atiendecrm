<?php

namespace App\Jobs;

use App\Models\Policy;
use App\Models\Tenant;
use Filament\Facades\Filament;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExpirationReminderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $tenantId,
        public int $policyId,
    ) {}

    public function handle(): void
    {
        $tenant = Tenant::find($this->tenantId);

        if (! $tenant) {
            return;
        }

        Filament::setTenant($tenant);

        $policy = Policy::find($this->policyId);

        if (! $policy || $policy->status !== 'active') {
            return;
        }

        Log::info("Expiration reminder: policy {$policy->policy_number} for client {$policy->client->name} expires on {$policy->expiration_date->toDateString()}.");
    }
}
