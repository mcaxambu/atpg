<?php

namespace App\Models;

use App\Enums\InteractionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma linha do historico do prospecto: o que foi conversado e quando.
 *
 * Nao tem edicao nem exclusao de proposito. Historico que se reescreve nao
 * serve para lembrar o que foi combinado — e a diretoria muda de gente.
 */
class ProspectInteraction extends Model
{
    use HasFactory;

    protected $fillable = ['prospect_id', 'user_id', 'type', 'summary', 'happened_at'];

    protected $casts = [
        'type' => InteractionType::class,
        'happened_at' => 'datetime',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSistema(): bool
    {
        return $this->type === InteractionType::Sistema;
    }
}
