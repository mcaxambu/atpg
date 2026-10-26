<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\RendersRichText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Assunto aberto por um associado.
 *
 * O texto passa pela mesma limpeza do resto do portal (RendersRichText): aqui
 * o conteudo e escrito por dezenas de pessoas, e nao so pela diretoria, entao
 * a lista de permissao do HTML importa ainda mais.
 */
class ForumTopic extends Model
{
    use HasFactory;
    use HasUniqueSlug;
    use RendersRichText;
    use SoftDeletes;

    protected $fillable = [
        'forum_category_id', 'user_id', 'title', 'slug', 'body',
        'is_pinned', 'is_closed', 'is_hidden', 'replies_count', 'last_reply_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_closed' => 'boolean',
        'is_hidden' => 'boolean',
        'last_reply_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'forum_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ForumReply::class)->oldest();
    }

    public function getRenderedBodyAttribute(): string
    {
        return $this->renderRichText($this->body);
    }

    public function getExcerptAttribute(): string
    {
        return $this->plainFromRichText($this->body, 160);
    }

    public function setBodyAttribute(?string $valor): void
    {
        $this->attributes['body'] = $this->limparHtmlRico($valor);
    }

    /** Topicos que os associados enxergam. */
    public function scopeVisiveis(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Ordem da lista: fixados primeiro, depois a conversa mais recente.
     *
     * `last_reply_at` cai para `created_at` em topico sem resposta — senao o
     * assunto recem-aberto, que e justamente o que precisa de gente, afundaria
     * na lista.
     */
    public function scopePorAtividade(Builder $query): Builder
    {
        return $query->orderByDesc('is_pinned')
            ->orderByRaw('COALESCE(last_reply_at, created_at) DESC');
    }

    public function ultimaAtividade(): ?\Illuminate\Support\Carbon
    {
        return $this->last_reply_at ?? $this->created_at;
    }

    public function podeSerEditadoPor(?User $user): bool
    {
        return $user !== null && $this->user_id === $user->id && ! $this->is_closed;
    }

    /**
     * Quem acompanha o topico: quem abriu e quem ja respondeu.
     *
     * E a lista de quem recebe aviso de resposta nova. Nao existe "seguir"
     * separado de proposito: participar JA e demonstrar interesse, e mais um
     * botao para marcar seria mais uma coisa que ninguem lembra de usar.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function participantes(): \Illuminate\Support\Collection
    {
        return User::query()
            ->whereIn('id', $this->replies()->pluck('user_id')->push($this->user_id)->filter()->unique())
            ->whereNotNull('email')
            ->get();
    }
}
