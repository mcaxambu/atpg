@extends('layouts.company')

@section('title', 'Candidaturas | '.$job->title)

@section('content')
<div>
    <a href="{{ route('empresa.vagas.index') }}" class="text-sm text-brand-600 hover:underline dark:text-brand-400">&larr; Voltar para as vagas</a>
    <h1 class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Candidaturas</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $job->title }}</p>
</div>

<div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-600 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
    Estes são dados pessoais de candidatos. Use-os apenas para este processo seletivo e apague
    a candidatura quando ela não for mais necessária — o currículo é apagado junto.
</div>

<x-admin.card :padding="false">
    <x-slot:title>{{ $applications->total() }} {{ $applications->total() === 1 ? 'candidatura' : 'candidaturas' }}</x-slot:title>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($applications as $application)
            <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0 space-y-1">
                    <strong class="flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-white/90">
                        {{ $application->name }}
                        @unless ($application->viewed_at)
                            <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-bold text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">NOVA</span>
                        @endunless
                    </strong>
                    <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                        {{ $application->email }}@if ($application->phone) &middot; {{ $application->phone }}@endif
                    </p>
                    <p class="text-xs text-gray-400">
                        Recebida em {{ $application->created_at->format('d/m/Y \à\s H:i') }}
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    <x-admin.button :href="route('empresa.vagas.candidatura', [$job->id, $application->id])" icon="eye">Ver</x-admin.button>

                    @if ($application->hasResume())
                        <x-admin.button :href="route('empresa.vagas.curriculo', [$job->id, $application->id])" icon="file">Currículo</x-admin.button>
                    @endif

                    <x-admin.confirm-form :action="route('empresa.vagas.candidatura.destroy', [$job->id, $application->id])"
                                          message="Remover a candidatura de {{ $application->name }}? O currículo será apagado.">Remover</x-admin.confirm-form>
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="users" title="Nenhuma candidatura ainda."
                                 description="Quando alguém se candidatar pelo portal, aparece aqui." />
        @endforelse
    </div>

    @if ($applications->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $applications->links() }}</div>
    @endif
</x-admin.card>
@endsection
