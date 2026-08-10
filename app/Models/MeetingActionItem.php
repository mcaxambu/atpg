<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Encaminhamento: o que ficou decidido, com responsavel e prazo.
 */
class MeetingActionItem extends Model
{
    use HasFactory;

    public const STATUSES = [
        'pendente' => 'Pendente',
        'concluido' => 'Concluído',
        'cancelado' => 'Cancelado',
    ];

    protected $fillable = [
        'meeting_id',
        'meeting_agenda_item_id',
        'title',
        'description',
        'responsible',
        'due_date',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function agendaItem(): BelongsTo
    {
        return $this->belongsTo(MeetingAgendaItem::class, 'meeting_agenda_item_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pendente');
    }

    /**
     * Vencido: so faz sentido para o que ainda esta pendente.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->pending()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString());
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pendente'
            && $this->due_date !== null
            && $this->due_date->isPast()
            && ! $this->due_date->isToday();
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
