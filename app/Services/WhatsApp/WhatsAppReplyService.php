<?php

namespace App\Services\WhatsApp;

use App\Exceptions\WhatsAppApiException;
use App\Models\Interaction;
use App\Models\Ticket;
use Illuminate\Support\Carbon;

/**
 * Lets an agent reply to a client's WhatsApp conversation from within a
 * Ticket, respecting Meta's 24-hour customer service session window: a
 * free-text message can only be sent within 24 hours of the client's last
 * inbound WhatsApp message; outside that window Meta rejects it and an
 * approved template must be used instead (not implemented here yet).
 */
class WhatsAppReplyService
{
    public const SESSION_WINDOW_HOURS = 24;

    public function __construct(
        protected WhatsAppClient $client,
        protected WhatsAppClientFactory $clientFactory,
        protected WhatsAppChannelResolver $channelResolver,
    ) {}

    /**
     * Timestamp of the client's most recent inbound WhatsApp message across
     * all of their tickets. Inbound messages are the ones ProcessWhatsAppWebhookEvent
     * logs without a user_id; a manually logged or agent-sent interaction
     * never opens or extends the window.
     */
    public function lastCustomerMessageAt(Ticket $ticket): ?Carbon
    {
        $timestamp = Interaction::query()
            ->whereHas('ticket', fn ($query) => $query->where('client_id', $ticket->client_id))
            ->where('channel', 'whatsapp')
            ->whereNull('user_id')
            ->latest('created_at')
            ->value('created_at');

        return $timestamp ? Carbon::parse($timestamp) : null;
    }

    public function isSessionWindowOpen(Ticket $ticket): bool
    {
        $lastMessageAt = $this->lastCustomerMessageAt($ticket);

        return $lastMessageAt !== null && $lastMessageAt->gt(now()->subHours(self::SESSION_WINDOW_HOURS));
    }

    /**
     * Sends a free-text WhatsApp message to the ticket's client and logs it
     * as an Interaction. Callers must check isSessionWindowOpen() first —
     * this does not re-check it, since Meta will reject the send anyway if
     * the window is actually closed and that failure should surface as-is.
     *
     * @throws WhatsAppApiException
     */
    public function reply(Ticket $ticket, string $message, ?int $userId): Interaction
    {
        $ticket->loadMissing('client');
        $to = $ticket->client->routeNotificationForWhatsApp();

        $channel = $this->channelResolver->resolveForTenant($ticket->tenant_id, 'General');
        $whatsAppClient = $channel ? $this->clientFactory->forChannel($channel) : $this->client;

        $whatsAppClient->sendText($to, $message);

        return Interaction::create([
            'tenant_id' => $ticket->tenant_id,
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'channel' => 'whatsapp',
            'message' => $message,
        ]);
    }
}
