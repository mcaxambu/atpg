<?php

namespace App\Models;

use App\Enums\JobType;
use App\Enums\JobWorkplace;
use App\Enums\ModerationStatus;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\Moderatable;
use App\Models\Concerns\RendersRichText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobOpening extends Model
{
    use HasFactory;
    use HasUniqueSlug;
    use Moderatable;
    use RendersRichText;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'title',
        'slug',
        'type',
        'workplace',
        'seniority',
        'description',
        'requirements',
        'benefits',
        'city',
        'state',
        'salary_min',
        'salary_max',
        'show_salary',
        'closes_at',
        'is_active',
        'status',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'type' => JobType::class,
        'workplace' => JobWorkplace::class,
        'show_salary' => 'boolean',
        'is_active' => 'boolean',
        'status' => ModerationStatus::class,
        'closes_at' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * Vaga com prazo vencido some do portal sozinha, sem ninguem encerrar.
     * `closes_at` nulo significa sem prazo.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where(function (Builder $inner) {
            $inner->whereNull('closes_at')->orWhereDate('closes_at', '>=', now()->toDateString());
        });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('closes_at')->whereDate('closes_at', '<', now()->toDateString());
    }

    /**
     * O que realmente aparece no portal: aprovada, publicada, no prazo e de
     * uma empresa que tambem esta visivel.
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->visible()->open()
            ->whereHas('company', fn (Builder $company) => $company->visible());
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($term) {
            $inner->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('city', 'like', "%{$term}%")
                ->orWhereHas('company', fn (Builder $company) => $company->where('name', 'like', "%{$term}%"));
        });
    }

    public function isExpired(): bool
    {
        return $this->closes_at !== null && $this->closes_at->isBefore(now()->startOfDay());
    }

    public function isPubliclyVisible(): bool
    {
        return $this->isVisible()
            && ! $this->isExpired()
            && (bool) $this->company?->isVisible();
    }

    /**
     * Rotulo curto da situacao para o painel da empresa, que precisa
     * distinguir "esperando a associacao" de "no ar" e de "prazo vencido".
     */
    public function situationLabel(): string
    {
        return match (true) {
            $this->isPending() => 'Em análise',
            $this->isRejected() => 'Rejeitada',
            $this->isExpired() => 'Prazo encerrado',
            ! $this->is_active => 'Encerrada',
            default => 'No ar',
        };
    }

    public function salaryLabel(): ?string
    {
        if (! $this->show_salary || (! $this->salary_min && ! $this->salary_max)) {
            return null;
        }

        $money = fn (int $value) => 'R$ '.number_format($value, 0, ',', '.');

        return match (true) {
            $this->salary_min && $this->salary_max => $money($this->salary_min).' a '.$money($this->salary_max),
            (bool) $this->salary_min => 'A partir de '.$money($this->salary_min),
            default => 'Até '.$money($this->salary_max),
        };
    }

    /**
     * O cascade do banco apagaria as linhas de candidatura sem passar pelo
     * model — e os curriculos ficariam orfaos no disco. Apagamos pelo Eloquent
     * antes, para que cada candidatura remova o proprio arquivo.
     */
    protected static function booted(): void
    {
        static::forceDeleting(function (self $job) {
            $job->applications()->cursor()->each->delete();
        });
    }

    public function getRenderedDescriptionAttribute(): string
    {
        return $this->renderRichText($this->description);
    }

    /** Sem formatacao, para o cartao da listagem de vagas. */
    public function getDescriptionExcerptAttribute(): string
    {
        return $this->plainFromRichText($this->description, 180);
    }

    public function locationLabel(): string
    {
        if ($this->workplace === JobWorkplace::Remote) {
            return 'Remoto';
        }

        $local = collect([$this->city, $this->state])->filter()->join(' - ');

        return $local !== '' ? $local : $this->workplace->label();
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
