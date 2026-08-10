@extends('layouts.admin')

@section('title', 'Páginas | Portal Associação Tech PG')
@section('page_heading', 'Páginas CMS')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-wide text-brand-500">CMS do portal</span>
            <h2 class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">Páginas</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Crie páginas institucionais sem alterar codigo: regulamentos, comitês, projetos, editais e documentos.</p>
        </div>
        <a class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600" href="{{ route('admin.cms.pages.create') }}">Nova página</a>
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm font-medium text-success-700">{{ session('status') }}</div>
    @endif

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-white/[0.02]">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Página</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Slug</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Menu</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($pages as $page)
                        <tr>
                            <td class="px-5 py-4"><strong class="block text-sm font-semibold text-gray-800 dark:text-white/90">{{ $page->title }}</strong><span class="block max-w-xl truncate text-xs text-gray-500 dark:text-gray-400">{{ $page->excerpt_text }}</span></td>
                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">/página/{{ $page->slug }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $page->show_in_menu ? 'Sim' : 'Não' }}</td>
                            <td class="px-5 py-4">
                                @if ($page->is_published)
                                    <span class="inline-flex rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700">Publicada</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">Rascunho</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    @if ($page->is_published)
                                        <a class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" href="{{ route('pages.show', $page) }}" target="_blank">Ver</a>
                                    @endif
                                    <a class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" href="{{ route('admin.cms.pages.edit', $page) }}">Editar</a>
                                    <form method="post" action="{{ route('admin.cms.pages.destroy', $page) }}">
                                        @csrf
                                        @method('delete')
                                        <button class="rounded-lg border border-error-200 px-3 py-2 text-sm font-medium text-error-600 hover:bg-error-50" type="submit">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400">Nenhuma página cadastrada ainda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
