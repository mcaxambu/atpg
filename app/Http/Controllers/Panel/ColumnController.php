<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\ColumnRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * As colunas do proprio colunista.
 *
 * Um controller so atende empresa e membro: o que muda entre os dois e apenas
 * o prefixo da rota, entao duplicar a classe seria duplicar a regra. O nome da
 * rota de volta vem de `rotaBase()`, deduzido do papel de quem esta logado.
 *
 * Toda consulta parte de `minhasColunas()`, escopada pelo colunista do acesso:
 * nao ha caminho para alcancar a coluna de outro autor trocando o id na URL.
 */
class ColumnController extends Controller
{
    public function index(Request $request): View
    {
        return view('portal.panel.columns.index', [
            'columns' => $this->minhasColunas($request)->latest()->paginate(20),
            'columnist' => $request->user()->columnist(),
            'rotaBase' => $this->rotaBase($request),
            'layout' => $request->user()->isCompany() ? 'layouts.company' : 'layouts.membro',
        ]);
    }

    public function create(Request $request): View
    {
        return $this->form($request, new Post);
    }

    public function store(ColumnRequest $request): RedirectResponse
    {
        $data = $request->columnData();
        $data['columnist_id'] = $request->user()->columnist()->id;

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('post-covers', 'public');
        }

        $post = Post::create($data);

        return redirect()->route($this->rotaBase($request).'.index')
            ->with('status', "\"{$post->title}\" foi enviada para análise da associação.");
    }

    public function edit(Request $request, int $column): View
    {
        return $this->form($request, $this->minhasColunas($request)->findOrFail($column));
    }

    public function update(ColumnRequest $request, int $column): RedirectResponse
    {
        $post = $this->minhasColunas($request)->findOrFail($column);
        $data = $request->columnData($post);

        if ($request->hasFile('cover_image')) {
            $this->apagarCapa($post);
            $data['cover_image'] = $request->file('cover_image')->store('post-covers', 'public');
        }

        if ($request->boolean('remove_cover')) {
            $this->apagarCapa($post);
            $data['cover_image'] = null;
        }

        $post->update($data);

        return redirect()->route($this->rotaBase($request).'.index')
            ->with('status', "\"{$post->title}\" atualizada e enviada para nova análise.");
    }

    public function destroy(Request $request, int $column): RedirectResponse
    {
        $post = $this->minhasColunas($request)->findOrFail($column);

        $this->apagarCapa($post);
        $post->delete();

        return redirect()->route($this->rotaBase($request).'.index')
            ->with('status', "\"{$post->title}\" removida.");
    }

    /**
     * Colunas do colunista logado. `whereNull` no colunista seria um furo:
     * sem o filtro por id, um acesso alcancaria noticia da diretoria.
     */
    private function minhasColunas(Request $request)
    {
        $colunista = $request->user()->columnist();

        abort_unless($colunista, 403, 'Seu cadastro não está marcado como colunista.');

        return Post::query()->where('columnist_id', $colunista->id);
    }

    private function rotaBase(Request $request): string
    {
        return $request->user()->isCompany() ? 'empresa.colunas' : 'membro.colunas';
    }

    private function form(Request $request, Post $post): View
    {
        return view('portal.panel.columns.form', [
            'post' => $post,
            'columnist' => $request->user()->columnist(),
            'rotaBase' => $this->rotaBase($request),
            'layout' => $request->user()->isCompany() ? 'layouts.company' : 'layouts.membro',
        ]);
    }

    private function apagarCapa(Post $post): void
    {
        if ($post->cover_image) {
            Storage::disk('public')->delete($post->cover_image);
        }
    }
}
