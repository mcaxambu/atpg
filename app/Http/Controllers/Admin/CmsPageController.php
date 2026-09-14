<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CmsPageController extends Controller
{
    public function index()
    {
        return view('portal.admin.cms.pages.index', [
            'pages' => CmsPage::query()->orderBy('position')->orderBy('title')->get(),
        ]);
    }

    public function create()
    {
        return view('portal.admin.cms.pages.form', ['page' => new CmsPage]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        if ($request->hasFile('hero_image')) {
            $data['hero_image'] = $request->file('hero_image')->store('page-heroes', 'public');
        }

        CmsPage::create($data);

        return redirect()->route('admin.cms.pages.index')->with('status', 'Pagina cadastrada com sucesso.');
    }

    public function edit(CmsPage $page)
    {
        return view('portal.admin.cms.pages.form', compact('page'));
    }

    public function update(Request $request, CmsPage $page)
    {
        $data = $this->validatedData($request, $page);

        if ($request->hasFile('hero_image')) {
            if ($page->hero_image) {
                Storage::disk('public')->delete($page->hero_image);
            }

            $data['hero_image'] = $request->file('hero_image')->store('page-heroes', 'public');
        }

        $page->update($data);

        return redirect()->route('admin.cms.pages.index')->with('status', 'Pagina atualizada com sucesso.');
    }

    public function destroy(CmsPage $page)
    {
        if ($page->hero_image) {
            Storage::disk('public')->delete($page->hero_image);
        }

        $page->delete();

        return redirect()->route('admin.cms.pages.index')->with('status', 'Pagina removida com sucesso.');
    }

    private function validatedData(Request $request, ?CmsPage $page = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('cms_pages', 'slug')->ignore($page),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'min:20'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
            'show_in_menu' => ['nullable', 'boolean'],
            'hero_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ], [
            'slug.regex' => 'O endereço deve conter apenas letras minúsculas, números e hífens.',
            'slug.unique' => 'Já existe outra página com este endereço.',
        ], ['slug' => 'endereço']);

        // O slug NAO acompanha o titulo depois que a pagina existe.
        //
        // Antes ele era regerado a cada gravacao. Como as paginas
        // institucionais sao encontradas pelo slug, renomear o titulo
        // desligava a pagina da rota em silencio: o portal passava a mostrar o
        // texto embutido no Blade e ninguem percebia. Foi assim que a "Sobre"
        // ficou desligada. Agora o endereco so muda se o editor mudar de
        // proposito, no campo dedicado.
        $data['slug'] = match (true) {
            filled($data['slug'] ?? null) => Str::slug($data['slug']),
            $page !== null => $page->slug,
            default => $this->uniqueSlug($data['title']),
        };

        $data['position'] = $data['position'] ?? 0;
        $data['is_published'] = $request->boolean('is_published');
        $data['show_in_menu'] = $request->boolean('show_in_menu');

        return $data;
    }

    private function uniqueSlug(string $title, ?CmsPage $page = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 2;

        while (CmsPage::where('slug', $slug)->when($page, fn ($query) => $query->whereKeyNot($page->id))->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
