<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CmsModuleController extends Controller
{
    public static function modules(): array
    {
        return [
            'categorias' => ['title' => 'Categorias', 'singular' => 'Categoria', 'description' => 'Categorias para organizar noticias, paginas e conteudos institucionais.'],
            'banners' => ['title' => 'Banners', 'singular' => 'Banner', 'description' => 'Banners principais, chamadas visuais e imagens de campanha do portal.'],
            'destaques' => ['title' => 'Destaques', 'singular' => 'Destaque', 'description' => 'Conteudos, empresas, eventos e links priorizados no portal.'],
            'depoimentos' => ['title' => 'Depoimentos', 'singular' => 'Depoimento', 'description' => 'Falas de associados, empresas, parceiros e liderancas do ecossistema.'],
            'projetos' => ['title' => 'Projetos', 'singular' => 'Projeto', 'description' => 'Projetos institucionais, iniciativas coletivas e frentes de trabalho.'],
            'missao-visao' => ['title' => 'Missão e visão', 'singular' => 'Bloco', 'description' => 'Os dois blocos de propósito exibidos na página Sobre. Use o título "Missão" e "Visão".'],
            'valores' => ['title' => 'Valores', 'singular' => 'Valor', 'description' => 'Cards de valores da associação, exibidos na página Sobre.'],
            'parceiros' => ['title' => 'Parceiros', 'singular' => 'Parceiro', 'description' => 'Apoiadores, entidades parceiras, patrocinadores e conexoes institucionais.'],
        ];
    }

    public function index(string $module)
    {
        $meta = $this->meta($module);

        return view('portal.admin.cms.modules.index', [
            'module' => $module,
            'meta' => $meta,
            'items' => CmsItem::module($module)->orderBy('position')->latest()->get(),
        ]);
    }

    public function create(string $module)
    {
        return view('portal.admin.cms.modules.form', [
            'module' => $module,
            'meta' => $this->meta($module),
            'item' => new CmsItem(['module' => $module, 'is_active' => true]),
        ]);
    }

    public function store(Request $request, string $module)
    {
        $this->meta($module);
        $data = $this->validatedData($request, $module);
        $data['module'] = $module;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store("cms/{$module}", 'public');
        }

        CmsItem::create($data);

        return redirect()->route('admin.cms.modules.index', $module)->with('status', 'Item cadastrado com sucesso.');
    }

    public function edit(string $module, CmsItem $item)
    {
        $this->ensureModule($module, $item);

        return view('portal.admin.cms.modules.form', [
            'module' => $module,
            'meta' => $this->meta($module),
            'item' => $item,
        ]);
    }

    public function update(Request $request, string $module, CmsItem $item)
    {
        $this->ensureModule($module, $item);
        $data = $this->validatedData($request, $module, $item);

        if ($request->hasFile('image')) {
            if ($item->image_path) {
                Storage::disk('public')->delete($item->image_path);
            }

            $data['image_path'] = $request->file('image')->store("cms/{$module}", 'public');
        }

        $item->update($data);

        return redirect()->route('admin.cms.modules.index', $module)->with('status', 'Item atualizado com sucesso.');
    }

    public function destroy(string $module, CmsItem $item)
    {
        $this->ensureModule($module, $item);

        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }

        $item->delete();

        return redirect()->route('admin.cms.modules.index', $module)->with('status', 'Item removido com sucesso.');
    }

    private function validatedData(Request $request, string $module, ?CmsItem $item = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'link_url' => ['nullable', 'string', 'max:255'],
            'button_label' => ['nullable', 'string', 'max:80'],
            'position' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:3072'],
        ]);

        $data['slug'] = $this->uniqueSlug($module, $data['title'], $item);
        $data['position'] = $data['position'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        if ($data['is_active'] && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if (! $data['is_active']) {
            $data['published_at'] = null;
        }

        return $data;
    }

    private function meta(string $module): array
    {
        abort_unless(isset(self::modules()[$module]), 404);

        return self::modules()[$module];
    }

    private function ensureModule(string $module, CmsItem $item): void
    {
        $this->meta($module);
        abort_unless($item->module === $module, 404);
    }

    private function uniqueSlug(string $module, string $title, ?CmsItem $item = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 2;

        while (CmsItem::module($module)->where('slug', $slug)->when($item, fn ($query) => $query->whereKeyNot($item->id))->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
