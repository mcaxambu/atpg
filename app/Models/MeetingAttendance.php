<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'company_id',
        'member_id',
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

    /**
     * Reuniao online: a pessoa entrou na chamada PELO PORTAL.
     *
     * Nao e a mesma prova que o codigo da sala — prova que clicou para entrar,
     * nao que ficou. E o maximo que da para saber sem o Google Workspace, cuja
     * API de participantes exige conta paga, admin e conta de servico.
     */
    public const CHECKIN_ONLINE = 'online';

    public function fezCheckin(): bool
    {
        return $this->checked_in_at !== null;
    }

    public function checkinLabel(): string
    {
        return match ($this->checked_in_source) {
            self::CHECKIN_EMPRESA => 'check-in da empresa',
            self::CHECKIN_DIRETORIA => 'marcado na recepção',
            self::CHECKIN_ONLINE => 'entrou pela videochamada',
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

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Quem e o convocado desta linha: uma empresa associada OU um membro
     * pessoa fisica. Nunca os dois — ver a migracao que criou `member_id`.
     */
    public function participante(): Company|Member|null
    {
        return $this->company ?? $this->member;
    }

    public function nomeDoParticipante(): string
    {
        return $this->participante()?->name ?? 'Associado removido';
    }

    /**
     * Identificador de uma linha para formularios e listas.
     *
     * Empresa e membro tem ids independentes, entao mandar so o numero
     * registraria presenca na pessoa errada.
     */
    public function chaveDoParticipante(): string
    {
        return self::chaveDe($this->participante());
    }

    public static function chaveDe(Company|Member|null $participante): string
    {
        return match (true) {
            $participante instanceof Company => 'empresa:'.$participante->id,
            $participante instanceof Member => 'membro:'.$participante->id,
            default => '',
        };
    }

    /**
     * Resolve a chave de volta no participante, ou null se nao existir.
     */
    public static function participantePorChave(?string $chave): Company|Member|null
    {
        [$tipo, $id] = array_pad(explode(':', (string) $chave, 2), 2, null);

        return match ($tipo) {
            'empresa' => Company::find($id),
            'membro' => Member::find($id),
            default => null,
        };
    }

    /**
     * Filtra a linha de um participante, seja ele empresa ou membro.
     */
    public function scopeDoParticipante(Builder $query, Company|Member|null $participante): Builder
    {
        return match (true) {
            $participante instanceof Company => $query->where('company_id', $participante->id),
            $participante instanceof Member => $query->where('member_id', $participante->id),
            // Sem participante nao ha linha nenhuma — e nao a lista inteira.
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function hasReplied(): bool
    {
        return $this->status !== AttendanceStatus::Pendente;
    }
}
