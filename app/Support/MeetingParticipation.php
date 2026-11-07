<?php

namespace App\Support;

use App\Enums\AttendanceStatus;
use App\Enums\MeetingStatus;
use App\Models\Company;
use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\Member;
use Illuminate\Support\Collection;

/**
 * Participação dos associados nas reuniões.
 *
 * São DUAS medidas diferentes, e misturá-las dá número errado:
 *
 * - RESPOSTA à convocação: a empresa respondeu que vai, que talvez, ou que não
 *   vai. Existe para qualquer reunião, inclusive as que ainda vão acontecer.
 * - PRESENÇA no dia: só existe depois que a diretoria registra a lista de
 *   presentes, o que encerra a reunião. Reunião agendada não entra na conta —
 *   senão toda reunião futura contaria como falta de todo mundo.
 */
class MeetingParticipation
{
    /** Reuniões que já aconteceram e têm lista de presença registrada. */
    public static function reunioesRealizadas(): Collection
    {
        return Meeting::query()
            ->where('status', MeetingStatus::Realizada)
            ->orderByDesc('scheduled_at')
            ->get();
    }

    /**
     * Números da associação inteira.
     *
     * @return array<string, mixed>
     */
    public static function resumo(): array
    {
        $realizadas = self::reunioesRealizadas();
        $idsRealizadas = $realizadas->pluck('id');

        $convocacoes = MeetingAttendance::query()->count();
        $responderam = MeetingAttendance::query()
            ->where('status', '!=', AttendanceStatus::Pendente->value)
            ->count();

        $convocadosEmRealizadas = MeetingAttendance::query()
            ->whereIn('meeting_id', $idsRealizadas)
            ->count();

        $presencas = MeetingAttendance::query()
            ->whereIn('meeting_id', $idsRealizadas)
            ->where('attended', true)
            ->count();

        return [
            'reunioes_total' => Meeting::count(),
            'reunioes_realizadas' => $realizadas->count(),
            'convocacoes' => $convocacoes,
            'taxa_resposta' => self::porcentagem($responderam, $convocacoes),
            // Media de presenca por reuniao realizada. Sem reuniao encerrada,
            // e `null` — e nao zero: nao ha o que medir ainda.
            'media_presenca' => $idsRealizadas->isEmpty()
                ? null
                : self::porcentagem($presencas, $convocadosEmRealizadas),
            'presentes_por_reuniao' => $realizadas->isEmpty()
                ? null
                : round($presencas / max($realizadas->count(), 1), 1),
        ];
    }

    /**
     * Uma linha por associado convocado — empresa ou pessoa física.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function porParticipante(): Collection
    {
        $idsRealizadas = self::reunioesRealizadas()->pluck('id');

        return MeetingAttendance::query()
            ->with(['company', 'member'])
            ->get()
            // Agrupa pela chave do participante: ids de empresa e de membro se
            // repetem, e agrupar so pelo numero juntaria gente diferente.
            ->groupBy(fn (MeetingAttendance $p) => $p->chaveDoParticipante())
            ->filter(fn ($linhas, $chave) => $chave !== '')
            ->map(function (Collection $linhas, string $chave) use ($idsRealizadas) {
                $emRealizadas = $linhas->whereIn('meeting_id', $idsRealizadas);

                $convocacoes = $linhas->count();
                $responderam = $linhas->where('status', '!=', AttendanceStatus::Pendente)->count();
                $compareceu = $emRealizadas->where('attended', true)->count();

                return [
                    'participante' => $linhas->first()->participante(),
                    'chave' => $chave,
                    'convocacoes' => $convocacoes,
                    'responderam' => $responderam,
                    'taxa_resposta' => self::porcentagem($responderam, $convocacoes),
                    'confirmadas' => $linhas->where('status', AttendanceStatus::Confirmado)->count(),
                    'realizadas' => $emRealizadas->count(),
                    'presencas' => $compareceu,
                    'taxa_presenca' => $emRealizadas->isEmpty()
                        ? null
                        : self::porcentagem($compareceu, $emRealizadas->count()),
                ];
            })
            ->values()
            ->sortBy(fn (array $linha) => $linha['participante']?->name)
            ->values();
    }

    /**
     * O histórico de um associado, reunião a reunião.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function historicoDa(Company|Member $participante): Collection
    {
        return MeetingAttendance::query()
            ->with('meeting')
            ->doParticipante($participante)
            ->get()
            ->sortByDesc(fn (MeetingAttendance $p) => $p->meeting?->scheduled_at)
            ->values()
            ->map(fn (MeetingAttendance $p) => [
                'reuniao' => $p->meeting,
                'resposta' => $p->status,
                'respondeu_em' => $p->responded_at,
                'compareceu' => $p->meeting?->status === MeetingStatus::Realizada ? (bool) $p->attended : null,
            ]);
    }

    private static function porcentagem(int $parte, int $total): int
    {
        return $total === 0 ? 0 : (int) round($parte / $total * 100);
    }
}
