<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Notifications\TicketSatisfactionSurvey;

class TicketObserver
{
    public function updated(Ticket $ticket): void
    {
        if ($ticket->isDirty('status') && $ticket->status === 'closed') {
            $ticket->client->notify(new TicketSatisfactionSurvey($ticket));
        }
    }
}
