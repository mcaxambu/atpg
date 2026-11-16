<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GerarMensalidades;
use App\Enums\MensalidadeStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Mensalidade;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Financeiro: mensalidade dos associados.
 *
 * Sem cobrança automática por enquanto — o provedor ainda não foi escolhido.
 * O que existe aqui é o controle: quem deve, quem pagou, quanto entrou. A
 * baixa é manual, e o campo de cobrança externa está no banco esperando a
 * integração, sem nada depender dela.
 */
class MensalidadeController extends Controller
{
    public function index(Request $request): View
    {
        $competencia = $request->query('competencia') ?: Carbon::today()->format('Y-m');

        $mensalidades = Mensalidade::query()
            ->daCompetencia($competencia)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->with('company:id,name,slug')
            ->get()
            ->sortBy(fn (Mensalidade $m) => $m->company?->name)
            ->values();

        return view('portal.admin.financeiro.index', [
            'mensalidades' => $mensalidades,
            'competencia' => $competencia,
            'competencias' => $this->competenciasConhecidas(),
            'resumo' => $this->resumo($mensalidades),
            'valorConfigurado' => GerarMensalidades::valorAtual(),
            'diaVencimento' => GerarMensalidades::diaDeVencimento(),
            'isentas' => Company::visible()->where('mensalidade_isenta', true)->count(),
        ]);
    }

    /**
     * Gera o mês pela tela — o cron faz o mesmo no dia 1.
     */
    public function gerar(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'competencia' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        if ((float) GerarMensalidades::valorAtual() <= 0) {
            return back()->withErrors(['competencia' => 'Defina o valor da mensalidade antes de gerar.']);
        }

        $resultado = app(GerarMensalidades::class)(
            Carbon::parse($dados['competencia'].'-01'),
            $request->user()->id
        );

        return redirect()->route('admin.financeiro.index', ['competencia' => $dados['competencia']])
            ->with('status', "{$resultado['geradas']} mensalidade(s) gerada(s); {$resultado['puladas']} já existia(m).");
    }

    /**
     * Baixa manual do pagamento.
     */
    public function pagar(Request $request, Mensalidade $mensalidade): RedirectResponse
    {
        $dados = $request->validate([
            'pago_em' => ['nullable', 'date'],
            'forma_pagamento' => ['nullable', 'in:'.implode(',', array_keys(Mensalidade::FORMAS))],
            'observacao' => ['nullable', 'string', 'max:500'],
        ]);

        $mensalidade->update([
            'status' => MensalidadeStatus::Paga,
            'pago_em' => $dados['pago_em'] ?? Carbon::today(),
            'forma_pagamento' => $dados['forma_pagamento'] ?? null,
            'observacao' => $dados['observacao'] ?? $mensalidade->observacao,
            'registrado_por' => $request->user()->id,
        ]);

        return back()->with('status', 'Pagamento registrado.');
    }

    /**
     * Desfaz a baixa: erro de clique em dinheiro precisa de volta.
     */
    public function reabrir(Mensalidade $mensalidade): RedirectResponse
    {
        $mensalidade->update([
            'status' => MensalidadeStatus::Pendente,
            'pago_em' => null,
            'forma_pagamento' => null,
        ]);

        return back()->with('status', 'Mensalidade reaberta.');
    }

    public function situacao(Request $request, Mensalidade $mensalidade): RedirectResponse
    {
        $dados = $request->validate([
            'status' => ['required', 'in:isenta,cancelada'],
            'observacao' => ['nullable', 'string', 'max:500'],
        ]);

        $mensalidade->update([
            'status' => MensalidadeStatus::from($dados['status']),
            'pago_em' => null,
            'observacao' => $dados['observacao'] ?? $mensalidade->observacao,
            'registrado_por' => $request->user()->id,
        ]);

        return back()->with('status', 'Situação atualizada.');
    }

    public function configuracao(): View
    {
        return view('portal.admin.financeiro.configuracao', [
            'valor' => GerarMensalidades::valorAtual(),
            'dia' => GerarMensalidades::diaDeVencimento(),
            'isentas' => Company::visible()->orderBy('name')->get(['id', 'name', 'mensalidade_isenta']),
        ]);
    }

    public function salvarConfiguracao(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'valor' => ['required', 'numeric', 'min:0', 'max:99999'],
            'dia' => ['required', 'integer', 'min:1', 'max:28'],
            'isentas' => ['nullable', 'array'],
            'isentas.*' => ['integer', 'exists:companies,id'],
        ]);

        SiteSetting::setValue(GerarMensalidades::CHAVE_VALOR, number_format((float) $dados['valor'], 2, '.', ''), 'text', 'financeiro');
        SiteSetting::setValue(GerarMensalidades::CHAVE_DIA_VENCIMENTO, (string) $dados['dia'], 'text', 'financeiro');

        $isentas = $dados['isentas'] ?? [];

        Company::query()->update(['mensalidade_isenta' => false]);

        if ($isentas !== []) {
            Company::whereIn('id', $isentas)->update(['mensalidade_isenta' => true]);
        }

        return redirect()->route('admin.financeiro.configuracao')
            ->with('status', 'Configuração salva. Vale para as próximas gerações; o que já foi gerado não muda.');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Mensalidade>  $mensalidades
     * @return array<string, mixed>
     */
    private function resumo($mensalidades): array
    {
        $previsto = $mensalidades->filter(fn (Mensalidade $m) => $m->status->contaComoReceita())->sum('valor');
        $recebido = $mensalidades->where('status', MensalidadeStatus::Paga)->sum('valor');
        $vencidas = $mensalidades->filter(fn (Mensalidade $m) => $m->estaVencida());

        return [
            'previsto' => $previsto,
            'recebido' => $recebido,
            'em_aberto' => $previsto - $recebido,
            'vencidas' => $vencidas->count(),
            'valor_vencido' => $vencidas->sum('valor'),
            'total' => $mensalidades->count(),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function competenciasConhecidas()
    {
        $existentes = Mensalidade::query()
            ->selectRaw('competencia')
            ->distinct()
            ->orderByDesc('competencia')
            ->pluck('competencia')
            ->map(fn ($data) => Carbon::parse($data)->format('Y-m'));

        // O mês de hoje aparece mesmo sem nada gerado — é de lá que se clica
        // em "gerar".
        return $existentes->prepend(Carbon::today()->format('Y-m'))->unique()->values();
    }
}
