<?php

namespace App\Notifications;

use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgentEscalationRequired extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public string $mensajeCliente,
        public string $motivo,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.agent_escalation.subject', ['client_name' => $this->ticket->client->name]))
            ->greeting(__('notifications.agent_escalation.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.agent_escalation.line_1', ['client_name' => $this->ticket->client->name]))
            ->line(__('notifications.agent_escalation.line_2', ['mensaje' => $this->mensajeCliente]))
            ->action(__('notifications.agent_escalation.action'), $this->ticketUrl())
            ->line(__('notifications.agent_escalation.line_3', ['motivo' => $this->motivo]));
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('notifications.agent_escalation.subject', ['client_name' => $this->ticket->client->name]))
            ->body(__('notifications.agent_escalation.line_2', ['mensaje' => $this->mensajeCliente]))
            ->icon('heroicon-o-exclamation-triangle')
            ->iconColor('danger')
            ->actions([
                Action::make('ver')
                    ->label(__('notifications.agent_escalation.action'))
                    ->url($this->ticketUrl())
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    protected function ticketUrl(): string
    {
        return TicketResource::getUrl('edit', ['record' => $this->ticket], tenant: $this->ticket->tenant);
    }
}
