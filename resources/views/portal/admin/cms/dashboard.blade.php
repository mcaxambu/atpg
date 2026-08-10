@extends('layouts.admin')

@section('title', 'CMS | Portal Associação Tech PG')
@section('page_heading', 'CMS')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-wide text-brand-500">Conteúdo do portal</span>
            <h2 class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">Central CMS</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Gerencie a home, notícias, eventos, páginas e modulos institucionais sem alterar codigo.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" href="{{ route('admin.cms.modules.create', 'banners') }}">Novo banner</a>
            <a class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" href="{{ route('admin.cms.modules.create', 'destaques') }}">Novo destaque</a>
            <a class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" href="{{ route('admin.cms.pages.create') }}">Nova página</a>
            <a class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" href="{{ route('admin.cms.settings.edit') }}">Configurações</a>
            <a class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600" href="{{ route('admin.cms.posts.create') }}">Nova notícia</a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:-translate-y-0.5 hover:shadow-theme-md dark:border-gray-800 dark:bg-white/[0.03]" href="{{ route('admin.cms.posts.index') }}">
            <span class="text-sm text-gray-500 dark:text-gray-400">Notícias</span>
            <strong class="mt-2 block text-3xl font-semibold text-gray-800 dark:text-white/90">{{ $posts->count() }}</strong>
            <span class="mt-2 block text-xs font-medium text-gray-500 dark:text-gray-400">{{ $posts->where('is_published', true)->count() }} publicadas</span>
        </a>
        <a class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:-translate-y-0.5 hover:shadow-theme-md dark:border-gray-800 dark:bg-white/[0.03]" href="{{ route('admin.cms.events.index') }}">
            <span class="text-sm text-gray-500 dark:text-gray-400">Eventos</span>
            <strong class="mt-2 block text-3xl font-semibold text-gray-800 dark:text-white/90">{{ $events->count() }}</strong>
            <span class="mt-2 block text-xs font-medium text-gray-500 dark:text-gray-400">{{ $events->where('is_published', true)->count() }} publicados</span>
        </a>
        <a class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:-translate-y-0.5 hover:shadow-theme-md dark:border-gray-800 dark:bg-white/[0.03]" href="{{ route('admin.cms.pages.index') }}">
            <span class="text-sm text-gray-500 dark:text-gray-400">Páginas</span>
            <strong class="mt-2 block text-3xl font-semibold text-gray-800 dark:text-white/90">{{ $pages->count() }}</strong>
            <span class="mt-2 block text-xs font-medium text-gray-500 dark:text-gray-400">{{ $pages->where('show_in_menu', true)->count() }} no menu</span>
        </a>
        <a class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:-translate-y-0.5 hover:shadow-theme-md dark:border-gray-800 dark:bg-white/[0.03]" href="{{ route('admin.cms.modules.index', 'destaques') }}">
            <span class="text-sm text-gray-500 dark:text-gray-400">Itens modulares</span>
            <strong class="mt-2 block text-3xl font-semibold text-gray-800 dark:text-white/90">{{ $cmsItems->count() }}</strong>
            <span class="mt-2 block text-xs font-medium text-gray-500 dark:text-gray-400">{{ $cmsItems->where('is_active', true)->count() }} publicados</span>
        </a>
    </div>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Modulos do website</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Conteúdos que alimentam secoes da home e páginas institucionais.</p>
            </div>
            <a class="text-sm font-medium text-brand-500 hover:text-brand-600" href="{{ route('home') }}">Ver portal público</a>
        </div>
        @php
            $modules = [
                'banners' => 'Banners',
                'destaques' => 'Destaques',
                'projetos' => 'Projetos',
                'depoimentos' => 'Depoimentos',
                'parceiros' => 'Parceiros',
                'mídia-kit' => 'Mídia Kit',
                'categorias' => 'Categorias',
            ];
        @endphp
        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($modules as $slug => $label)
                <a class="rounded-xl border border-gray-200 p-4 transition hover:-translate-y-0.5 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/[0.03]" href="{{ route('admin.cms.modules.index', $slug) }}">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</span>
                    <strong class="mt-2 block text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $moduleCounts[$slug] ?? 0 }}</strong>
                    <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">itens cadastrados</span>
                </a>
            @endforeach
        </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1.15fr_.85fr]">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Fluxo editorial recomendado</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Um caminho simples para manter o portal com cara de produto vivo.</p>
                </div>
                <a class="text-sm font-medium text-brand-500 hover:text-brand-600" href="{{ route('admin.cms.settings.edit') }}">Ajustar identidade</a>
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-brand-500 text-sm font-bold text-white">1</span>
                    <strong class="mt-4 block text-sm text-gray-800 dark:text-white/90">Banner principal</strong>
                    <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">Atualize a chamada da home conforme a campanha ou frente institucional.</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-brand-500 text-sm font-bold text-white">2</span>
                    <strong class="mt-4 block text-sm text-gray-800 dark:text-white/90">Notícias e eventos</strong>
                    <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">Publique agenda, comunicados, oportunidades e entregas da associação.</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-brand-500 text-sm font-bold text-white">3</span>
                    <strong class="mt-4 block text-sm text-gray-800 dark:text-white/90">Projetos e parceiros</strong>
                    <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">Mostre iniciativas, patrocinadores e conexões que validam o ecossistema.</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Atalhos de alto impacto</h3>
            <div class="mt-4 grid gap-3">
                <a class="flex items-center justify-between rounded-xl border border-gray-200 p-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]" href="{{ route('admin.cms.modules.index', 'depoimentos') }}">Depoimentos <span>editar</span></a>
                <a class="flex items-center justify-between rounded-xl border border-gray-200 p-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]" href="{{ route('admin.cms.modules.index', 'parceiros') }}">Parceiros <span>editar</span></a>
                <a class="flex items-center justify-between rounded-xl border border-gray-200 p-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]" href="{{ route('admin.cms.modules.index', 'projetos') }}">Projetos <span>editar</span></a>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Checklist editorial</h3>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-gray-200 p-4 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-400">Notícias devem ter resumo curto, categoria clara e imagem quando possível.</div>
            <div class="rounded-xl border border-gray-200 p-4 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-400">Eventos precisam de data, local e link de inscricao quando houver.</div>
            <div class="rounded-xl border border-gray-200 p-4 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-400">Páginas institucionais no menu devem ser poucas e realmente importantes.</div>
            <div class="rounded-xl border border-gray-200 p-4 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-400">Rascunhos ficam salvos no CMS sem aparecer no portal público.</div>
        </div>
    </section>
</div>
@endsection
