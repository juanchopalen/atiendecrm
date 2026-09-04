<?php

namespace App\Notifications;

use App\Models\Client;
use App\Notifications\Concerns\RetriesTransientFailures;
use App\Notifications\Messages\WhatsAppTemplateMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ClientWelcome extends Notification implements ShouldQueue
{
    use Queueable, RetriesTransientFailures;

    public function __construct(public Client $client) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['whatsapp'];
    }

    public function toWhatsApp(object $notifiable): WhatsAppTemplateMessage
    {
        return WhatsAppTemplateMessage::create('client_welcome')
            ->event('client.created')
            ->department('Suscripción')
            ->parameters([
                $this->client->name,
                $this->client->tenant->name,
            ]);
    }
}
