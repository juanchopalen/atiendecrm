<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['whatsapp_channel_id', 'nombre_visible'])]
class Inbox extends Model
{
    use HasFactory;

    public function whatsappChannel(): BelongsTo
    {
        return $this->belongsTo(WhatsappChannel::class);
    }

    public function agentes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'inbox_user');
    }
}
