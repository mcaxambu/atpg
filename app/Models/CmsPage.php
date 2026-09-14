<?php

namespace App\Models;

use App\Models\Concerns\RendersMarkdown;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CmsPage extends Model
{
    use HasFactory;
    use RendersMarkdown;

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
        return $this->excerpt ?: $this->plainFromMarkdown($this->body, 160);
    }

    /** Corpo da pagina em HTML, a partir do Markdown gravado pelo editor. */
    public function getRenderedBodyAttribute(): string
    {
        return $this->renderMarkdown($this->body);
    }
}
