<?php

namespace Tests\Feature;

use App\Exceptions\WhatsAppApiException;
use App\Models\Client;
use App\Models\Interaction;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppReplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppReplyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_window_is_open_within_24_hours_of_the_clients_last_message(): void
    {
        [$ticket] = $this->makeTicketWithInboundMessage(now()->subHours(2));

        $this->assertTrue(app(WhatsAppReplyService::class)->isSessionWindowOpen($ticket));
    }

    public function test_session_window_is_closed_after_24_hours(): void
    {
        [$ticket] = $this->makeTicketWithInboundMessage(now()->subHours(25));

        $this->assertFalse(app(WhatsAppReplyService::class)->isSessionWindowOpen($ticket));
    }

    public function test_session_window_is_closed_when_the_client_never_wrote(): void
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);
        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Juan Perez', 'phone' => '+52 55 1234 5678']);
        $ticket = Ticket::create(['tenant_id' => $tenant->id, 'client_id' => $client->id, 'type' => 'consulta', 'subject' => 'Consulta', 'status' => 'open']);

        $this->assertFalse(app(WhatsAppReplyService::class)->isSessionWindowOpen($ticket));
    }

    public function test_an_agent_manually_logged_interaction_does_not_extend_the_window(): void
    {
        [$ticket, $client] = $this->makeTicketWithInboundMessage(now()->subHours(25));

        $agent = User::factory()->create(['tenant_id' => $client->tenant_id]);
        Interaction::create([
            'tenant_id' => $ticket->tenant_id,
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'channel' => 'whatsapp',
            'message' => 'Nota manual del agente',
        ]);

        $this->assertFalse(app(WhatsAppReplyService::class)->isSessionWindowOpen($ticket));
    }

    public function test_reply_sends_a_text_message_and_logs_an_interaction(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.OUT1']]]),
        ]);

        [$ticket, $client] = $this->makeTicketWithInboundMessage(now()->subHours(1));
        $agent = User::factory()->create(['tenant_id' => $client->tenant_id]);

        $interaction = app(WhatsAppReplyService::class)->reply($ticket, 'Hola, ¿en qué te ayudo?', $agent->id);

        $this->assertSame('whatsapp', $interaction->channel);
        $this->assertSame($agent->id, $interaction->user_id);
        $this->assertSame('Hola, ¿en qué te ayudo?', $interaction->message);

        Http::assertSent(fn ($request) => $request['type'] === 'text' && $request['text']['body'] === 'Hola, ¿en qué te ayudo?');
    }

    public function test_reply_throws_when_the_whatsapp_api_rejects_the_message(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['code' => 131047, 'message' => 'Re-engagement message']], 400),
        ]);

        [$ticket] = $this->makeTicketWithInboundMessage(now()->subHours(1));

        $this->expectException(WhatsAppApiException::class);

        app(WhatsAppReplyService::class)->reply($ticket, 'Hola', null);
    }

    /**
     * @return array{0: Ticket, 1: Client}
     */
    protected function makeTicketWithInboundMessage(\DateTimeInterface $inboundAt): array
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);
        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Juan Perez', 'phone' => '+52 55 1234 5678']);
        $ticket = Ticket::create(['tenant_id' => $tenant->id, 'client_id' => $client->id, 'type' => 'consulta', 'subject' => 'Consulta', 'status' => 'open']);

        $interaction = Interaction::create([
            'tenant_id' => $tenant->id,
            'ticket_id' => $ticket->id,
            'user_id' => null,
            'channel' => 'whatsapp',
            'message' => 'Hola, tengo una pregunta',
        ]);
        $interaction->forceFill(['created_at' => $inboundAt])->saveQuietly();

        return [$ticket, $client];
    }
}
