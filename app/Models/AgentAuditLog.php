<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id', 'client_id', 'telefono', 'canal', 'mensaje', 'tipo_intencion',
    'confianza', 'tool_calls', 'fuente', 'requiere_seguimiento_humano', 'error',
])]
class AgentAuditLog extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tool_calls' => 'array',
            'confianza' => 'decimal:2',
            'requiere_seguimiento_humano' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
