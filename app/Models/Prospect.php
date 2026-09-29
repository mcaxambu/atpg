<?php

namespace App\Models;

use App\Enums\InteractionType;
use App\Enums\ProspectSource;
use App\Enums\ProspectStage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Empresa ou pessoa que a associacao esta convidando para entrar.
 *
 * O prospecto nasce fora do portal — numa indicacao, num evento — e segue ate
 * virar cadastro. Nao se apaga ao virar associado: a historia de como a empresa
 * chegou e o que foi combinado continua valendo depois da entrada.
 */
class Prospect extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const KIND_COMPANY = 'company';
    public const KIND_PERSON = 'person';

    protected $fillable = [
        'name', 'kind', 'contact_name', 'email', 'whatsapp', 'city', 'segment',
        'site_url', 'stage', 'source', 'owner_user_id', 'created_by',
        'next_action', 'next_action_at', 'notes', 'company_id', 'member_id',
        'lost_reason', 'stage_changed_at',
    ];

    protected $casts = [
        'stage' => ProspectStage::class,
        'source' => ProspectSource::class,
        'next_action_at' => 'date',
        'stage_changed_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(ProspectInteraction::class)->latest('happened_at');
    }

    public function isCompany(): bool
    {
        return $this->kind === self::KIND_COMPANY;
    }

    public function kindLabel(): string
    {
        return $this->isCompany() ? 'Empresa' : 'Pessoa';
    }

    /** O cadastro que nasceu deste prospecto, quando ja existe. */
    public function cadastro(): Company|Member|null
    {
        return $this->company ?? $this->member;
    }

    // ----- Cobranca -----

    /**
     * Passou da data do proximo passo.
     *
     * E o sinal que faz o funil valer alguma coisa: sem ele, o cartao fica
     * parado numa coluna e ninguem percebe.
     */
    public function atrasado(): bool
    {
        return $this->stage->emAndamento()
            && $this->next_action_at !== null
            && $this->next_action_at->isPast();
    }

    /** Em andamento e sem nenhum contato ha muito tempo. */
    public function parado(int $dias = 21): bool
    {
        if (! $this->stage->emAndamento()) {
            return false;
        }

        $ultimo = $this->stage_changed_at ?? $this->created_at;

        return $ultimo !== null && $ultimo->diffInDays(now()) >= $dias;
    }

    public function diasParado(): int
    {
        $ultimo = $this->stage_changed_at ?? $this->created_at;

        return $ultimo ? (int) $ultimo->diffInDays(now()) : 0;
    }

    // ----- Consultas -----

    public function scopeEmAndamento(Builder $query): Builder
    {
        return $query->whereIn('stage', array_map(
            fn (ProspectStage $e) => $e->value,
            ProspectStage::funil()
        ));
    }

    public function scopeNaEtapa(Builder $query, ProspectStage $etapa): Builder
    {
        return $query->where('stage', $etapa->value);
    }

    public function scopeAtrasados(Builder $query): Builder
    {
        return $query->emAndamento()
            ->whereNotNull('next_action_at')
            ->whereDate('next_action_at', '<', now()->toDateString());
    }

    /**
     * Ordem do funil: quem esta atrasado primeiro, depois pelo proximo passo
     * mais proximo. Cartao sem data vai para o fim — nao e urgente, mas
     * tambem nao pode sumir.
     */
    public function scopeOrdemDeTrabalho(Builder $query): Builder
    {
        return $query->orderByRaw('next_action_at IS NULL')
            ->orderBy('next_action_at')
            ->orderByDesc('id');
    }

    // ----- Mudanca de etapa -----

    /**
     * Move de etapa e escreve no historico.
     *
     * O registro automatico existe para o historico contar a conversa inteira:
     * quem le a ficha seis meses depois precisa ver quando o convite saiu, sem
     * depender de alguem ter anotado isso a mao.
     */
    public function moverPara(ProspectStage $etapa, ?User $autor = null, ?string $motivo = null): void
    {
        if ($this->stage === $etapa) {
            return;
        }

        $anterior = $this->stage;

        $this->forceFill([
            'stage' => $etapa,
            'stage_changed_at' => now(),
            'lost_reason' => $etapa === ProspectStage::Perdido ? $motivo : null,
        ])->save();

        $texto = "Etapa: {$anterior->label()} → {$etapa->label()}";

        if ($etapa === ProspectStage::Perdido && filled($motivo)) {
            $texto .= ". Motivo: {$motivo}";
        }

        $this->interactions()->create([
            'user_id' => $autor?->id,
            'type' => InteractionType::Sistema,
            'summary' => $texto,
            'happened_at' => now(),
        ]);
    }
}
