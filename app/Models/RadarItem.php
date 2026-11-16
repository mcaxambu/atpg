<?php

namespace App\Models;

use App\Enums\RadarStatus;
use App\Enums\RadarTipo;
use App\Support\Radar\Fontes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Um achado do radar, esperando a diretoria decidir.
 */
class RadarItem extends Model
{
    use HasFactory;

    protected $table = 'radar_itens';

    protected $fillable = [
        'fonte',
        'url',
        'url_hash',
        'titulo_original',
        'resumo_original',
        'publicado_em',
        'tipo',
        'titulo',
        'resumo',
        'data_inicio',
        'data_fim',
        'hora_inicio',
        'local',
        'organizador',
        'inscricao_url',
        'confianca',
        'motivo',
        'status',
        'decidido_em',
        'decidido_por',
        'publicado_type',
        'publicado_id',
        'erro',
    ];

    protected $casts = [
        'tipo' => RadarTipo::class,
        'status' => RadarStatus::class,
        'publicado_em' => 'datetime',
        'decidido_em' => 'datetime',
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'confianca' => 'integer',
    ];

    /**
     * A chave que impede o mesmo link de voltar toda madrugada.
     */
    public static function hashDa(string $url): string
    {
        return hash('sha256', rtrim(trim($url), '/'));
    }

    public function decidor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decidido_por');
    }

    public function publicado(): MorphTo
    {
        return $this->morphTo();
    }

    public function nomeDaFonte(): string
    {
        return Fontes::nome($this->fonte);
    }

    public function scopeEsperando(Builder $query): Builder
    {
        return $query->where('status', RadarStatus::Novo);
    }

    public function scopeDoTipo(Builder $query, ?string $tipo): Builder
    {
        return $tipo ? $query->where('tipo', $tipo) : $query;
    }

    /**
     * Evento que ja passou nao serve para a agenda — e o item mais comum de
     * chegar atrasado, porque a materia so sai depois que o evento aconteceu.
     */
    public function jaPassou(): bool
    {
        return $this->tipo === RadarTipo::Evento
            && $this->data_inicio !== null
            && $this->data_inicio->isBefore(now()->startOfDay());
    }
}
