<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A tenant without its own whatsapp_channels falls back to the shared/common
 * WhatsApp number (this MVP's default). Its inbox has no whatsapp_channel_id
 * — it's a "virtual" inbox that groups conversations arriving on that shared
 * number for the tenant, see Inbox::virtualForTenant().
 */
#[Fillable(['whatsapp_channel_id', 'tenant_id', 'nombre_visible'])]
class Inbox extends Model
{
    use BelongsToTenant, HasFactory;

    public function whatsappChannel(): BelongsTo
    {
        return $this->belongsTo(WhatsappChannel::class);
    }

    public function agentes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'inbox_user');
    }

    public function isVirtual(): bool
    {
        return $this->whatsapp_channel_id === null;
    }

    /**
     * Finds or creates the shared-number inbox for a tenant with no
     * dedicated WhatsApp channel.
     */
    public static function virtualForTenant(int $tenantId): self
    {
        return static::query()
            ->withoutGlobalScopes()
            ->firstOrCreate(
                ['tenant_id' => $tenantId, 'whatsapp_channel_id' => null],
                ['nombre_visible' => 'Bandeja general (número compartido)'],
            );
    }
}
