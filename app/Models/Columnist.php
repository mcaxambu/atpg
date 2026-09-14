<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\RendersMarkdown;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Perfil publico de quem assina coluna no portal.
 *
 * Aponta para um membro OU para uma empresa — nunca os dois. Nao e um usuario:
 * o acesso continua sendo o do cadastro de origem, entao o colunista escreve
 * pelo painel que ja usa.
 *
 * Nome e apresentacao caem para os dados do cadastro quando ficam em branco.
 * Isso e proposital: dois campos de nome para a mesma pessoa divergem assim que
 * alguem corrige so um deles.
 */
class Columnist extends Model
{
    use HasFactory;
    use HasUniqueSlug;
    use RendersMarkdown;

    protected $fillable = [
        'member_id',
        'company_id',
        'name',
        'slug',
        'headline',
        'bio',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /** O cadastro de origem: membro ou empresa. */
    public function origem(): Member|Company|null
    {
        return $this->member ?? $this->company;
    }

    public function isCompany(): bool
    {
        return $this->company_id !== null;
    }

    /** Nome de assinatura. Em branco, usa o nome do cadastro. */
    public function getDisplayNameAttribute(): string
    {
        return filled($this->name) ? $this->name : (string) $this->origem()?->name;
    }

    /**
     * Credito da pessoa quando quem assina e uma empresa.
     *
     * A empresa assina com o nome dela; `name` serve para creditar quem
     * escreveu de fato ("Joao Silva — Empresa X").
     */
    public function getBylineAttribute(): string
    {
        if ($this->isCompany() && filled($this->name)) {
            return "{$this->name} — {$this->company->name}";
        }

        return $this->display_name;
    }

    public function getPhotoPathAttribute(): ?string
    {
        return $this->member?->photo_path ?? $this->company?->logo_path;
    }

    public function getInitialsAttribute(): string
    {
        return (string) ($this->member?->avatar_initials ?? $this->company?->display_initials ?? '?');
    }

    /** Apresentacao. Em branco, usa o resumo do membro ou a descricao da empresa. */
    public function getPresentationAttribute(): string
    {
        return filled($this->bio)
            ? $this->bio
            : (string) ($this->member?->summary ?? $this->company?->description);
    }

    public function getRenderedPresentationAttribute(): string
    {
        return $this->renderMarkdown($this->presentation);
    }

    /**
     * Colunista que o portal mostra: ativo e com o cadastro de origem visivel.
     * Membro despublicado nao pode continuar assinando na vitrine.
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $inner) {
                $inner->whereHas('member', fn (Builder $m) => $m->visible())
                    ->orWhereHas('company', fn (Builder $c) => $c->visible());
            });
    }

    public function isPubliclyVisible(): bool
    {
        return $this->is_active && (bool) $this->origem()?->isVisible();
    }
}
