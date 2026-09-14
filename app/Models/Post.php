<?php

namespace App\Models;

use App\Enums\ModerationStatus;
use App\Models\Concerns\Moderatable;
use App\Models\Concerns\RendersMarkdown;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;
    use Moderatable;
    use RendersMarkdown;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'category',
        'columnist_id',
        'source_url',
        'source_name',
        'cover_image',
        'is_published',
        'is_featured',
        'published_at',
        'status',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'status' => ModerationStatus::class,
        'reviewed_at' => 'datetime',
    ];

    public function columnist(): BelongsTo
    {
        return $this->belongsTo(Columnist::class);
    }

    /**
     * Coluna e a noticia que tem autor. Nao ha campo separado para isso: a
     * presenca do colunista JA e a distincao, e um flag a mais so criaria a
     * chance de os dois discordarem.
     */
    public function isColumn(): bool
    {
        return $this->columnist_id !== null;
    }

    public function scopeColumns(Builder $query): Builder
    {
        return $query->whereNotNull('columnist_id');
    }

    /**
     * O trait de moderacao liga/desliga `is_active`, que existe em empresa e
     * membro mas NAO em noticia — aqui quem controla a publicacao e
     * `is_published` + `published_at`. Sem estas duas sobrescritas, aprovar ou
     * devolver uma coluna estourava "no such column: is_active".
     */
    public function approve(?User $reviewer = null): void
    {
        $this->forceFill([
            'status' => ModerationStatus::Approved,
            'is_published' => true,
            'published_at' => $this->published_at ?? now(),
            'rejection_reason' => null,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer?->id,
        ])->save();
    }

    public function reject(?string $reason = null, ?User $reviewer = null): void
    {
        $this->forceFill([
            'status' => ModerationStatus::Rejected,
            'is_published' => false,
            'published_at' => null,
            'rejection_reason' => $reason,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer?->id,
        ])->save();
    }

    /**
     * O que o portal pode listar.
     *
     * Alem de publicada, uma COLUNA precisa de um colunista no ar: membro
     * despublicado nao pode continuar assinando na vitrine, e a assinatura
     * apontaria para uma pagina inexistente. Noticia sem autor nao e afetada.
     */
    public function scopeVisibleToPublic(Builder $query): Builder
    {
        return $query->published()->where(function (Builder $inner) {
            $inner->whereNull('columnist_id')
                ->orWhereHas('columnist', fn (Builder $c) => $c->publiclyVisible());
        });
    }

    /** Mesma regra do escopo, para a checagem da pagina individual. */
    public function isVisibleToPublic(): bool
    {
        $publicada = $this->isApproved()
            && $this->is_published
            && $this->published_at
            && $this->published_at->lte(now());

        if (! $publicada) {
            return false;
        }

        return ! $this->isColumn() || (bool) $this->columnist?->isPubliclyVisible();
    }

    public function scopeNewsOnly(Builder $query): Builder
    {
        return $query->whereNull('columnist_id');
    }

    /**
     * Ordem da listagem publica: o destaque marcado vem primeiro, e o resto
     * segue por data. Concentrado num escopo para que a home, o indice e as
     * relacionadas nao divirjam entre si.
     */
    public function scopeInFeedOrder(Builder $query): Builder
    {
        return $query->orderByDesc('is_featured')->latest('published_at');
    }

    /**
     * O que o portal mostra.
     *
     * A aprovacao entrou junto com as colunas: texto escrito por colunista
     * espera a diretoria. Noticia da propria diretoria nasce aprovada, entao
     * para ela nada mudou.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', ModerationStatus::Approved)
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
        // A conversao mora no trait, junto com a das paginas, vagas e perfis:
        // eram tres copias das mesmas opcoes, e o tratamento de link externo
        // acabou nascendo so numa delas.
        return $this->renderMarkdown($this->body);
    }
}
