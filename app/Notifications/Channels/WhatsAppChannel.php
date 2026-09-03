<?php

namespace App\Notifications\Channels;

use App\Exceptions\WhatsAppApiException;
use App\Models\WhatsappNotification;
use App\Notifications\Messages\WhatsAppTemplateMessage;
use App\Services\WhatsApp\WhatsAppChannelResolver;
use App\Services\WhatsApp\WhatsAppClient;
use App\Services\WhatsApp\WhatsAppClientFactory;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    public function __construct(
        protected WhatsAppClient $client,
        protected WhatsAppClientFactory $clientFactory,
        protected WhatsAppChannelResolver $channelResolver,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $to = $notifiable->routeNotificationFor('whatsapp', $notification);

        if (blank($to)) {
            return;
        }

        /** @var WhatsAppTemplateMessage $message */
        $message = $notification->toWhatsApp($notifiable);

        $channel = $notifiable->tenant_id
            ? $this->channelResolver->resolveForTenant($notifiable->tenant_id, $message->getDepartment())
            : null;

        $client = $channel ? $this->clientFactory->forChannel($channel) : $this->client;

        $record = WhatsappNotification::create([
            'tenant_id' => $notifiable->tenant_id,
            'whatsapp_channel_id' => $channel?->id,
            'notifiable_type' => $notifiable->getMorphClass(),
            'notifiable_id' => $notifiable->getKey(),
            'event' => $message->getEvent(),
            'template' => $message->getTemplate(),
            'language' => $message->getLanguage(),
            'to' => $to,
            'variables' => $message->getParameters(),
            'status' => 'queued',
        ]);

        try {
            $wamid = $client->sendTemplate(
                $to,
                $message->getTemplate(),
                $message->getLanguage(),
                $message->getParameters(),
            );

            $record->update([
                'wamid' => $wamid,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (WhatsAppApiException $e) {
            $record->update([
                'status' => 'failed',
                'error_code' => $e->apiErrorCode,
                'error_message' => $e->getMessage(),
            ]);

            Log::error('WhatsApp template send failed', [
                'whatsapp_notification_id' => $record->id,
                'template' => $message->getTemplate(),
                'error_code' => $e->apiErrorCode,
                'retryable' => $e->retryable,
            ]);

            if ($e->retryable) {
                throw $e;
            }
        }
    }
}
