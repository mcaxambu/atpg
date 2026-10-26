<?php

namespace App\Models;

use App\Models\Concerns\RendersRichText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Resposta a um tópico do fórum.
 */
class ForumReply extends Model
{
    use HasFactory;
    use RendersRichText;
    use SoftDeletes;

    protected $fillable = ['forum_topic_id', 'user_id', 'body', 'is_hidden'];

    protected $casts = ['is_hidden' => 'boolean'];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(ForumTopic::class, 'forum_topic_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getRenderedBodyAttribute(): string
    {
        return $this->renderRichText($this->body);
    }

    public function setBodyAttribute(?string $valor): void
    {
        $this->attributes['body'] = $this->limparHtmlRico($valor);
    }

    public function scopeVisiveis(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    public function podeSerEditadaPor(?User $user): bool
    {
        return $user !== null && $this->user_id === $user->id && ! $this->topic->is_closed;
    }
}
