<?php

namespace App\Models;

use App\Models\Concerns\RendersRichText;
use App\Support\SafeHtml;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MeetingMinute extends Model
{
    use HasFactory;
    use RendersRichText;
    use SoftDeletes;

    /** Disco privado: o PDF nunca fica alcancavel por URL direta. */
    public const DISK = 'local';

    public const DIRECTORY = 'atas';

    protected $fillable = [
        'title',
        'meeting_date',
        'summary',
        'body',
        'file_path',
        'file_name',
        'file_size',
        'is_published',
        'uploaded_by',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'is_published' => 'boolean',
        'file_size' => 'integer',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        // A busca alcanca o corpo em Markdown: e o ganho pratico de ter o
        // texto no banco em vez de so um PDF opaco.
        return $query->where(fn (Builder $inner) => $inner
            ->where('title', 'like', "%{$term}%")
            ->orWhere('summary', 'like', "%{$term}%")
            ->orWhere('body', 'like', "%{$term}%"));
    }

    public function hasFile(): bool
    {
        return filled($this->file_path);
    }

    public function hasBody(): bool
    {
        return filled($this->body);
    }

    /**
     * Markdown convertido para HTML.
     *
     * HTML cru vem escapado e link inseguro e bloqueado: mesmo sendo conteudo
     * de administrador, uma conta comprometida nao deve virar XSS armazenado
     * para todos os associados que abrirem a ata.
     */
    public function getRenderedBodyAttribute(): string
    {
        if (! $this->hasBody()) {
            return '';
        }

        // Ata escrita no editor novo ja vem em HTML; o caminho de Markdown
        // continua para a ata antiga e para a importada do Notion.
        //
        // Opcoes proprias de proposito nesse caminho: a ata NAO usa soft_break
        // como <br>, e trocar isso mudaria a diagramacao das atas ja
        // publicadas. Só o tratamento de link externo e compartilhado.
        $html = self::pareceHtml($this->body)
            ? SafeHtml::limpar($this->body)
            : Str::markdown($this->body, [
                'html_input' => 'escape',
                'allow_unsafe_links' => false,
            ]);

        return $this->abrirLinksExternosEmNovaAba($html);
    }

    /**
     * Ordem de leitura natural para ata: a mais recente primeiro.
     */
    public function scopeRecentFirst(Builder $query): Builder
    {
        return $query->orderByDesc('meeting_date')->orderByDesc('id');
    }

    public function getFileSizeForHumansAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes <= 0) {
            return '—';
        }

        return $bytes >= 1048576
            ? number_format($bytes / 1048576, 1, ',', '.').' MB'
            : number_format($bytes / 1024, 0, ',', '.').' KB';
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
