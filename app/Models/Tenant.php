<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

#[Fillable(['name', 'slug', 'tax_id', 'is_active', 'es_demo', 'features'])]
class Tenant extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'es_demo' => 'boolean',
            'features' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function whatsappBusinessAccounts(): HasMany
    {
        return $this->hasMany(WhatsappBusinessAccount::class);
    }

    public function whatsappChannels(): HasMany
    {
        return $this->hasMany(WhatsappChannel::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function notificationDaysToPay(): ?int
    {
        $days = Arr::get($this->features ?? [], 'notifications.days_to_pay');

        return filled($days) ? (int) $days : null;
    }
}
