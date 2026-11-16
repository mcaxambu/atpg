<?php

namespace App\Models;

use App\Enums\MensalidadeStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A mensalidade de uma associada num mês.
 */
class Mensalidade extends Model
{
    use HasFactory;

    protected $table = 'mensalidades';

    protected $fillable = [
        'company_id',
        'competencia',
        'vencimento',
        'valor',
        'status',
        'pago_em',
        'forma_pagamento',
        'observacao',
        'cobranca_externa_id',
        'cobranca_url',
        'cobranca_enviada_em',
        'registrado_por',
    ];

    protected $casts = [
        'competencia' => 'date',
        'vencimento' => 'date',
        'pago_em' => 'date',
        'cobranca_enviada_em' => 'datetime',
        'valor' => 'decimal:2',
        'status' => MensalidadeStatus::class,
    ];

    public const FORMAS = [
        'pix' => 'Pix',
        'transferencia' => 'Transferência',
        'boleto' => 'Boleto',
        'dinheiro' => 'Dinheiro',
        'cartao' => 'Cartão',
        'outra' => 'Outra',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /**
     * Em aberto E passou do vencimento.
     *
     * Não é um status no banco de propósito: "vencida" é uma leitura do
     * calendário, não uma decisão de alguém. Guardar como estado exigiria
     * alguém (ou um cron) virando a chave todo dia, e o dia em que isso
     * falhasse a tela mentiria.
     */
    public function estaVencida(): bool
    {
        return $this->status === MensalidadeStatus::Pendente
            && $this->vencimento->isBefore(Carbon::today());
    }

    public function diasDeAtraso(): int
    {
        return $this->estaVencida() ? $this->vencimento->diffInDays(Carbon::today()) : 0;
    }

    public function competenciaLabel(): string
    {
        return ucfirst($this->competencia->translatedFormat('F \d\e Y'));
    }

    public function formaLabel(): ?string
    {
        return self::FORMAS[$this->forma_pagamento] ?? null;
    }

    public function scopeDaCompetencia(Builder $query, ?string $competencia): Builder
    {
        return $competencia
            ? $query->whereDate('competencia', Carbon::parse($competencia.'-01')->startOfMonth())
            : $query;
    }

    public function scopeEmAberto(Builder $query): Builder
    {
        return $query->where('status', MensalidadeStatus::Pendente);
    }

    public function scopeVencidas(Builder $query): Builder
    {
        return $query->emAberto()->whereDate('vencimento', '<', Carbon::today());
    }
}
