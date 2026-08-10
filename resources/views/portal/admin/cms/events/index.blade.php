@extends('layouts.admin')

@section('title', 'Eventos | Portal Associação Tech PG')
@section('page_heading', 'Eventos')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-wide text-brand-500">CMS do portal</span>
            <h2 class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">Eventos</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Cadastre encontros, workshops, demo days, fóruns e agendas da associação.</p>
        </div>
        <a class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600" href="{{ route('admin.cms.events.create') }}">Novo evento</a>
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm font-medium text-success-700">{{ session('status') }}</div>
    @endif

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-white/[0.02]">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Evento</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Tipo</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Data</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($events as $event)
                        <tr>
                            <td class="px-5 py-4">
                                <strong class="block text-sm font-semibold text-gray-800 dark:text-white/90">{{ $event->title }}</strong>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $event->location ?: 'Local a definir' }}</span>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $event->type }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $event->event_date?->format('d/m/Y') ?: '-' }}</td>
                            <td class="px-5 py-4">
                                @if ($event->is_published)
                                    <span class="inline-flex rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700">Publicado</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">Rascunho</span>
                                @endif
                                @if ($event->is_featured)
                                    <span class="ml-1 inline-flex rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-700">Destaque</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    @if ($event->is_published)
                                        <a class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]" href="{{ route('events.show', $event) }}" target="_blank">Ver</a>
                                    @endif
                                    <a class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]" href="{{ route('admin.cms.events.edit', $event) }}">Editar</a>
                                    <form method="post" action="{{ route('admin.cms.events.destroy', $event) }}">
                                        @csrf
                                        @method('delete')
                                        <button class="rounded-lg border border-error-200 px-3 py-2 text-sm font-medium text-error-600 hover:bg-error-50" type="submit">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400">Nenhum evento cadastrado ainda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
