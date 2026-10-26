<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'company_id',
        'status',
        'responded_by',
        'responded_at',
        'attended',
        'checked_in_at',
        'checked_in_source',
        'notes',
    ];

    protected $casts = [
        'status' => AttendanceStatus::class,
        'responded_at' => 'datetime',
        'attended' => 'boolean',
        'checked_in_at' => 'datetime',
    ];

    public const CHECKIN_EMPRESA = 'empresa';

    public const CHECKIN_DIRETORIA = 'diretoria';

    public function fezCheckin(): bool
    {
        return $this->checked_in_at !== null;
    }

    public function checkinLabel(): string
    {
        return match ($this->checked_in_source) {
            self::CHECKIN_EMPRESA => 'check-in da empresa',
            self::CHECKIN_DIRETORIA => 'marcado na recepção',
            default => '',
        };
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function hasReplied(): bool
    {
        return $this->status !== AttendanceStatus::Pendente;
    }
}
