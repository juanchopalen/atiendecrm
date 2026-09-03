<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tenant_id', 'waba_id', 'business_verification_status', 'access_token'])]
class WhatsappBusinessAccount extends Model
{
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function channels(): HasMany
    {
        return $this->hasMany(WhatsappChannel::class);
    }
}
