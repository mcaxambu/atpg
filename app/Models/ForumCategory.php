<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Assunto do forum. Quem cria e a diretoria.
 *
 * Categoria desativada nao some: os topicos continuam existindo e legiveis,
 * so nao se abre assunto novo ali. Apagar a categoria levaria junto a conversa
 * dos associados, que nao e da diretoria para descartar.
 */
class ForumCategory extends Model
{
    use HasFactory;
    use HasUniqueSlug;

    protected $fillable = ['name', 'slug', 'description', 'icon', 'position', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    public function topics(): HasMany
    {
        return $this->hasMany(ForumTopic::class);
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeEmOrdem(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('name');
    }
}
