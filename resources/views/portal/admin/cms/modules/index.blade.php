@extends('layouts.admin')

@section('title', $meta['title'] . ' | CMS')
@section('page_heading', $meta['title'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-wide text-brand-500">CMS do Website</span>
            <h2 class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $meta['title'] }}</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $meta['description'] }}</p>
        </div>
        <a class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600" href="{{ route('admin.cms.modules.create', $module) }}">Novo item</a>
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm font-medium text-success-700">{{ session('status') }}</div>
    @endif

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Itens cadastrados</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-white/[0.02]">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Item</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Ordem</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($items as $item)
                        <tr>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-xl bg-brand-50 text-sm font-semibold text-brand-600 dark:bg-brand-500/15">
                                        @if ($item->image_path)
                                            <img class="h-full w-full bg-white object-cover" src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->title }}">
                                        @else
                                            {{ str($item->title)->substr(0, 2)->upper() }}
                                        @endif
                                    </div>
                                    <div>
                                        <strong class="block text-sm font-semibold text-gray-800 dark:text-white/90">{{ $item->title }}</strong>
                                        @if ($item->subtitle)
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $item->subtitle }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $item->position }}</td>
                            <td class="px-5 py-4">
                                @if ($item->is_active)
                                    <span class="inline-flex rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700">Publicado</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">Rascunho</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]" href="{{ route('admin.cms.modules.edit', [$module, $item]) }}">Editar</a>
                                    <form method="post" action="{{ route('admin.cms.modules.destroy', [$module, $item]) }}">
                                        @csrf
                                        @method('delete')
                                        <button class="rounded-lg border border-error-200 px-3 py-2 text-sm font-medium text-error-600 hover:bg-error-50" type="submit">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Nenhum item cadastrado neste modulo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
