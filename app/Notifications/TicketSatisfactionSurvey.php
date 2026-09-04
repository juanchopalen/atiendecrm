<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Notifications\Concerns\RetriesTransientFailures;
use App\Notifications\Messages\WhatsAppTemplateMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TicketSatisfactionSurvey extends Notification implements ShouldQueue
{
    use Queueable, RetriesTransientFailures;

    public function __construct(public Ticket $ticket) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['whatsapp'];
    }

    public function toWhatsApp(object $notifiable): WhatsAppTemplateMessage
    {
        return WhatsAppTemplateMessage::create('ticket_satisfaction_survey')
            ->event('ticket.closed')
            ->parameters([
                $notifiable->name,
                $this->ticket->tenant->name,
                (string) $this->ticket->id,
            ]);
    }
}
