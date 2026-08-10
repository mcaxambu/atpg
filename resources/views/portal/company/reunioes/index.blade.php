@extends('layouts.company')

@section('title', 'Reuniões')

@section('content')
@php
    $cartao = function ($reuniao) {
        return [
            'minha' => $reuniao->attendances->first(),
            'aberta' => $reuniao->acceptsConfirmations(),
        ];
    };
@endphp

<div>
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Reuniões</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Confirme a presença da empresa e acompanhe a pauta das reuniões da associação.
    </p>
</div>

<x-admin.card title="Próximas reuniões" :padding="false">
    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($proximas as $reuniao)
            @php ['minha' => $minha, 'aberta' => $aberta] = $cartao($reuniao); @endphp

            <div class="p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex min-w-0 gap-4">
                        <div class="grid h-14 w-14 shrink-0 place-items-center rounded-xl bg-brand-50 text-center leading-none dark:bg-brand-500/15">
                            <span>
                                <strong class="block text-lg font-bold text-brand-600 dark:text-brand-300">{{ $reuniao->scheduled_at->format('d') }}</strong>
                                <small class="text-[10px] font-semibold uppercase text-brand-500">{{ $reuniao->scheduled_at->translatedFormat('M') }}</small>
                            </span>
                        </div>
                        <div class="min-w-0">
                            <strong class="block text-sm font-semibold text-gray-800 dark:text-white/90">{{ $reuniao->title }}</strong>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">
                                {{ $reuniao->type->label() }} &middot;
                                {{ $reuniao->scheduled_at->translatedFormat('l, d/m') }}, {{ $reuniao->periodLabel() }}
                            </span>
                            @if ($reuniao->location)
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $reuniao->location }}</span>
                            @endif
                            @if ($reuniao->online_url)
                                <a href="{{ $reuniao->online_url }}" target="_blank" rel="noopener"
                                   class="mt-1 inline-block text-xs font-medium text-brand-500 hover:underline">Entrar na videochamada</a>
                            @endif
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-col items-start gap-2 lg:items-end">
                        @if ($minha && $minha->hasReplied())
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $minha->status->badgeClasses() }}">
                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $minha->status->label() }}
                            </span>
                        @endif

                        @if ($aberta)
                            <form method="post" action="{{ route('empresa.reunioes.confirmar', $reuniao) }}"
                                  class="flex flex-wrap gap-2">
                                @csrf
                                @foreach (\App\Enums\AttendanceStatus::replyOptions() as $valor => $rotulo)
                                    <button type="submit" name="status" value="{{ $valor }}"
                                            @class([
                                                'rounded-lg border px-3 py-2 text-xs font-medium transition',
                                                'border-brand-500 bg-brand-500 text-white' => $minha?->status?->value === $valor,
                                                'border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.04]' => $minha?->status?->value !== $valor,
                                            ])>{{ $rotulo }}</button>
                                @endforeach
                            </form>
                        @else
                            <span class="text-xs text-gray-400">Confirmações encerradas</span>
                        @endif

                        <a href="{{ route('reuniao.calendario', $reuniao->public_token) }}"
                           class="text-xs font-medium text-gray-500 hover:underline dark:text-gray-400">Adicionar à agenda</a>
                    </div>
                </div>

                @if ($reuniao->agendaItems->isNotEmpty())
                    <details class="mt-4 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                        <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                            Ver pauta ({{ $reuniao->agendaItems->count() }} {{ $reuniao->agendaItems->count() === 1 ? 'item' : 'itens' }})
                        </summary>
                        <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-gray-600 dark:text-gray-300">
                            @foreach ($reuniao->agendaItems as $item)
                                <li>
                                    <strong class="text-gray-800 dark:text-gray-200">{{ $item->title }}</strong>
                                    @if ($item->presenter)
                                        <span class="text-xs text-gray-500 dark:text-gray-400">— {{ $item->presenter }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </details>
                @endif
            </div>
        @empty
            <x-admin.empty-state icon="calendar" title="Nenhuma reunião agendada."
                                 description="Quando a associação marcar uma reunião, ela aparece aqui." />
        @endforelse
    </div>
</x-admin.card>

@if ($anteriores->isNotEmpty())
    <x-admin.card title="Reuniões anteriores" :padding="false">
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($anteriores as $reuniao)
                @php $minha = $reuniao->attendances->first(); @endphp
                <div class="flex flex-col gap-2 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <strong class="block text-sm font-semibold text-gray-800 dark:text-white/90">{{ $reuniao->title }}</strong>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                            {{ $reuniao->scheduled_at->translatedFormat('d/m/Y') }} &middot; {{ $reuniao->type->shortLabel() }}
                        </span>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $reuniao->status->badgeClasses() }}">
                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $reuniao->status->label() }}
                        </span>
                        @if ($minha?->attended)
                            <span class="text-xs font-medium text-success-600 dark:text-success-400">Você participou</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </x-admin.card>
@endif
@endsection
