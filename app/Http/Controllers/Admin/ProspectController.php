<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InteractionType;
use App\Enums\ProspectSource;
use App\Enums\ProspectStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProspectRequest;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Funil de novos associados.
 *
 * A tela principal e o funil em colunas, mas a lista que importa e a de quem
 * esta atrasado: um CRM so ajuda se disser o que fazer HOJE. Por isso o topo
 * mostra atrasados e parados, e o quadro vem depois.
 */
class ProspectController extends Controller
{
    public function index(Request $request): View
    {
        $etapaFiltro = ProspectStage::tryFrom((string) $request->query('etapa'));
        $busca = trim((string) $request->query('q'));
        $responsavel = $request->integer('responsavel') ?: null;

        $consulta = Prospect::query()
            ->with(['owner', 'company', 'member'])
            ->when($busca !== '', fn ($q) => $q->where(function ($inner) use ($busca) {
                $inner->where('name', 'like', "%{$busca}%")
                    ->orWhere('contact_name', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%");
            }))
            ->when($responsavel, fn ($q) => $q->where('owner_user_id', $responsavel));

        // Filtro por etapa troca o quadro por uma lista: quando a pessoa
        // escolhe uma etapa, ela quer ver todos daquela etapa, nao cinco.
        $lista = $etapaFiltro
            ? (clone $consulta)->naEtapa($etapaFiltro)->ordemDeTrabalho()->paginate(30)->withQueryString()
            : null;

        $colunas = [];

        if (! $etapaFiltro) {
            foreach (ProspectStage::funil() as $etapa) {
                $colunas[$etapa->value] = [
                    'etapa' => $etapa,
                    'total' => (clone $consulta)->naEtapa($etapa)->count(),
                    'cartoes' => (clone $consulta)->naEtapa($etapa)->ordemDeTrabalho()->limit(12)->get(),
                ];
            }
        }

        return view('portal.admin.crm.index', [
            'colunas' => $colunas,
            'lista' => $lista,
            'etapaFiltro' => $etapaFiltro,
            'busca' => $busca,
            'responsavel' => $responsavel,
            'responsaveis' => $this->responsaveis(),
            'atrasados' => (clone $consulta)->atrasados()->with('owner')->orderBy('next_action_at')->get(),
            'resumo' => $this->resumo(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Prospect(['stage' => ProspectStage::Novo, 'source' => ProspectSource::Prospeccao]));
    }

    public function store(ProspectRequest $request): RedirectResponse
    {
        $prospecto = Prospect::create($request->prospectData() + [
            'created_by' => $request->user()->id,
            'stage_changed_at' => now(),
        ]);

        $prospecto->interactions()->create([
            'user_id' => $request->user()->id,
            'type' => InteractionType::Sistema,
            'summary' => 'Prospecto cadastrado na etapa '.$prospecto->stage->label().'.',
            'happened_at' => now(),
        ]);

        return redirect()->route('admin.crm.show', $prospecto)
            ->with('status', "\"{$prospecto->name}\" entrou no funil.");
    }

    public function show(Prospect $prospect): View
    {
        return view('portal.admin.crm.show', [
            'prospect' => $prospect->load(['owner', 'creator', 'company', 'member', 'interactions.user']),
            'etapas' => ProspectStage::all(),
            'tiposDeContato' => InteractionType::manuais(),
        ]);
    }

    public function edit(Prospect $prospect): View
    {
        return $this->form($prospect);
    }

    public function update(ProspectRequest $request, Prospect $prospect): RedirectResponse
    {
        $prospect->update($request->prospectData());

        return redirect()->route('admin.crm.show', $prospect)->with('status', 'Prospecto atualizado.');
    }

    public function destroy(Prospect $prospect): RedirectResponse
    {
        $prospect->delete();

        return redirect()->route('admin.crm.index')->with('status', "\"{$prospect->name}\" foi removido do funil.");
    }

    /** Muda a etapa pelo proprio funil, sem abrir o formulario inteiro. */
    public function stage(Request $request, Prospect $prospect): RedirectResponse
    {
        $dados = $request->validate([
            'stage' => ['required', 'string', 'in:'.implode(',', array_column(ProspectStage::cases(), 'value'))],
            'lost_reason' => ['nullable', 'string', 'max:255', 'required_if:stage,perdido'],
        ], [
            'lost_reason.required_if' => 'Diga por que este prospecto não seguiu adiante.',
        ]);

        $prospect->moverPara(
            ProspectStage::from($dados['stage']),
            $request->user(),
            $dados['lost_reason'] ?? null
        );

        return back()->with('status', "Etapa alterada para {$prospect->stage->label()}.");
    }

    private function form(Prospect $prospect): View
    {
        return view('portal.admin.crm.form', [
            'prospect' => $prospect,
            'etapas' => ProspectStage::all(),
            'origens' => ProspectSource::all(),
            'responsaveis' => $this->responsaveis(),
        ]);
    }

    /** Quem pode ficar responsavel: usuarios do painel com acesso ao modulo. */
    private function responsaveis()
    {
        return User::query()
            ->where('role', \App\Enums\UserRole::Admin)
            ->orderBy('name')
            ->get()
            ->filter(fn (User $u) => $u->canAccessModule(\App\Enums\AdminModule::Crm))
            ->values();
    }

    private function resumo(): array
    {
        return [
            'em_andamento' => Prospect::emAndamento()->count(),
            'atrasados' => Prospect::atrasados()->count(),
            'associados_no_mes' => Prospect::where('stage', ProspectStage::Associado->value)
                ->where('stage_changed_at', '>=', now()->startOfMonth())
                ->count(),
        ];
    }
}
