<?php

namespace App\Models;

use App\Models\Concerns\RendersRichText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmsPage extends Model
{
    use HasFactory;
    use RendersRichText;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'hero_image',
        'is_published',
        'show_in_menu',
        'position',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'show_in_menu' => 'boolean',
        'position' => 'integer',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function getExcerptTextAttribute(): string
    {
        return $this->excerpt ?: $this->plainFromRichText($this->body, 160);
    }

    /** Corpo da pagina em HTML, a partir do Markdown gravado pelo editor. */
    public function getRenderedBodyAttribute(): string
    {
        return $this->renderRichText($this->body);
    }
    /**
     * Campo escrito no editor do painel: o HTML e limpo na gravacao.
     *
     * Fica no model, e nao no FormRequest, porque este campo e gravado
     * por mais de um caminho (painel da diretoria, painel da empresa,
     * painel do membro, importacao e seeder) — a trava tem de morar onde
     * todos passam. Ver App\Models\Concerns\RendersRichText.
     */
    public function setBodyAttribute(?string $valor): void
    {
        $this->attributes['body'] = $this->limparHtmlRico($valor);
    }
}
