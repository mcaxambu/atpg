@extends('layouts.admin')

@section('title', 'Vagas pendentes')
@section('page_heading', 'Vagas aguardando análise')

@section('content')
<x-admin.card :padding="false">
    <x-slot:title>{{ $jobs->total() }} na fila</x-slot:title>
    <x-slot:subtitle>Vagas enviadas pelas empresas que ainda não entraram no portal.</x-slot:subtitle>

    <div class="border-b border-gray-100 p-5 dark:border-gray-800">
        <x-admin.filter-bar :action="route('admin.jobs.pending')" placeholder="Buscar por título ou empresa..." />
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($jobs as $job)
            <div class="flex flex-col gap-3 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0 space-y-1">
                    <strong class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $job->title }}</strong>
                    <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                        {{ $job->company?->name }} &middot; {{ $job->type->label() }}
                        &middot; {{ $job->workplace->label() }} &middot; {{ $job->locationLabel() }}
                    </p>
                    <p class="text-xs text-gray-400">Enviada em {{ $job->created_at->format('d/m/Y \à\s H:i') }}</p>
                </div>

                <div class="flex shrink-0 gap-2">
                    <x-admin.button :href="route('admin.jobs.show', $job)" icon="eye">Analisar</x-admin.button>
                    <x-admin.confirm-form :action="route('admin.jobs.approve', $job)" method="patch"
                                          variant="success" icon="check"
                                          message="Aprovar e publicar &quot;{{ $job->title }}&quot; no portal?">Aprovar</x-admin.confirm-form>
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="check" title="Nenhuma vaga pendente."
                                 description="As vagas enviadas pelas empresas chegam nesta fila." />
        @endforelse
    </div>

    @if ($jobs->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $jobs->links() }}</div>
    @endif
</x-admin.card>
@endsection
