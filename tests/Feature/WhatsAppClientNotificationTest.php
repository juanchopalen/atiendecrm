<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Inbox;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\WhatsappNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppClientNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_client_sends_a_whatsapp_welcome_template(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.WELCOME1']],
            ], 200),
        ]);

        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);

        $client = Client::create([
            'tenant_id' => $tenant->id,
            'name' => 'Juan Perez',
            'phone' => '+52 55 1234 5678',
        ]);

        $notification = WhatsappNotification::where('notifiable_id', $client->id)
            ->where('notifiable_type', $client->getMorphClass())
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame('client_welcome', $notification->template);
        $this->assertSame('sent', $notification->status);
        $this->assertSame('wamid.WELCOME1', $notification->wamid);
        $this->assertSame('525512345678', $notification->to);

        // The tenant has no whatsapp_channels of its own, so the welcome
        // message went out via the shared number, which should provision a
        // virtual inbox for the tenant.
        $this->assertNotNull($notification->tenant_id);
        $this->assertNull($notification->whatsapp_channel_id);
        $inbox = Inbox::where('tenant_id', $tenant->id)->whereNull('whatsapp_channel_id')->first();
        $this->assertNotNull($inbox);
        $this->assertTrue($inbox->isVirtual());
    }

    public function test_no_notification_is_recorded_when_client_has_no_phone(): void
    {
        Http::fake();

        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);

        Client::create([
            'tenant_id' => $tenant->id,
            'name' => 'Sin Telefono',
        ]);

        $this->assertDatabaseCount('whatsapp_notifications', 0);
        Http::assertNothingSent();
    }

    public function test_closing_a_ticket_sends_a_satisfaction_survey_template(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.SURVEY1']],
            ], 200),
        ]);

        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);

        $client = Client::create([
            'tenant_id' => $tenant->id,
            'name' => 'Juan Perez',
            'phone' => '+52 55 1234 5678',
        ]);

        // Sending the welcome template on creation also hits the fake HTTP
        // client; only the survey request matters for this assertion.
        $ticket = Ticket::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'type' => 'consulta',
            'subject' => 'Consulta',
            'status' => 'open',
        ]);

        $ticket->update(['status' => 'closed', 'closed_at' => now()]);

        $notification = WhatsappNotification::where('event', 'ticket.closed')->first();

        $this->assertNotNull($notification);
        $this->assertSame('ticket_satisfaction_survey', $notification->template);
        $this->assertSame('sent', $notification->status);
        $this->assertSame('wamid.SURVEY1', $notification->wamid);
    }

    public function test_updating_a_ticket_without_closing_it_does_not_send_a_survey(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.WELCOME2']],
            ], 200),
        ]);

        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);

        $client = Client::create([
            'tenant_id' => $tenant->id,
            'name' => 'Juan Perez',
            'phone' => '+52 55 1234 5678',
        ]);

        $ticket = Ticket::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'type' => 'consulta',
            'subject' => 'Consulta',
            'status' => 'open',
        ]);

        $ticket->update(['status' => 'in_progress']);

        $this->assertDatabaseMissing('whatsapp_notifications', ['event' => 'ticket.closed']);
    }
}
