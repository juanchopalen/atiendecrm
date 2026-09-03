<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tenant_id', 'categoria', 'titulo', 'contenido', 'tipo'])]
class KnowledgeDocument extends Model
{
    use BelongsToTenant, HasFactory;
}
