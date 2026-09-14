<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Candidatura de uma pessoa a uma vaga.
 *
 * Dado pessoal de terceiro: o curriculo mora no disco privado e so sai de la
 * pela rota de download da empresa dona da vaga.
 */
class JobApplication extends Model
{
    use HasFactory;

    /** Disco privado — nao e servido pelo Apache. */
    public const DISK = 'local';

    protected $fillable = [
        'job_opening_id',
        'name',
        'email',
        'phone',
        'linkedin_url',
        'message',
        'resume_path',
        'consented_at',
        'viewed_at',
    ];

    protected $casts = [
        'consented_at' => 'datetime',
        'viewed_at' => 'datetime',
    ];

    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }

    public function hasResume(): bool
    {
        return filled($this->resume_path) && Storage::disk(self::DISK)->exists($this->resume_path);
    }

    public function markAsViewed(): void
    {
        if ($this->viewed_at === null) {
            $this->forceFill(['viewed_at' => now()])->save();
        }
    }

    public function deleteResume(): void
    {
        if (filled($this->resume_path)) {
            Storage::disk(self::DISK)->delete($this->resume_path);
        }
    }

    /**
     * Apagar a candidatura leva o curriculo junto: nao faz sentido guardar o
     * arquivo de alguem cujo registro ja nao existe.
     */
    protected static function booted(): void
    {
        static::deleting(fn (self $application) => $application->deleteResume());
    }
}
