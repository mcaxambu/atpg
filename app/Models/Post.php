<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'category',
        'cover_image',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function getReadingTimeAttribute(): string
    {
        $words = str_word_count(strip_tags($this->body ?? ''));
        $minutes = max(1, (int) ceil($words / 200));

        return "{$minutes} min";
    }

    public function getExcerptTextAttribute(): string
    {
        return $this->excerpt ?: Str::limit(strip_tags($this->body ?? ''), 150);
    }

    /**
     * Corpo da noticia em HTML.
     *
     * Antes isto era nl2br() puro: subtitulo, lista e destaque saiam como
     * linhas soltas separadas por <br>, sem hierarquia nenhuma. Markdown
     * resolve e e o mesmo mecanismo ja usado nas atas.
     *
     * soft_break continua como <br> de proposito — as noticias ja publicadas
     * foram escritas como texto corrido, e sem isso as quebras dentro de um
     * paragrafo desapareceriam.
     *
     * HTML cru vem escapado e link inseguro e bloqueado: mesmo sendo conteudo
     * de administrador, uma conta comprometida nao deve virar XSS armazenado
     * para todo visitante do portal.
     */
    public function getRenderedBodyAttribute(): string
    {
        if (blank($this->body)) {
            return '';
        }

        return Str::markdown($this->body, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'renderer' => ['soft_break' => "<br>\n"],
        ]);
    }
}
