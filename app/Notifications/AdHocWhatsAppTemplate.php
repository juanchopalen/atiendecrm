<?php

namespace App\Notifications;

use App\Notifications\Messages\WhatsAppTemplateMessage;
use Illuminate\Notifications\Notification;

/**
 * Sends an arbitrary WhatsApp template with caller-supplied parameters,
 * without needing a dedicated Notification class per template. Used by
 * `php artisan whatsapp:test-notification` to exercise the real send path
 * (channel resolution, shared-number fallback, WhatsappNotification
 * recording) against any approved template.
 */
class AdHocWhatsAppTemplate extends Notification
{
    /**
     * @param  array<int, string>  $parameters
     */
    public function __construct(
        protected string $template,
        protected string $language,
        protected array $parameters,
        protected string $department,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['whatsapp'];
    }

    public function toWhatsApp(object $notifiable): WhatsAppTemplateMessage
    {
        return WhatsAppTemplateMessage::create($this->template)
            ->event('test.ad_hoc')
            ->language($this->language)
            ->department($this->department)
            ->parameters($this->parameters);
    }
}
