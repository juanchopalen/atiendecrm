<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Models\WhatsappNotification;
use App\Notifications\TicketSatisfactionSurvey;

class TicketObserver
{
    /**
     * Minimum gap between satisfaction surveys sent to the same client, so
     * closing several tickets in quick succession doesn't read as robotic.
     */
    protected const SURVEY_THROTTLE_HOURS = 48;

    public function updated(Ticket $ticket): void
    {
        if (! $ticket->isDirty('status') || $ticket->status !== 'closed') {
            return;
        }

        $client = $ticket->client;

        $surveyedRecently = WhatsappNotification::query()
            ->where('notifiable_type', $client->getMorphClass())
            ->where('notifiable_id', $client->getKey())
            ->where('event', 'ticket.closed')
            ->where('created_at', '>=', now()->subHours(self::SURVEY_THROTTLE_HOURS))
            ->exists();

        if ($surveyedRecently) {
            return;
        }

        $client->notify(new TicketSatisfactionSurvey($ticket));
    }
}
