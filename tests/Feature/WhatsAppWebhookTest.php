<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Inbox;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\WhatsappNotification;
use App\Models\WhatsappWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_challenge_succeeds_with_correct_token(): void
    {
        config(['services.whatsapp.verify_token' => 'secret-token']);

        $response = $this->get('/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=secret-token&hub.challenge=12345');

        $response->assertOk();
        $response->assertSeeText('12345');
    }

    public function test_verification_challenge_fails_with_wrong_token(): void
    {
        config(['services.whatsapp.verify_token' => 'secret-token']);

        $response = $this->get('/api/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=12345');

        $response->assertForbidden();
    }

    public function test_status_update_webhook_updates_notification_status(): void
    {
        config(['services.whatsapp.app_secret' => null]);

        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);
        $notification = WhatsappNotification::create([
            'tenant_id' => $tenant->id,
            'event' => 'policy.expiring_soon',
            'template' => 'policy_expiration_reminder',
            'language' => 'es_MX',
            'to' => '5215512345678',
            'wamid' => 'wamid.TEST123',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.TEST123',
                            'status' => 'delivered',
                            'timestamp' => (string) now()->timestamp,
                            'recipient_id' => '5215512345678',
                        ]],
                    ],
                ]],
            ]],
        ];

        $response = $this->postJson('/api/whatsapp/webhook', $payload);

        $response->assertOk();

        $notification->refresh();

        $this->assertSame('delivered', $notification->status);
        $this->assertNotNull($notification->delivered_at);
    }

    public function test_inbound_message_creates_interaction_on_latest_ticket(): void
    {
        config(['services.whatsapp.app_secret' => null]);

        Http::fake([
            '*-aiplatform.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'tipo_intencion' => 'fuera_de_alcance',
                                'categoria_kb' => null,
                                'requiere_datos_cliente' => false,
                                'sub_intencion_cliente' => null,
                                'confianza' => 0.8,
                            ]),
                        ]],
                    ],
                ]],
            ]),
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.REPLY0']]]),
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

        $payload = [
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'contacts' => [['profile' => ['name' => 'Juan Perez'], 'wa_id' => '5215512345678']],
                        'messages' => [[
                            'id' => 'wamid.INBOUND1',
                            'from' => '5215512345678',
                            'text' => ['body' => 'CANCELAR'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $response = $this->postJson('/api/whatsapp/webhook', $payload);

        $response->assertOk();

        $this->assertDatabaseHas('interactions', [
            'ticket_id' => $ticket->id,
            'channel' => 'whatsapp',
            'message' => 'CANCELAR',
        ]);
    }

    public function test_inbound_message_triggers_agent_reply_via_whatsapp(): void
    {
        config([
            'services.whatsapp.app_secret' => null,
            'services.whatsapp.phone_number_id' => '1234567890',
            'services.whatsapp.access_token' => 'test-token',
        ]);

        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);
        $client = Client::create([
            'tenant_id' => $tenant->id,
            'name' => 'Juan Perez',
            'phone' => '+52 55 1234 5678',
        ]);
        Ticket::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'type' => 'consulta',
            'subject' => 'Consulta',
            'status' => 'open',
        ]);

        Http::fake([
            '*-aiplatform.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'tipo_intencion' => 'faq',
                                'categoria_kb' => null,
                                'requiere_datos_cliente' => false,
                                'sub_intencion_cliente' => null,
                                'confianza' => 0.8,
                            ]),
                        ]],
                    ],
                ]],
            ]),
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.REPLY1']],
            ]),
        ]);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'contacts' => [['profile' => ['name' => 'Juan Perez'], 'wa_id' => '5215512345678']],
                        'messages' => [[
                            'id' => 'wamid.INBOUND2',
                            'from' => '5215512345678',
                            'text' => ['body' => '¿Qué cubre mi póliza?'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $response = $this->postJson('/api/whatsapp/webhook', $payload);

        $response->assertOk();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v22.0/1234567890/messages'
                && $request['to'] === '5215512345678'
                && $request['type'] === 'text';
        });

        $this->assertDatabaseHas('agent_audit_logs', [
            'telefono' => '5215512345678',
            'canal' => 'whatsapp',
            'tipo_intencion' => 'faq',
        ]);
    }

    public function test_inbound_message_on_shared_number_is_routed_to_the_last_notified_tenant(): void
    {
        config(['services.whatsapp.app_secret' => null]);

        Http::fake([
            '*-aiplatform.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'tipo_intencion' => 'fuera_de_alcance',
                        'categoria_kb' => null,
                        'requiere_datos_cliente' => false,
                        'sub_intencion_cliente' => null,
                        'confianza' => 0.8,
                    ])]]],
                ]],
            ]),
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.REPLY2']]]),
        ]);

        // Two tenants share the same customer phone (e.g. the client moved
        // brokers, or was registered twice). Tenant B is the one that most
        // recently reached out on WhatsApp, so it "owns" the conversation.
        $tenantA = Tenant::create(['name' => 'Tenant A', 'slug' => 'tenant-a', 'is_active' => true]);
        $tenantB = Tenant::create(['name' => 'Tenant B', 'slug' => 'tenant-b', 'is_active' => true]);

        $clientA = Client::create(['tenant_id' => $tenantA->id, 'name' => 'Juan Perez', 'phone' => '+52 55 1234 5678']);
        Ticket::create(['tenant_id' => $tenantA->id, 'client_id' => $clientA->id, 'type' => 'consulta', 'subject' => 'Consulta', 'status' => 'open']);

        $clientB = Client::create(['tenant_id' => $tenantB->id, 'name' => 'Juan Perez', 'phone' => '+52 55 1234 5678']);
        $ticketB = Ticket::create(['tenant_id' => $tenantB->id, 'client_id' => $clientB->id, 'type' => 'consulta', 'subject' => 'Consulta', 'status' => 'open']);

        WhatsappNotification::create([
            'tenant_id' => $tenantB->id,
            'event' => 'client.created',
            'template' => 'client_welcome',
            'language' => 'es_MX',
            'to' => '5215512345678',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'contacts' => [['profile' => ['name' => 'Juan Perez'], 'wa_id' => '5215512345678']],
                        'messages' => [[
                            'id' => 'wamid.SHARED1',
                            'from' => '5215512345678',
                            'text' => ['body' => 'Hola'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $response = $this->postJson('/api/whatsapp/webhook', $payload);

        $response->assertOk();

        $event = WhatsappWebhookEvent::where('wamid', 'wamid.SHARED1')->first();
        $this->assertSame($tenantB->id, $event->tenant_id);

        $this->assertDatabaseHas('interactions', [
            'tenant_id' => $tenantB->id,
            'ticket_id' => $ticketB->id,
            'message' => 'Hola',
        ]);
    }

    public function test_inbound_message_on_shared_number_falls_back_to_client_tenant_without_prior_notification(): void
    {
        config(['services.whatsapp.app_secret' => null]);

        Http::fake([
            '*-aiplatform.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'tipo_intencion' => 'fuera_de_alcance',
                        'categoria_kb' => null,
                        'requiere_datos_cliente' => false,
                        'sub_intencion_cliente' => null,
                        'confianza' => 0.8,
                    ])]]],
                ]],
            ]),
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.REPLY3']]]),
        ]);

        $tenant = Tenant::create(['name' => 'Tenant C', 'slug' => 'tenant-c', 'is_active' => true]);
        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Maria Lopez', 'phone' => '+52 55 9999 0000']);
        $ticket = Ticket::create(['tenant_id' => $tenant->id, 'client_id' => $client->id, 'type' => 'consulta', 'subject' => 'Consulta', 'status' => 'open']);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'contacts' => [['profile' => ['name' => 'Maria Lopez'], 'wa_id' => '5215599990000']],
                        'messages' => [[
                            'id' => 'wamid.SHARED2',
                            'from' => '5215599990000',
                            'text' => ['body' => 'Hola'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $response = $this->postJson('/api/whatsapp/webhook', $payload);

        $response->assertOk();

        $event = WhatsappWebhookEvent::where('wamid', 'wamid.SHARED2')->first();
        $this->assertSame($tenant->id, $event->tenant_id);

        $this->assertDatabaseHas('interactions', [
            'tenant_id' => $tenant->id,
            'ticket_id' => $ticket->id,
        ]);
    }

    public function test_inbound_message_on_shared_number_falls_back_to_default_tenant_when_unresolvable(): void
    {
        config(['services.whatsapp.app_secret' => null]);

        // The default tenant (id 1) must exist for the fallback to resolve.
        Tenant::create(['name' => 'Default Tenant', 'slug' => 'default-tenant', 'is_active' => true]);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'contacts' => [['profile' => ['name' => 'Desconocido'], 'wa_id' => '5215500001111']],
                        'messages' => [[
                            'id' => 'wamid.SHARED3',
                            'from' => '5215500001111',
                            'text' => ['body' => 'Hola'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $response = $this->postJson('/api/whatsapp/webhook', $payload);

        $response->assertOk();

        $event = WhatsappWebhookEvent::where('wamid', 'wamid.SHARED3')->first();
        $this->assertSame(1, $event->tenant_id);
        $this->assertDatabaseCount('interactions', 0);
    }

    public function test_inbound_message_on_shared_number_provisions_a_virtual_inbox_for_the_resolved_tenant(): void
    {
        config(['services.whatsapp.app_secret' => null]);

        Http::fake([
            '*-aiplatform.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'tipo_intencion' => 'fuera_de_alcance',
                        'categoria_kb' => null,
                        'requiere_datos_cliente' => false,
                        'sub_intencion_cliente' => null,
                        'confianza' => 0.8,
                    ])]]],
                ]],
            ]),
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.REPLY4']]]),
        ]);

        $tenant = Tenant::create(['name' => 'Tenant D', 'slug' => 'tenant-d', 'is_active' => true]);
        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Pedro Gomez', 'phone' => '+52 55 4444 5555']);
        Ticket::create(['tenant_id' => $tenant->id, 'client_id' => $client->id, 'type' => 'consulta', 'subject' => 'Consulta', 'status' => 'open']);

        // Creating the client already provisions the tenant's virtual inbox
        // via its welcome notification (outbound path); clear it so this
        // test isolates provisioning from the inbound webhook path instead.
        Inbox::where('tenant_id', $tenant->id)->delete();

        $payload = [
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'contacts' => [['profile' => ['name' => 'Pedro Gomez'], 'wa_id' => '5215544445555']],
                        'messages' => [[
                            'id' => 'wamid.SHARED4',
                            'from' => '5215544445555',
                            'text' => ['body' => 'Hola'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $response = $this->postJson('/api/whatsapp/webhook', $payload);

        $response->assertOk();

        $inbox = Inbox::where('tenant_id', $tenant->id)->whereNull('whatsapp_channel_id')->first();
        $this->assertNotNull($inbox);
        $this->assertTrue($inbox->isVirtual());
    }

    public function test_redelivered_inbound_message_does_not_create_a_duplicate_interaction(): void
    {
        config(['services.whatsapp.app_secret' => null]);

        Http::fake([
            '*-aiplatform.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode([
                        'tipo_intencion' => 'fuera_de_alcance',
                        'categoria_kb' => null,
                        'requiere_datos_cliente' => false,
                        'sub_intencion_cliente' => null,
                        'confianza' => 0.8,
                    ])]]],
                ]],
            ]),
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.REPLY5']]]),
        ]);

        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme', 'is_active' => true]);
        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Juan Perez', 'phone' => '+52 55 1234 5678']);
        Ticket::create(['tenant_id' => $tenant->id, 'client_id' => $client->id, 'type' => 'consulta', 'subject' => 'Consulta', 'status' => 'open']);

        $payload = [
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'contacts' => [['profile' => ['name' => 'Juan Perez'], 'wa_id' => '5215512345678']],
                        'messages' => [[
                            'id' => 'wamid.DUPLICATE1',
                            'from' => '5215512345678',
                            'text' => ['body' => 'Hola'],
                        ]],
                    ],
                ]],
            ]],
        ];

        // Meta redelivers the same webhook event up to 3 times when it
        // doesn't get an acknowledgement fast enough — this must not create
        // 3 interactions or trigger 3 agent replies.
        $this->postJson('/api/whatsapp/webhook', $payload)->assertOk();
        $this->postJson('/api/whatsapp/webhook', $payload)->assertOk();
        $this->postJson('/api/whatsapp/webhook', $payload)->assertOk();

        $this->assertSame(1, WhatsappWebhookEvent::where('wamid', 'wamid.DUPLICATE1')->count());
        $this->assertDatabaseCount('interactions', 1);

        // 1 welcome template on client creation + 1 Gemini classification + 1 agent
        // reply for the single processed webhook — not the 5 a triple delivery
        // without deduplication would cause (welcome + 3x(classification + reply)).
        Http::assertSentCount(3);
    }

    public function test_receive_rejects_invalid_signature_when_secret_configured(): void
    {
        config(['services.whatsapp.app_secret' => 'top-secret']);

        $response = $this->postJson('/api/whatsapp/webhook', ['entry' => []], [
            'X-Hub-Signature-256' => 'sha256=invalid',
        ]);

        $response->assertForbidden();
    }
}
