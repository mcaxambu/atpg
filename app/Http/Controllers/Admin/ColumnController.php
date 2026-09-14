<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ModerationStatus;
use App\Http\Controllers\Controller;
use App\Models\Columnist;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Colunistas e moderacao das colunas.
 *
 * Coluna e uma noticia com autor, entao a edicao do texto continua sendo a do
 * CMS. Aqui a diretoria faz o que e proprio da coluna: ver quem assina, ler o
 * que chegou e decidir se publica.
 */
class ColumnController extends Controller
{
    public function index(Request $request): View
    {
        return view('portal.admin.columns.index', [
            'columns' => Post::query()
                ->columns()
                ->with('columnist.member', 'columnist.company')
                ->when(
                    $request->filled('status') && ModerationStatus::tryFrom($request->query('status')),
                    fn ($query) => $query->where('status', $request->query('status'))
                )
                ->when($request->query('q'), fn ($query, $termo) => $query->where('title', 'like', "%{$termo}%"))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'statusCounts' => [
                'all' => Post::columns()->count(),
                ModerationStatus::Pending->value => Post::columns()->pending()->count(),
                ModerationStatus::Approved->value => Post::columns()->approved()->count(),
                ModerationStatus::Rejected->value => Post::columns()->rejected()->count(),
            ],
        ]);
    }

    public function pending(Request $request): View
    {
        return view('portal.admin.columns.pending', [
            'columns' => Post::query()
                ->columns()
                ->pending()
                ->with('columnist.member', 'columnist.company')
                ->latest()
                ->paginate(20),
        ]);
    }

    /** Lista de quem assina coluna, com a contagem de textos. */
    public function columnists(): View
    {
        return view('portal.admin.columns.columnists', [
            'columnists' => Columnist::query()
                ->with('member:id,name,slug,photo_path', 'company:id,name,slug,logo_path')
                ->withCount('posts')
                ->orderByDesc('is_active')
                ->get(),
        ]);
    }

    public function show(Post $column): View
    {
        abort_unless($column->isColumn(), 404);

        return view('portal.admin.columns.show', [
            'column' => $column->load('columnist.member', 'columnist.company', 'reviewer'),
        ]);
    }

    public function approve(Request $request, Post $column): RedirectResponse
    {
        abort_unless($column->isColumn(), 404);

        $column->approve($request->user());

        // approve() do trait liga is_active, que a noticia nao usa: quem
        // controla a publicacao aqui e is_published + published_at.
        $column->forceFill([
            'is_published' => true,
            'published_at' => $column->published_at ?? now(),
        ])->save();

        return back()->with('status', "\"{$column->title}\" aprovada e publicada no portal.");
    }

    public function reject(Request $request, Post $column): RedirectResponse
    {
        abort_unless($column->isColumn(), 404);

        $dados = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ], [], ['rejection_reason' => 'motivo']);

        $column->reject($dados['rejection_reason'] ?? null, $request->user());
        $column->forceFill(['is_published' => false, 'published_at' => null])->save();

        return back()->with('status', "\"{$column->title}\" devolvida. O colunista vê o motivo no painel dele.");
    }

    public function unpublish(Post $column): RedirectResponse
    {
        abort_unless($column->isColumn(), 404);

        $column->update(['is_published' => false]);

        return back()->with('status', "\"{$column->title}\" saiu do portal.");
    }

    public function publish(Post $column): RedirectResponse
    {
        abort_unless($column->isColumn(), 404);

        $column->forceFill([
            'status' => ModerationStatus::Approved,
            'is_published' => true,
            'published_at' => $column->published_at ?? now(),
        ])->save();

        return back()->with('status', "\"{$column->title}\" voltou ao portal.");
    }
}
