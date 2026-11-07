<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MeetingAttendance;
use App\Support\MeetingParticipation;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Participação dos associados nas reuniões.
 *
 * Responde duas perguntas da diretoria: quem está vindo, e como anda a
 * participação de uma empresa específica ao longo do tempo.
 */
class MeetingParticipationController extends Controller
{
    public function index(Request $request): View
    {
        $ordem = $request->query('ordem', 'nome');

        $linhas = MeetingParticipation::porParticipante();

        $linhas = match ($ordem) {
            // Sem presença registrada a ordenação por presença é inútil; cai
            // para a taxa de resposta, que existe desde a primeira convocação.
            'presenca' => $linhas->sortByDesc(fn (array $l) => $l['taxa_presenca'] ?? -1),
            'ausencia' => $linhas->sortBy(fn (array $l) => $l['taxa_presenca'] ?? 999),
            'resposta' => $linhas->sortByDesc(fn (array $l) => $l['taxa_resposta']),
            default => $linhas->sortBy(fn (array $l) => $l['participante']?->name),
        };

        return view('portal.admin.reunioes.participacao', [
            'resumo' => MeetingParticipation::resumo(),
            'linhas' => $linhas->values(),
            'ordem' => $ordem,
        ]);
    }

    /**
     * O histórico de um associado — empresa ou pessoa física.
     *
     * O tipo vem no endereço porque os ids de um e de outro se repetem: sem
     * ele, /participacao/3 abriria a empresa 3 ou o membro 3 ao acaso.
     */
    public function show(string $tipo, int $id): View
    {
        $participante = MeetingAttendance::participantePorChave($tipo.':'.$id);

        abort_unless($participante, 404);

        return view('portal.admin.reunioes.participacao-associado', [
            'participante' => $participante,
            'historico' => MeetingParticipation::historicoDa($participante),
        ]);
    }
}
