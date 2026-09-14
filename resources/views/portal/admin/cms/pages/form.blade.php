@extends('layouts.admin')

@section('title', ($page->exists ? 'Editar página' : 'Nova página') . ' | Portal Associação Tech PG')
@section('page_heading', $page->exists ? 'Editar página' : 'Nova página')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-wide text-brand-500">CMS do portal</span>
            <h2 class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $page->exists ? 'Editar página' : 'Nova página' }}</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Páginas publicadas ficam disponiveis em /página/slug e podem aparecer no menu principal.</p>
        </div>
        <a class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" href="{{ route('admin.cms.pages.index') }}">Voltar</a>
    </div>


    <form class="space-y-6" method="post" action="{{ $page->exists ? route('admin.cms.pages.update', $page) : route('admin.cms.pages.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($page->exists) @method('put') @endif

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <label class="block lg:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Titulo</span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="title" value="{{ old('title', $page->title) }}" required>
                </label>
                <label class="block lg:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Endereço da página</span>
                    <div class="flex items-center gap-2">
                        <span class="shrink-0 text-sm text-gray-400">/pagina/</span>
                        <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                               name="slug" value="{{ old('slug', $page->slug) }}"
                               placeholder="{{ $page->exists ? '' : 'gerado a partir do título' }}">
                    </div>
                    <span class="mt-1.5 block text-xs text-gray-500 dark:text-gray-400">
                        @if ($page->exists)
                            O endereço não muda sozinho quando você altera o título. Só mude aqui se souber o que está fazendo — links antigos param de funcionar.
                        @else
                            Deixe em branco para gerar a partir do título.
                        @endif
                    </span>
                </label>

                {{-- Aviso forte: mexer neste slug desliga a rota institucional. --}}
                @if (\App\Support\InstitutionalPages::isInstitutional($page->slug))
                    <div class="lg:col-span-2 rounded-lg border border-warning-500/30 bg-warning-50 px-4 py-3 text-sm text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">
                        <strong>Página institucional:</strong> este conteúdo alimenta
                        <a class="underline" href="{{ \App\Support\InstitutionalPages::urlFor($page->slug) }}" target="_blank" rel="noopener">{{ \App\Support\InstitutionalPages::urlFor($page->slug) }}</a>
                        no site. Mudar o endereço acima desliga essa página — o portal volta a mostrar o texto padrão, sem avisar.
                    </div>
                @endif

                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Ordem no menu</span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="position" type="number" min="0" value="{{ old('position', $page->position ?? 0) }}">
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Imagem do topo</span>
                    <input class="block w-full rounded-lg border border-gray-300 bg-transparent text-sm text-gray-800 file:mr-5 file:border-0 file:bg-gray-100 file:px-4 file:py-3 file:text-sm file:font-medium file:text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="hero_image" type="file" accept=".jpg,.jpeg,.png,.webp,image/*">
                </label>
                <label class="block lg:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Resumo</span>
                    <textarea class="min-h-24 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="excerpt">{{ old('excerpt', $page->excerpt) }}</textarea>
                </label>
                <label class="block lg:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Conteudo</span>
                    <textarea class="min-h-72 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="body" data-editor required>{{ old('body', $page->body) }}</textarea>
                </label>
                <div class="grid gap-3 lg:col-span-2">
                    <label class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm font-medium text-gray-700 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->exists ? $page->is_published : false))> Publicar página</label>
                    <label class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm font-medium text-gray-700 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300"><input type="checkbox" name="show_in_menu" value="1" @checked(old('show_in_menu', $page->exists ? $page->show_in_menu : false))> Exibir no menu principal</label>
                </div>
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <a class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" href="{{ route('admin.cms.pages.index') }}">Cancelar</a>
            <button class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600" type="submit">Salvar página</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    @vite('resources/js/editor.js')
@endpush
