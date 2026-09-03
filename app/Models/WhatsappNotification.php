<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'tenant_id', 'whatsapp_channel_id', 'notifiable_type', 'notifiable_id', 'event', 'template', 'language',
    'to', 'variables', 'wamid', 'status', 'error_code', 'error_message',
    'sent_at', 'delivered_at', 'read_at',
])]
class WhatsappNotification extends Model
{
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function whatsappChannel(): BelongsTo
    {
        return $this->belongsTo(WhatsappChannel::class);
    }
}
