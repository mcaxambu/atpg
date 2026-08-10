@extends('layouts.admin')

@section('title', $title . ' | CMS')
@section('page_heading', $title)

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-wide text-brand-500">CMS do Website</span>
            <h2 class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h2>
            <p class="mt-2 max-w-3xl text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
        </div>
        <a class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]" href="{{ route('admin.cms.dashboard') }}">Voltar ao CMS</a>
    </div>

    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Modulo preparado para CRUD</h3>
                <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                    Esta tela já reserva o espaco administrativo deste modulo. Na proxima etapa podemos adicionar tabela,
                    formulário, upload de imagens, ordenacao, status de publicação e relacionamento com as páginas do portal.
                </p>
            </div>
            <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/[0.03]">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">Slug interno</span>
                <strong class="mt-2 block text-sm text-gray-800 dark:text-white/90">{{ $module }}</strong>
            </div>
        </div>
    </section>
</div>
@endsection
