<?php

namespace App\Models;

use App\Enums\ModerationStatus;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\Moderatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory;
    use HasUniqueSlug;
    use Moderatable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'legal_name',
        'cnpj',
        'slug',
        'initials',
        'segment',
        'city',
        'state',
        'zip_code',
        'address',
        'address_number',
        'neighborhood',
        'description',
        'contact_name',
        'contact_role',
        'email',
        'whatsapp',
        'site_url',
        'is_active',
        'status',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by',
        'registration_source',
        'logo_path',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'status' => ModerationStatus::class,
        'reviewed_at' => 'datetime',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /**
     * Responsavel com acesso ao painel da empresa.
     */
    public function accessUser(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Busca livre usada nas listagens do painel e no diretorio publico.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($term) {
            foreach (['name', 'legal_name', 'segment', 'city', 'cnpj', 'email', 'contact_name'] as $column) {
                $inner->orWhere($column, 'like', "%{$term}%");
            }
        });
    }

    public function getDisplayInitialsAttribute(): string
    {
        if ($this->initials) {
            return $this->initials;
        }

        return str($this->name)->explode(' ')->filter()->take(2)
            ->map(fn (string $part) => str($part)->substr(0, 1)->upper()->toString())
            ->implode('');
    }
}
