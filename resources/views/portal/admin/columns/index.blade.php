@extends('layouts.admin')

@section('title', 'Colunas')
@section('page_heading', 'Colunas')

@section('content')
@php
    $statusAtual = request()->query('status');
    $filtros = [
        ['rotulo' => 'Todas', 'valor' => null, 'contagem' => $statusCounts['all']],
        ['rotulo' => 'Em análise', 'valor' => 'pending', 'contagem' => $statusCounts['pending']],
        ['rotulo' => 'Aprovadas', 'valor' => 'approved', 'contagem' => $statusCounts['approved']],
        ['rotulo' => 'Rejeitadas', 'valor' => 'rejected', 'contagem' => $statusCounts['rejected']],
    ];
@endphp

<x-admin.card :padding="false">
    <x-slot:title>{{ $columns->total() }} {{ $columns->total() === 1 ? 'coluna' : 'colunas' }}</x-slot:title>
    <x-slot:subtitle>Textos assinados pelos colunistas do portal.</x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('admin.columns.columnists')" icon="users">Ver colunistas</x-admin.button>
    </x-slot:actions>

    <div class="space-y-4 border-b border-gray-100 p-5 dark:border-gray-800">
        <x-admin.filter-bar :action="route('admin.columns.index')" placeholder="Buscar pelo título da coluna..." />

        <div class="flex flex-wrap gap-2">
            @foreach ($filtros as $filtro)
                <a href="{{ route('admin.columns.index', array_filter(['status' => $filtro['valor'], 'q' => request()->query('q')])) }}"
                   @class([
                       'rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                       'bg-brand-500 text-white' => $statusAtual === $filtro['valor'],
                       'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-white/[0.06] dark:text-gray-300' => $statusAtual !== $filtro['valor'],
                   ])>
                    {{ $filtro['rotulo'] }} ({{ $filtro['contagem'] }})
                </a>
            @endforeach
        </div>
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($columns as $column)
            <div class="flex flex-col gap-3 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0 space-y-1">
                    <strong class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $column->title }}</strong>
                    <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                        {{ $column->columnist?->byline ?? 'Sem colunista' }}
                    </p>
                    <p class="text-xs text-gray-400">
                        Enviada em {{ $column->created_at->format('d/m/Y \à\s H:i') }}
                        @if ($column->published_at) &middot; publicada em {{ $column->published_at->format('d/m/Y') }} @endif
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <x-admin.status-badge :status="$column->status" :published="$column->is_published" />
                    <x-admin.button :href="route('admin.columns.show', $column)" icon="eye">Analisar</x-admin.button>
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="news" title="Nenhuma coluna ainda."
                                 description="Marque um membro ou empresa como colunista para que possam escrever." />
        @endforelse
    </div>

    @if ($columns->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $columns->links() }}</div>
    @endif
</x-admin.card>
@endsection
