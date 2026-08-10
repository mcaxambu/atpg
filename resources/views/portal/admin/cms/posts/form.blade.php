@extends('layouts.admin')

@section('title', ($post->exists ? 'Editar notícia' : 'Nova notícia') . ' | Portal Associação Tech PG')
@section('page_heading', $post->exists ? 'Editar notícia' : 'Nova notícia')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-wide text-brand-500">CMS do portal</span>
            <h2 class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $post->exists ? 'Editar notícia' : 'Nova notícia' }}</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Atualize comunicados, eventos, conquistas, parcerias e conteúdos institucionais.</p>
        </div>
        <a class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]" href="{{ route('admin.cms.posts.index') }}">Voltar</a>
    </div>


    <form class="space-y-6" method="post" action="{{ $post->exists ? route('admin.cms.posts.update', $post) : route('admin.cms.posts.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($post->exists) @method('put') @endif

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="mb-5">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Conteudo</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">O titulo gera automaticamente a URL pública da notícia.</p>
            </div>
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <label class="block lg:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Titulo</span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="title" value="{{ old('title', $post->title) }}" required>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Categoria</span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="category" value="{{ old('category', $post->category ?: 'Notícia') }}" required>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data de publicação</span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="published_at" type="datetime-local" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
                </label>
                <label class="block lg:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Resumo</span>
                    <textarea class="min-h-24 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="excerpt" maxlength="500">{{ old('excerpt', $post->excerpt) }}</textarea>
                </label>
                <label class="block lg:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Texto da notícia</span>
                    <textarea class="min-h-72 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="body" required>{{ old('body', $post->body) }}</textarea>
                    <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">Aceita Markdown, igual às atas: <code>## subtítulo</code>, <code>- lista</code>, <code>1. numerada</code>, <code>**negrito</code><code>**</code>, <code>[link](url)</code>, <code>&gt; citação</code>. Texto corrido também funciona; separe parágrafos com uma linha em branco.</span>
                </label>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="mb-5">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Publicação</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Controle capa e visibilidade no portal.</p>
            </div>
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Imagem de capa</span>
                    <input class="block w-full rounded-lg border border-gray-300 bg-transparent text-sm text-gray-800 file:mr-5 file:border-0 file:bg-gray-100 file:px-4 file:py-3 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:file:bg-white/[0.03] dark:file:text-gray-300" name="cover_image" type="file" accept=".jpg,.jpeg,.png,.webp,image/*">
                </label>
                <label class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm font-medium text-gray-700 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300">
                    <input class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" type="checkbox" name="is_published" value="1" @checked(old('is_published', $post->exists ? $post->is_published : false))>
                    Publicar no portal
                </label>
            </div>

            @if ($post->cover_image)
                <div class="mt-5 flex items-center gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                    <img class="h-20 w-32 rounded-lg object-cover" src="{{ asset('storage/' . $post->cover_image) }}" alt="Capa {{ $post->title }}">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Capa atual</span>
                </div>
            @endif
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <a class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]" href="{{ route('admin.cms.posts.index') }}">Cancelar</a>
            <button class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600" type="submit">Salvar notícia</button>
        </div>
    </form>
</div>
@endsection
