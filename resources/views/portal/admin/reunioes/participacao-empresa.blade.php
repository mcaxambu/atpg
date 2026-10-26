@extends('layouts.admin')

@section('title', 'Participação de '.$empresa->name)
@section('page_heading', 'Participação de '.$empresa->name)

@section('content')
@php
    $realizadas = $historico->whereNotNull('compareceu');
    $presencas = $realizadas->where('compareceu', true)->count();
@endphp

<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <x-admin.card :padding="false">
            <x-slot:title>Reunião a reunião</x-slot:title>
            <x-slot:subtitle>O que a empresa respondeu e se compareceu, da mais recente para a mais antiga.</x-slot:subtitle>

            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($historico as $linha)
                    <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <a href="{{ route('admin.reunioes.show', $linha['reuniao']) }}"
                               class="truncate text-sm font-semibold text-gray-800 hover:text-brand-600 dark:text-white/90">
                                {{ $linha['reuniao']?->title ?? 'Reunião removida' }}
                            </a>

                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                {{ $linha['reuniao']?->scheduled_at?->translatedFormat('d \d\e F \d\e Y') }}
                                @if ($linha['respondeu_em'])
                                    · respondeu em {{ $linha['respondeu_em']->format('d/m/Y') }}
                                @endif
                            </p>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $linha['resposta']->badgeClasses() }}">
                                {{ $linha['resposta']->label() }}
                            </span>

                            @if ($linha['compareceu'] === null)
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-500 dark:bg-white/[0.06] dark:text-gray-400">
                                    ainda não encerrada
                                </span>
                            @elseif ($linha['compareceu'])
                                <span class="rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700 dark:bg-success-500/15 dark:text-success-300">
                                    compareceu
                                </span>
                            @else
                                <span class="rounded-full bg-error-50 px-2.5 py-1 text-xs font-medium text-error-700 dark:bg-error-500/15 dark:text-error-300">
                                    faltou
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <x-admin.empty-state icon="calendar" title="Esta empresa não foi convocada ainda."
                                         description="Ela aparece aqui a partir da primeira reunião em que entrar na lista." />
                @endforelse
            </div>
        </x-admin.card>
    </div>

    <div class="space-y-6">
        <x-admin.card title="Resumo">
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Convocações</dt>
                    <dd class="mt-0.5 text-gray-700 dark:text-gray-300">{{ $historico->count() }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Respondeu</dt>
                    <dd class="mt-0.5 text-gray-700 dark:text-gray-300">
                        {{ $historico->where('resposta', \App\Enums\AttendanceStatus::Pendente)->count() === $historico->count()
                            ? 'nenhuma vez'
                            : $historico->count() - $historico->where('resposta', \App\Enums\AttendanceStatus::Pendente)->count().' de '.$historico->count() }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Presença</dt>
                    <dd class="mt-0.5 text-gray-700 dark:text-gray-300">
                        @if ($realizadas->isEmpty())
                            sem reunião encerrada
                        @else
                            {{ $presencas }} de {{ $realizadas->count() }} reuniões
                        @endif
                    </dd>
                </div>
            </dl>
        </x-admin.card>

        <div class="flex flex-wrap gap-2">
            <x-admin.button :href="route('admin.companies.show', $empresa)" icon="building">Ver cadastro</x-admin.button>
            <x-admin.button :href="route('admin.reunioes.participacao')" variant="ghost">Voltar</x-admin.button>
        </div>
    </div>
</div>
@endsection
