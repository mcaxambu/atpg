@extends('layouts.admin')

@section('title', 'Vagas')
@section('page_heading', 'Vagas das empresas')

@section('content')
@php
    $statusAtual = request()->query('status');
    $filtros = [
        ['rotulo' => 'Todas', 'valor' => null, 'contagem' => $statusCounts['all']],
        ['rotulo' => 'Pendentes', 'valor' => 'pending', 'contagem' => $statusCounts['pending']],
        ['rotulo' => 'Aprovadas', 'valor' => 'approved', 'contagem' => $statusCounts['approved']],
        ['rotulo' => 'Rejeitadas', 'valor' => 'rejected', 'contagem' => $statusCounts['rejected']],
    ];
@endphp

<x-admin.card :padding="false">
    <x-slot:title>{{ $jobs->total() }} {{ $jobs->total() === 1 ? 'vaga' : 'vagas' }}</x-slot:title>
    <x-slot:subtitle>Publicadas pelas empresas associadas. As candidaturas ficam com a empresa.</x-slot:subtitle>

    <div class="space-y-4 border-b border-gray-100 p-5 dark:border-gray-800">
        <x-admin.filter-bar :action="route('admin.jobs.index')" placeholder="Buscar por título, cidade ou empresa..." />

        <div class="flex flex-wrap gap-2">
            @foreach ($filtros as $filtro)
                <a href="{{ route('admin.jobs.index', array_filter(['status' => $filtro['valor'], 'q' => request()->query('q')])) }}"
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
        @forelse ($jobs as $job)
            <div class="flex flex-col gap-3 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0 space-y-1">
                    <strong class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $job->title }}</strong>
                    <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                        {{ $job->company?->name }} &middot; {{ $job->type->label() }} &middot; {{ $job->locationLabel() }}
                    </p>
                    <p class="text-xs text-gray-400">
                        {{ $job->applications_count }} {{ $job->applications_count === 1 ? 'candidatura' : 'candidaturas' }}
                        &middot; {{ $job->situationLabel() }}
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <x-admin.status-badge :status="$job->status" :published="$job->is_active" />
                    <x-admin.button :href="route('admin.jobs.show', $job)" icon="eye">Analisar</x-admin.button>
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="file" title="Nenhuma vaga encontrada."
                                 description="As vagas cadastradas pelas empresas aparecem aqui." />
        @endforelse
    </div>

    @if ($jobs->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $jobs->links() }}</div>
    @endif
</x-admin.card>
@endsection
