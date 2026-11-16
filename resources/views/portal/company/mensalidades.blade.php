@extends('layouts.company')

@section('title', 'Mensalidades')
@section('page_heading', 'Minhas mensalidades')

@section('content')
@php
    $dinheiro = fn ($valor) => 'R$ '.number_format((float) $valor, 2, ',', '.');
    $vencidas = $emAberto->filter(fn ($m) => $m->estaVencida());
@endphp

@if ($vencidas->isNotEmpty())
    <div class="mb-6 flex items-center gap-4 rounded-2xl border border-error-200 bg-error-50 p-5 dark:border-error-500/25 dark:bg-error-500/10">
        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-error-500 text-white">
            <x-admin.icon name="alert" class="h-6 w-6" />
        </span>
        <div>
            <strong class="block text-sm font-semibold text-error-800 dark:text-error-200">
                {{ $vencidas->count() }} {{ $vencidas->count() === 1 ? 'mensalidade vencida' : 'mensalidades vencidas' }} — {{ $dinheiro($vencidas->sum('valor')) }}
            </strong>
            <span class="text-sm text-error-700 dark:text-error-300">Fale com a diretoria para acertar.</span>
        </div>
    </div>
@endif

<x-admin.card :padding="false">
    <x-slot:title>Histórico</x-slot:title>
    <x-slot:subtitle>
        @if ($emAberto->isEmpty())
            Nada em aberto. Obrigado!
        @else
            {{ $emAberto->count() }} em aberto, somando {{ $dinheiro($emAberto->sum('valor')) }}.
        @endif
    </x-slot:subtitle>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($mensalidades as $mensalidade)
            <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <strong class="block text-sm font-semibold text-gray-800 dark:text-white/90">
                        {{ $mensalidade->competenciaLabel() }}
                    </strong>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                        {{ $dinheiro($mensalidade->valor) }} &middot; vence {{ $mensalidade->vencimento->format('d/m/Y') }}
                        @if ($mensalidade->pago_em) &middot; pago em {{ $mensalidade->pago_em->format('d/m/Y') }} @endif
                    </span>
                </div>

                <div class="flex shrink-0 items-center gap-3">
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $mensalidade->status->badgeClasses() }}">
                        {{ $mensalidade->estaVencida() ? 'Vencida' : $mensalidade->status->label() }}
                    </span>

                    @if ($mensalidade->status === \App\Enums\MensalidadeStatus::Paga)
                        <x-admin.button :href="route('empresa.mensalidades.recibo', $mensalidade)" target="_blank" icon="file">
                            Recibo
                        </x-admin.button>
                    @endif
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="inbox" title="Nenhuma mensalidade registrada."
                                 description="Quando a associação gerar a primeira, ela aparece aqui." />
        @endforelse
    </div>
</x-admin.card>
@endsection
