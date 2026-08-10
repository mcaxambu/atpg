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
        'notes',
    ];

    protected $casts = [
        'status' => AttendanceStatus::class,
        'responded_at' => 'datetime',
        'attended' => 'boolean',
    ];

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
