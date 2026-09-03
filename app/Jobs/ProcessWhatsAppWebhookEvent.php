<?php

namespace App\Jobs;

use App\Exceptions\WhatsAppApiException;
use App\Models\Client;
use App\Models\Interaction;
use App\Models\WhatsappNotification;
use App\Models\WhatsappWebhookEvent;
use App\Services\Agent\AgentOrchestrator;
use App\Services\WhatsApp\WhatsAppClient;
use App\Services\WhatsApp\WhatsAppClientFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppWebhookEvent implements ShouldQueue
{
    use Queueable;

    /**
     * Statuses in delivery order, used to avoid overwriting a later status
     * with a duplicate or out-of-order delivery from Meta.
     *
     * @var array<int, string>
     */
    protected const STATUS_ORDER = ['queued', 'sent', 'delivered', 'read', 'failed'];

    /**
     * Tenant an inbound message is assigned to when it arrives on the
     * shared/common WhatsApp number and can't be resolved to a specific
     * tenant any other way.
     */
    protected const SHARED_NUMBER_FALLBACK_TENANT_ID = 1;

    public function __construct(public int $webhookEventId) {}

    public function handle(): void
    {
        $event = WhatsappWebhookEvent::find($this->webhookEventId);

        if (! $event || $event->processed) {
            return;
        }

        match ($event->type) {
            'status_update' => $this->applyStatusUpdate($event),
            'inbound_message' => $this->routeInboundMessage($event),
            default => null,
        };

        $event->update(['processed' => true]);
    }

    protected function applyStatusUpdate(WhatsappWebhookEvent $event): void
    {
        $status = $event->payload['status'] ?? null;
        $wamid = $event->wamid;

        if (! $status || ! $wamid) {
            return;
        }

        $notification = WhatsappNotification::where('wamid', $wamid)->first();

        if (! $notification) {
            return;
        }

        $currentIndex = array_search($notification->status, self::STATUS_ORDER, true);
        $newIndex = array_search($status, self::STATUS_ORDER, true);

        if ($newIndex === false || ($currentIndex !== false && $newIndex <= $currentIndex)) {
            return;
        }

        $attributes = ['status' => $status];

        if ($status === 'delivered') {
            $attributes['delivered_at'] = now();
        } elseif ($status === 'read') {
            $attributes['read_at'] = now();
        } elseif ($status === 'failed') {
            $error = $event->payload['errors'][0] ?? [];
            $attributes['error_code'] = $error['code'] ?? null;
            $attributes['error_message'] = $error['message'] ?? null;
        }

        $notification->update($attributes);
    }

    protected function routeInboundMessage(WhatsappWebhookEvent $event): void
    {
        $from = $event->payload['from'] ?? null;

        if (! $from) {
            return;
        }

        $last10 = substr(preg_replace('/[^0-9]/', '', $from), -10);

        // A channel resolved from phone_number_id pins the tenant, so the
        // client lookup below never crosses into another tenant's data. If
        // the event carries no known channel, the message arrived on the
        // shared/common number used by tenants without their own WhatsApp
        // channel (this MVP's default), so the tenant must be inferred from
        // the conversation instead.
        $tenantId = $event->whatsapp_channel_id
            ? $event->whatsappChannel?->tenant_id
            : $this->resolveTenantForSharedNumber($last10);

        $event->update(['tenant_id' => $tenantId]);

        $client = Client::query()
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') LIKE ?",
                ['%'.$last10]
            )
            ->first();

        if (! $client) {
            return;
        }

        $ticket = $client->tickets()->latest()->first();

        if (! $ticket) {
            return;
        }

        $text = $event->payload['text']['body']
            ?? $event->payload['button']['text']
            ?? '[mensaje sin texto]';

        Interaction::create([
            'tenant_id' => $client->tenant_id,
            'ticket_id' => $ticket->id,
            'channel' => 'whatsapp',
            'message' => $text,
        ]);

        $this->responderConAgente($event, $from, $text);
    }

    /**
     * Resolves the tenant an inbound message on the shared number belongs
     * to, in order:
     * 1. The tenant that most recently sent this customer a WhatsApp
     *    notification (the tenant currently "owning" the conversation).
     * 2. Else, the tenant that has a client registered with this phone.
     * 3. Else, the default tenant (id 1).
     */
    protected function resolveTenantForSharedNumber(string $last10): int
    {
        $lastNotificationTenantId = WhatsappNotification::query()
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`to`, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') LIKE ?",
                ['%'.$last10]
            )
            ->latest('id')
            ->value('tenant_id');

        if ($lastNotificationTenantId) {
            return $lastNotificationTenantId;
        }

        $clientTenantId = Client::query()
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') LIKE ?",
                ['%'.$last10]
            )
            ->value('tenant_id');

        return $clientTenantId ?? self::SHARED_NUMBER_FALLBACK_TENANT_ID;
    }

    /**
     * Procesa el mensaje entrante con el agente y envía la respuesta como un
     * mensaje de sesión por el mismo canal que recibió el mensaje.
     */
    protected function responderConAgente(WhatsappWebhookEvent $event, string $from, string $text): void
    {
        $resultado = app(AgentOrchestrator::class)->procesarMensaje($from, $text, 'whatsapp');
        $respuesta = $resultado['respuesta_final']['respuesta'] ?? null;

        if (blank($respuesta)) {
            return;
        }

        $whatsAppClient = $event->whatsappChannel
            ? app(WhatsAppClientFactory::class)->forChannel($event->whatsappChannel)
            : app(WhatsAppClient::class);

        try {
            $whatsAppClient->sendText($from, $respuesta);
        } catch (WhatsAppApiException $e) {
            Log::warning('No se pudo enviar la respuesta del agente por WhatsApp.', [
                'whatsapp_webhook_event_id' => $event->id,
                'error' => $e->getMessage(),
                'retryable' => $e->retryable,
            ]);
        }
    }
}
