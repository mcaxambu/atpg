<?php

namespace App\Models;

use App\Enums\ModerationStatus;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\Moderatable;
use App\Models\Concerns\RendersMarkdown;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory;
    use HasUniqueSlug;
    use Moderatable;
    use RendersMarkdown;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'avatar_initials',
        'avatar_color',
        'photo_path',
        'role',
        'city',
        'experience_years',
        'summary',
        'site_url',
        'linkedin_url',
        'instagram_url',
        'whatsapp',
        'email',
        'is_featured',
        'is_active',
        'status',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by',
        'registration_source',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'status' => ModerationStatus::class,
        'reviewed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(MemberExperience::class)->orderBy('position');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(MemberProject::class)->orderBy('position');
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(MemberCertification::class)->orderBy('position');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($term) {
            foreach (['name', 'role', 'city', 'email', 'summary'] as $column) {
                $inner->orWhere($column, 'like', "%{$term}%");
            }

            $inner->orWhereHas('company', fn (Builder $company) => $company->where('name', 'like', "%{$term}%"));
        });
    }

    /**
     * Membro so aparece no portal se ele e a empresa dele estiverem publicados.
     * Profissional independente (sem empresa) tambem e valido.
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->visible()->where(function (Builder $inner) {
            $inner->whereNull('company_id')
                ->orWhereHas('company', fn (Builder $company) => $company->visible());
        });
    }

    /** Perfil de colunista, quando este membro assina coluna no portal. */
    public function columnist(): HasOne
    {
        return $this->hasOne(Columnist::class);
    }

    public function getRenderedSummaryAttribute(): string
    {
        return $this->renderMarkdown($this->summary);
    }

    /** Sem formatacao, para o cartao do diretorio. */
    public function getSummaryExcerptAttribute(): string
    {
        return $this->plainFromMarkdown($this->summary, 180);
    }

    public function isPubliclyVisible(): bool
    {
        return $this->isVisible() && (! $this->company_id || (bool) $this->company?->isVisible());
    }
}
