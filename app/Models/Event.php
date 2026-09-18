<?php

namespace App\Models;

use App\Models\Concerns\RendersRichText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;
    use RendersRichText;

    protected $fillable = [
        'title',
        'slug',
        'type',
        'description',
        'location',
        'event_date',
        'starts_at',
        'ends_at',
        'registration_url',
        'cover_image',
        'is_published',
        'is_featured',
    ];

    protected $casts = [
        'event_date' => 'date',
        'starts_at' => 'datetime:H:i',
        'ends_at' => 'datetime:H:i',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function getRenderedDescriptionAttribute(): string
    {
        return $this->renderRichText($this->description);
    }

    /** Sem formatacao, para o cartao da agenda. */
    public function getDescriptionExcerptAttribute(): string
    {
        return $this->plainFromRichText($this->description, 220);
    }
    /**
     * Campo escrito no editor do painel: o HTML e limpo na gravacao.
     *
     * Fica no model, e nao no FormRequest, porque este campo e gravado
     * por mais de um caminho (painel da diretoria, painel da empresa,
     * painel do membro, importacao e seeder) — a trava tem de morar onde
     * todos passam. Ver App\Models\Concerns\RendersRichText.
     */
    public function setDescriptionAttribute(?string $valor): void
    {
        $this->attributes['description'] = $this->limparHtmlRico($valor);
    }
}
