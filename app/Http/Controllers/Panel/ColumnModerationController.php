<?php

namespace App\Http\Controllers\Panel;

use App\Enums\ModerationStatus;
use App\Http\Controllers\Controller;
use App\Models\Columnist;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Moderacao das colunas feita pela empresa delegada.
 *
 * O painel da empresa mostra apenas os dados dela; esta e a unica excecao, e
 * so existe quando a diretoria marca `moderates_columns` no cadastro. Por isso
 * TODA acao passa por `garantirPermissao()`: sem a marcacao e 403, mesmo que
 * alguem descubra a URL.
 *
 * A decisao em si (aprovar/devolver) usa os mesmos metodos do model que o
 * admin usa — a regra de moderacao mora no Post, nao duplicada aqui.
 */
class ColumnModerationController extends Controller
{
    public function index(Request $request): View
    {
        $this->garantirPermissao($request);

        return view('portal.panel.moderation.index', [
            'columns' => Post::query()
                ->columns()
                ->with('columnist.member', 'columnist.company')
                ->when(
                    $request->filled('status') && ModerationStatus::tryFrom($request->query('status')),
                    fn ($query) => $query->where('status', $request->query('status'))
                )
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'pendentes' => Post::columns()->pending()->count(),
            'colunistas' => Columnist::query()->where('is_active', true)->count(),
        ]);
    }

    public function show(Request $request, Post $column): View
    {
        $this->garantirPermissao($request);
        abort_unless($column->isColumn(), 404);

        return view('portal.panel.moderation.show', [
            'column' => $column->load('columnist.member', 'columnist.company'),
        ]);
    }

    public function approve(Request $request, Post $column): RedirectResponse
    {
        $this->garantirPermissao($request);
        abort_unless($column->isColumn(), 404);

        $column->approve($request->user());

        return redirect()->route('empresa.moderacao.index')
            ->with('status', "\"{$column->title}\" aprovada e publicada no portal.");
    }

    public function reject(Request $request, Post $column): RedirectResponse
    {
        $this->garantirPermissao($request);
        abort_unless($column->isColumn(), 404);

        $dados = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ], [], ['rejection_reason' => 'motivo']);

        $column->reject($dados['rejection_reason'] ?? null, $request->user());

        return redirect()->route('empresa.moderacao.index')
            ->with('status', "\"{$column->title}\" devolvida ao colunista.");
    }

    public function unpublish(Request $request, Post $column): RedirectResponse
    {
        $this->garantirPermissao($request);
        abort_unless($column->isColumn(), 404);

        $column->update(['is_published' => false]);

        return back()->with('status', "\"{$column->title}\" saiu do portal.");
    }

    private function garantirPermissao(Request $request): void
    {
        abort_unless(
            (bool) $request->user()?->company?->moderates_columns,
            403,
            'Sua empresa não é responsável pela moderação das colunas.'
        );
    }
}
