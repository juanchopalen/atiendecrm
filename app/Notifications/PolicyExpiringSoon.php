<?php

namespace App\Notifications;

use App\Models\Policy;
use App\Notifications\Concerns\RetriesTransientFailures;
use App\Notifications\Messages\WhatsAppTemplateMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PolicyExpiringSoon extends Notification implements ShouldQueue
{
    use Queueable, RetriesTransientFailures;

    public function __construct(
        public Policy $policy,
        public int $daysUntilExpiration,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'whatsapp'];
    }

    public function toWhatsApp(object $notifiable): WhatsAppTemplateMessage
    {
        $policy = $this->policy;

        return WhatsAppTemplateMessage::create('policy_expiration_reminder')
            ->event('policy.expiring_soon')
            ->department('Cobranzas')
            ->parameters([
                $notifiable->name,
                $policy->tenant->name,
                $policy->policy_number,
                $policy->expiration_date->translatedFormat('d/m/Y'),
            ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $policy = $this->policy;

        return (new MailMessage)
            ->subject(__('notifications.policy_expiring_soon.subject', ['policy_number' => $policy->policy_number]))
            ->greeting(__('notifications.policy_expiring_soon.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.policy_expiring_soon.line_1', [
                'policy_number' => $policy->policy_number,
                'line_of_business' => __("policies.lines_of_business.{$policy->line_of_business}"),
                'insurer' => $policy->insurer,
                'expiration_date' => $policy->expiration_date->translatedFormat('d/m/Y'),
            ]))
            ->line(__('notifications.policy_expiring_soon.line_2', ['days' => $this->daysUntilExpiration]))
            ->salutation(__('notifications.policy_expiring_soon.salutation').' '.$policy->tenant->name);
    }
}
