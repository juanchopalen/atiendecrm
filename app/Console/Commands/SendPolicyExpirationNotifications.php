<?php

namespace App\Console\Commands;

use App\Models\Policy;
use App\Models\Tenant;
use App\Notifications\PolicyExpiringSoon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('policies:send-expiration-notifications')]
#[Description('Notify clients by email when their policy is N days from expiring, N being configured per tenant in features.notifications.days_to_pay')]
class SendPolicyExpirationNotifications extends Command
{
    public function handle(): void
    {
        Tenant::query()->each(function (Tenant $tenant): void {
            $days = $tenant->notificationDaysToPay();

            if ($days === null) {
                return;
            }

            $targetDate = today()->addDays($days);

            Policy::query()
                ->where('tenant_id', $tenant->id)
                ->where('status', 'active')
                ->whereDate('expiration_date', $targetDate)
                ->whereNull('expiration_notified_at')
                ->with('client')
                ->each(function (Policy $policy) use ($days): void {
                    if (blank($policy->client?->email)) {
                        return;
                    }

                    $policy->client->notify(new PolicyExpiringSoon($policy, $days));
                    $policy->update(['expiration_notified_at' => now()]);

                    $this->info("Notified client {$policy->client->name} about policy {$policy->policy_number}.");
                });
        });
    }
}
