<?php

namespace App\Models\Concerns;

use App\Enums\ModerationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fluxo de moderacao compartilhado por Empresa e Membro.
 *
 * `status` guarda a decisao da associacao (pendente / aprovado / rejeitado).
 * `is_active` guarda a publicacao: um registro aprovado pode ser despublicado
 * temporariamente sem perder a aprovacao. Visivel no portal = aprovado + ativo.
 */
trait Moderatable
{
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ModerationStatus::Pending);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', ModerationStatus::Approved);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', ModerationStatus::Rejected);
    }

    /**
     * Registros efetivamente visiveis no site publico.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', ModerationStatus::Approved)->where('is_active', true);
    }

    public function isVisible(): bool
    {
        return $this->status === ModerationStatus::Approved && $this->is_active;
    }

    public function isPending(): bool
    {
        return $this->status === ModerationStatus::Pending;
    }

    /**
     * Aprovado pela associacao — independente de estar publicado agora.
     */
    public function isApproved(): bool
    {
        return $this->status === ModerationStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === ModerationStatus::Rejected;
    }

    public function approve(?User $reviewer = null): void
    {
        $this->forceFill([
            'status' => ModerationStatus::Approved,
            'is_active' => true,
            'rejection_reason' => null,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer?->id,
        ])->save();
    }

    public function reject(?string $reason = null, ?User $reviewer = null): void
    {
        $this->forceFill([
            'status' => ModerationStatus::Rejected,
            'is_active' => false,
            'rejection_reason' => $reason,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer?->id,
        ])->save();
    }
}
