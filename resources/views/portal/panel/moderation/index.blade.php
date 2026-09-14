@extends('layouts.company')

@section('title', 'Moderar colunas')

@section('content')
@php
    $statusAtual = request()->query('status');
    $filtros = [
        ['rotulo' => 'Em análise', 'valor' => 'pending'],
        ['rotulo' => 'Aprovadas', 'valor' => 'approved'],
        ['rotulo' => 'Devolvidas', 'valor' => 'rejected'],
        ['rotulo' => 'Todas', 'valor' => null],
    ];
@endphp

<div>
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Moderar colunas</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        A associação delegou a esta empresa a análise das colunas do portal.
        São textos de {{ $colunistas }} {{ $colunistas === 1 ? 'colunista' : 'colunistas' }};
        {{ $pendentes }} {{ $pendentes === 1 ? 'aguarda' : 'aguardam' }} decisão.
    </p>
</div>

{{-- Deixa claro que aqui não são os dados da própria empresa. --}}
<div class="rounded-lg border border-warning-500/30 bg-warning-50 px-4 py-3 text-xs text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-300">
    Estes textos são de outros associados. O que você aprovar aqui vai ao ar no portal da associação,
    assinado por quem escreveu.
</div>

<x-admin.card :padding="false">
    <x-slot:title>{{ $columns->total() }} {{ $columns->total() === 1 ? 'coluna' : 'colunas' }}</x-slot:title>

    <div class="border-b border-gray-100 p-5 dark:border-gray-800">
        <div class="flex flex-wrap gap-2">
            @foreach ($filtros as $filtro)
                <a href="{{ route('empresa.moderacao.index', array_filter(['status' => $filtro['valor']])) }}"
                   @class([
                       'rounded-full px-3 py-1.5 text-xs font-medium transition-colors',
                       'bg-brand-500 text-white' => $statusAtual === $filtro['valor'],
                       'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-white/[0.06] dark:text-gray-300' => $statusAtual !== $filtro['valor'],
                   ])>{{ $filtro['rotulo'] }}</a>
            @endforeach
        </div>
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($columns as $column)
            <div class="flex flex-col gap-3 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0 space-y-1">
                    <strong class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $column->title }}</strong>
                    <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                        por {{ $column->columnist?->byline ?? 'sem colunista' }}
                    </p>
                    <p class="text-xs text-gray-400">Enviada em {{ $column->created_at->format('d/m/Y \à\s H:i') }}</p>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-white/[0.06] dark:text-gray-300">
                        @if ($column->isPending()) Em análise
                        @elseif ($column->isRejected()) Devolvida
                        @elseif ($column->is_published) No ar
                        @else Aprovada, fora do ar
                        @endif
                    </span>
                    <x-admin.button :href="route('empresa.moderacao.show', $column->id)" icon="eye">Ler e decidir</x-admin.button>
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="check" title="Nenhuma coluna nesta situação."
                                 description="Os textos enviados pelos colunistas aparecem aqui." />
        @endforelse
    </div>

    @if ($columns->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $columns->links() }}</div>
    @endif
</x-admin.card>
@endsection
