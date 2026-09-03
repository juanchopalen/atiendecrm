<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['tenant_id', 'name', 'national_id', 'phone', 'email', 'address'])]
class Client extends Model implements HasMedia
{
    use BelongsToTenant, HasFactory, InteractsWithMedia, Notifiable;

    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function routeNotificationForWhatsApp(): ?string
    {
        if (blank($this->phone)) {
            return null;
        }

        return preg_replace('/[^0-9]/', '', $this->phone);
    }
}
