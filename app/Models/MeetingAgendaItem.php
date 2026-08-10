<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingAgendaItem extends Model
{
    use HasFactory;

    /** Desfechos possiveis de um item de pauta. */
    public const OUTCOMES = [
        'aprovado' => 'Aprovado',
        'rejeitado' => 'Rejeitado',
        'adiado' => 'Adiado',
        'informativo' => 'Informativo',
    ];

    protected $fillable = [
        'meeting_id',
        'position',
        'title',
        'description',
        'presenter',
        'duration_minutes',
        'outcome',
        'outcome_notes',
        'votes_for',
        'votes_against',
        'votes_abstain',
    ];

    protected $casts = [
        'position' => 'integer',
        'duration_minutes' => 'integer',
        'votes_for' => 'integer',
        'votes_against' => 'integer',
        'votes_abstain' => 'integer',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(MeetingActionItem::class);
    }

    public function outcomeLabel(): ?string
    {
        return $this->outcome ? (self::OUTCOMES[$this->outcome] ?? $this->outcome) : null;
    }

    public function hasVotes(): bool
    {
        return $this->votes_for !== null || $this->votes_against !== null || $this->votes_abstain !== null;
    }

    public function votesSummary(): string
    {
        return sprintf(
            '%d a favor, %d contra, %d abstenções',
            $this->votes_for ?? 0,
            $this->votes_against ?? 0,
            $this->votes_abstain ?? 0
        );
    }
}
