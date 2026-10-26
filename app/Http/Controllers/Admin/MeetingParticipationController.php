<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
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

        $linhas = MeetingParticipation::porEmpresa();

        $linhas = match ($ordem) {
            // Sem presença registrada a ordenação por presença é inútil; cai
            // para a taxa de resposta, que existe desde a primeira convocação.
            'presenca' => $linhas->sortByDesc(fn (array $l) => $l['taxa_presenca'] ?? -1),
            'ausencia' => $linhas->sortBy(fn (array $l) => $l['taxa_presenca'] ?? 999),
            'resposta' => $linhas->sortByDesc(fn (array $l) => $l['taxa_resposta']),
            default => $linhas->sortBy(fn (array $l) => $l['empresa']->name),
        };

        return view('portal.admin.reunioes.participacao', [
            'resumo' => MeetingParticipation::resumo(),
            'linhas' => $linhas->values(),
            'ordem' => $ordem,
        ]);
    }

    public function show(Company $company): View
    {
        return view('portal.admin.reunioes.participacao-empresa', [
            'empresa' => $company,
            'historico' => MeetingParticipation::historicoDa($company),
        ]);
    }
}
