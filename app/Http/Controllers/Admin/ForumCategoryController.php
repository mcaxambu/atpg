<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Assuntos do fórum, mantidos pela diretoria.
 *
 * Categoria não se apaga quando já tem tópico: a conversa dos associados não é
 * da diretoria para descartar. Desativar tira da lista de "abrir tópico" e
 * mantém tudo legível.
 */
class ForumCategoryController extends Controller
{
    public function index(): View
    {
        return view('portal.admin.forum.categories', [
            'categorias' => ForumCategory::emOrdem()->withCount('topics')->get(),
        ]);
    }

    public function create(): View
    {
        return view('portal.admin.forum.category-form', ['categoria' => new ForumCategory(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $this->validar($request);

        ForumCategory::create($dados + ['slug' => ForumCategory::uniqueSlug($dados['name'])]);

        return redirect()->route('admin.forum.categories.index')->with('status', 'Assunto criado.');
    }

    public function edit(ForumCategory $category): View
    {
        return view('portal.admin.forum.category-form', ['categoria' => $category]);
    }

    public function update(Request $request, ForumCategory $category): RedirectResponse
    {
        $category->update($this->validar($request));

        return redirect()->route('admin.forum.categories.index')->with('status', 'Assunto atualizado.');
    }

    public function destroy(ForumCategory $category): RedirectResponse
    {
        if ($category->topics()->exists()) {
            return back()->withErrors([
                'categoria' => 'Este assunto já tem tópicos. Desative-o em vez de excluir — assim a conversa continua legível.',
            ]);
        }

        $category->delete();

        return redirect()->route('admin.forum.categories.index')->with('status', 'Assunto excluído.');
    }

    /** @return array<string, mixed> */
    private function validar(Request $request): array
    {
        return $request->validate(
            [
                'name' => ['required', 'string', 'max:80'],
                'description' => ['nullable', 'string', 'max:160'],
                'position' => ['nullable', 'integer', 'min:0', 'max:999'],
                'is_active' => ['nullable', 'boolean'],
            ],
            ['name.required' => 'Dê um nome ao assunto.']
        ) + ['is_active' => $request->boolean('is_active')];
    }
}
