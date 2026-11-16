<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * Uma indústria da região — cliente em potencial do grupo todo.
 */
class Industry extends Model
{
    use HasFactory;
    use HasUniqueSlug;
    use SoftDeletes;

    protected $table = 'industries';

    protected $fillable = [
        'name',
        'slug',
        'legal_name',
        'cnpj',
        'sector',
        'size',
        'employees',
        'city',
        'state',
        'neighborhood',
        'address',
        'site_url',
        'description',
        'contact_name',
        'contact_role',
        'contact_email',
        'contact_phone',
        'notes',
        'last_contact_at',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_contact_at' => 'date',
        'employees' => 'integer',
    ];

    /** Portes, do jeito que a diretoria fala — não é classificação oficial. */
    public const PORTES = [
        'pequena' => 'Pequena',
        'media' => 'Média',
        'grande' => 'Grande',
    ];

    /**
     * O que esta indústria precisa.
     */
    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class);
    }

    public function criador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function porteLabel(): ?string
    {
        return self::PORTES[$this->size] ?? null;
    }

    /**
     * As associadas que sabem fazer o que esta indústria precisa.
     *
     * A ordem é pelo TAMANHO do encaixe: quem atende quatro das cinco
     * necessidades aparece antes de quem atende uma. Ordem alfabética aqui
     * esconderia justamente a resposta que a tela existe para dar.
     *
     * @return Collection<int, Company>
     */
    public function quemAtende(): Collection
    {
        $precisa = $this->specialties->pluck('id');

        if ($precisa->isEmpty()) {
            return collect();
        }

        return Company::query()
            ->visible()
            ->whereHas('specialties', fn (Builder $q) => $q->whereIn('specialties.id', $precisa))
            ->with(['specialties' => fn ($q) => $q->whereIn('specialties.id', $precisa)])
            ->get()
            ->sortByDesc(fn (Company $empresa) => $empresa->specialties->count())
            ->values();
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $termo): Builder
    {
        $termo = trim((string) $termo);

        if ($termo === '') {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($termo) {
            $inner->where('name', 'like', "%{$termo}%")
                ->orWhere('legal_name', 'like', "%{$termo}%")
                ->orWhere('sector', 'like', "%{$termo}%")
                ->orWhere('neighborhood', 'like', "%{$termo}%");
        });
    }

    /**
     * Só as indústrias que precisam de uma especialidade.
     */
    public function scopePrecisandoDe(Builder $query, ?int $specialtyId): Builder
    {
        return $specialtyId
            ? $query->whereHas('specialties', fn (Builder $q) => $q->where('specialties.id', $specialtyId))
            : $query;
    }
}
