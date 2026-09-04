<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Policy;
use App\Models\Tenant;
use App\Models\WhatsappNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendPolicyExpirationNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_reminder_sends_client_broker_policy_and_date_as_parameters(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.REMINDER1']],
            ], 200),
        ]);

        $tenant = Tenant::create([
            'name' => 'Oswaldo Avilán SC',
            'slug' => 'oswaldo-avilan-sc',
            'is_active' => true,
            'features' => ['notifications' => ['days_to_pay' => 15]],
        ]);

        $client = Client::create([
            'tenant_id' => $tenant->id,
            'name' => 'Miguel Torres',
            'phone' => '+58 412 1234567',
            'email' => 'miguel@example.com',
        ]);

        $policy = Policy::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'policy_number' => 'PL-0225515',
            'line_of_business' => 'auto',
            'insurer' => 'Seguros Caracas',
            'start_date' => now()->subMonths(11),
            'expiration_date' => today()->addDays(15),
            'status' => 'active',
        ]);

        $this->artisan('policies:send-expiration-notifications')->assertSuccessful();

        $notification = WhatsappNotification::where('event', 'policy.expiring_soon')->first();

        $this->assertNotNull($notification);
        $this->assertSame('policy_expiration_reminder', $notification->template);
        $this->assertSame([
            'Miguel Torres',
            'Oswaldo Avilán SC',
            'PL-0225515',
            $policy->expiration_date->translatedFormat('d/m/Y'),
        ], $notification->variables);
    }
}
