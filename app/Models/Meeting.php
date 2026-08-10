<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Meeting extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'type',
        'status',
        'scheduled_at',
        'ends_at',
        'location',
        'online_url',
        'description',
        'public_token',
        'confirmations_until',
        'quorum_minimum',
        'meeting_minute_id',
        'created_by',
    ];

    protected $casts = [
        'type' => MeetingType::class,
        'status' => MeetingStatus::class,
        'scheduled_at' => 'datetime',
        'ends_at' => 'datetime',
        'confirmations_until' => 'datetime',
        'quorum_minimum' => 'integer',
    ];

    protected static function booted(): void
    {
        // O token e o unico segredo do link de convocacao: gerado sempre no
        // servidor, nunca vindo do formulario.
        static::creating(function (self $meeting) {
            $meeting->public_token ??= Str::random(48);
        });
    }

    public function agendaItems(): HasMany
    {
        return $this->hasMany(MeetingAgendaItem::class)->orderBy('position')->orderBy('id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(MeetingAttendance::class);
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(MeetingActionItem::class)->orderByRaw('due_date is null, due_date');
    }

    public function minute(): BelongsTo
    {
        return $this->belongsTo(MeetingMinute::class, 'meeting_minute_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', MeetingStatus::Agendada)
            ->where('scheduled_at', '>=', now()->startOfDay())
            ->orderBy('scheduled_at');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(fn (Builder $inner) => $inner
            ->where('title', 'like', "%{$term}%")
            ->orWhere('location', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%"));
    }

    public function isPast(): bool
    {
        return $this->scheduled_at->isPast();
    }

    public function acceptsConfirmations(): bool
    {
        if ($this->status !== MeetingStatus::Agendada) {
            return false;
        }

        $limite = $this->confirmations_until ?? $this->scheduled_at;

        return $limite->isFuture();
    }

    public function confirmedCount(): int
    {
        return $this->attendances->where('status', AttendanceStatus::Confirmado)->count();
    }

    public function attendedCount(): int
    {
        return $this->attendances->where('attended', true)->count();
    }

    /**
     * O quorum considera a presenca real quando a reuniao ja aconteceu, e as
     * confirmacoes enquanto ela esta por vir — e a leitura util em cada momento.
     */
    public function hasQuorum(): ?bool
    {
        if (! $this->quorum_minimum) {
            return null;
        }

        $total = $this->status === MeetingStatus::Realizada
            ? $this->attendedCount()
            : $this->confirmedCount();

        return $total >= $this->quorum_minimum;
    }

    public function publicUrl(): string
    {
        return route('reuniao.convocacao', $this->public_token);
    }

    public function periodLabel(): string
    {
        $inicio = $this->scheduled_at->format('H:i');

        return $this->ends_at
            ? "{$inicio} às {$this->ends_at->format('H:i')}"
            : $inicio;
    }
}
