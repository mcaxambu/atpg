@extends('layouts.admin')

@section('title', ($item->exists ? 'Editar ' : 'Novo ') . $meta['singular'] . ' | CMS')
@section('page_heading', $item->exists ? 'Editar ' . $meta['singular'] : 'Novo ' . $meta['singular'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-wide text-brand-500">CMS do Website</span>
            <h2 class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $item->exists ? 'Editar' : 'Novo' }} {{ $meta['singular'] }}</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $meta['description'] }}</p>
        </div>
        <a class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]" href="{{ route('admin.cms.modules.index', $module) }}">Voltar</a>
    </div>


    <form class="space-y-6" method="post" action="{{ $item->exists ? route('admin.cms.modules.update', [$module, $item]) : route('admin.cms.modules.store', $module) }}" enctype="multipart/form-data">
        @csrf
        @if ($item->exists) @method('put') @endif

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Titulo</span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="title" value="{{ old('title', $item->title) }}" required>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Subtitulo</span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="subtitle" value="{{ old('subtitle', $item->subtitle) }}">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Imagem</span>
                    <input class="block w-full rounded-lg border border-gray-300 bg-transparent text-sm text-gray-800 file:mr-5 file:border-0 file:bg-gray-100 file:px-4 file:py-3 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:file:bg-white/[0.03] dark:file:text-gray-300" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,.svg,image/*">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Ordem</span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="position" type="number" min="0" value="{{ old('position', $item->position ?? 0) }}">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Link</span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="link_url" value="{{ old('link_url', $item->link_url) }}" placeholder="https://">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Texto do botao</span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="button_label" value="{{ old('button_label', $item->button_label) }}" placeholder="Saiba mais">
                </label>
                <label class="block lg:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição / Conteudo</span>
                    <textarea class="min-h-40 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="description" rows="7">{{ old('description', $item->description) }}</textarea>
                </label>
            </div>

            @if ($item->image_path)
                <div class="mt-5 flex items-center gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                    <img class="h-16 w-24 rounded-lg bg-white object-cover" src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->title }}">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Imagem atual cadastrada</span>
                </div>
            @endif

            <label class="mt-5 flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm font-medium text-gray-700 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300">
                <input class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->exists ? $item->is_active : true))>
                Publicado no portal
            </label>
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <a class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]" href="{{ route('admin.cms.modules.index', $module) }}">Cancelar</a>
            <button class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600" type="submit">Salvar</button>
        </div>
    </form>
</div>
@endsection
