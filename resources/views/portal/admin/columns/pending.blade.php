@extends('layouts.admin')

@section('title', 'Colunas pendentes')
@section('page_heading', 'Colunas aguardando análise')

@section('content')
<x-admin.card :padding="false">
    <x-slot:title>{{ $columns->total() }} na fila</x-slot:title>
    <x-slot:subtitle>Textos enviados pelos colunistas que ainda não entraram no portal.</x-slot:subtitle>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($columns as $column)
            <div class="flex flex-col gap-3 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0 space-y-1">
                    <strong class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $column->title }}</strong>
                    <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $column->columnist?->byline }}</p>
                    <p class="text-xs text-gray-400">Enviada em {{ $column->created_at->format('d/m/Y \à\s H:i') }}</p>
                </div>

                <div class="flex shrink-0 gap-2">
                    <x-admin.button :href="route('admin.columns.show', $column)" icon="eye">Ler e decidir</x-admin.button>
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="check" title="Nenhuma coluna pendente."
                                 description="Os textos enviados pelos colunistas chegam nesta fila." />
        @endforelse
    </div>

    @if ($columns->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $columns->links() }}</div>
    @endif
</x-admin.card>
@endsection
