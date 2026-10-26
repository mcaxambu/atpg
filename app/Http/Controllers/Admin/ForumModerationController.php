<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumReply;
use App\Models\ForumTopic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Moderação do fórum.
 *
 * O fórum é aberto aos associados e NÃO passa por aprovação prévia: conversa
 * que só aparece depois de liberada deixa de ser conversa. A moderação age
 * depois — fixar, fechar, ocultar — e tudo é reversível.
 *
 * Ocultar é diferente de excluir de propósito: tira da vista sem destruir o
 * que a pessoa escreveu, e dá para voltar atrás.
 */
class ForumModerationController extends Controller
{
    public function index(Request $request): View
    {
        $filtro = $request->query('filtro', 'todos');

        $topicos = ForumTopic::query()
            ->with(['author.company', 'author.member', 'category'])
            ->when($filtro === 'ocultos', fn ($q) => $q->where('is_hidden', true))
            ->when($filtro === 'fechados', fn ($q) => $q->where('is_closed', true))
            ->when($busca = trim((string) $request->query('q')), fn ($q) => $q->where('title', 'like', "%{$busca}%"))
            ->porAtividade()
            ->paginate(25)
            ->withQueryString();

        return view('portal.admin.forum.topics', [
            'topicos' => $topicos,
            'filtro' => $filtro,
            'busca' => $busca,
            'totais' => [
                'todos' => ForumTopic::count(),
                'ocultos' => ForumTopic::where('is_hidden', true)->count(),
                'fechados' => ForumTopic::where('is_closed', true)->count(),
            ],
        ]);
    }

    public function show(ForumTopic $topic): View
    {
        return view('portal.admin.forum.topic', [
            'topic' => $topic->load(['category', 'author.company', 'author.member', 'replies.author.company', 'replies.author.member']),
        ]);
    }

    public function toggle(Request $request, ForumTopic $topic): RedirectResponse
    {
        $dados = $request->validate([
            'acao' => ['required', 'in:fixar,fechar,ocultar'],
        ]);

        $campo = match ($dados['acao']) {
            'fixar' => 'is_pinned',
            'fechar' => 'is_closed',
            'ocultar' => 'is_hidden',
        };

        $topic->forceFill([$campo => ! $topic->{$campo}])->save();

        $aviso = match ($dados['acao']) {
            'fixar' => $topic->is_pinned ? 'Tópico fixado no topo.' : 'Tópico desafixado.',
            'fechar' => $topic->is_closed ? 'Tópico fechado para novas respostas.' : 'Tópico reaberto.',
            'ocultar' => $topic->is_hidden ? 'Tópico oculto dos associados.' : 'Tópico visível de novo.',
        };

        return back()->with('status', $aviso);
    }

    public function toggleReply(ForumReply $reply): RedirectResponse
    {
        $reply->forceFill(['is_hidden' => ! $reply->is_hidden])->save();

        // A contagem do topico acompanha: resposta oculta nao conta na lista.
        $reply->topic->forceFill([
            'replies_count' => $reply->topic->replies()->visiveis()->count(),
        ])->save();

        return back()->with('status', $reply->is_hidden ? 'Resposta oculta.' : 'Resposta visível de novo.');
    }

    public function destroy(ForumTopic $topic): RedirectResponse
    {
        $topic->delete();

        return redirect()->route('admin.forum.topics.index')
            ->with('status', "\"{$topic->title}\" foi removido do fórum.");
    }
}
