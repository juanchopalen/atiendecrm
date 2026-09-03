<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'whatsapp_business_account_id', 'tenant_id', 'phone_number_id', 'numero_visible',
    'departamento', 'modo', 'estado', 'calidad', 'solo_demo',
])]
class WhatsappChannel extends Model
{
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'solo_demo' => 'boolean',
        ];
    }

    public function whatsappBusinessAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsappBusinessAccount::class);
    }

    public function inbox(): HasOne
    {
        return $this->hasOne(Inbox::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(WhatsappNotification::class);
    }
}
