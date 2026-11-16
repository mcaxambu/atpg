@extends('layouts.admin')

@section('title', 'Financeiro')
@section('page_heading', 'Mensalidades')

@section('content')
@php
    $dinheiro = fn ($valor) => 'R$ '.number_format((float) $valor, 2, ',', '.');
    $mes = \Illuminate\Support\Carbon::parse($competencia.'-01');
@endphp

{{-- Dito antes de emitir, não descoberto no meio do lote. --}}
@if ($asaasLigado && $semCnpj->isNotEmpty())
    <div class="mb-6 rounded-2xl border border-warning-200 bg-warning-50 p-5 text-sm dark:border-warning-500/25 dark:bg-warning-500/10">
        <strong class="block font-semibold text-warning-800 dark:text-warning-200">
            {{ $semCnpj->count() }} associada(s) sem CNPJ — o Asaas recusa a cobrança delas
        </strong>
        <span class="text-warning-700 dark:text-warning-300">{{ $semCnpj->implode(', ') }}</span>
    </div>
@endif

@if ($errors->has('emissao'))
    <div class="mb-6 rounded-2xl border border-error-200 bg-error-50 p-5 text-sm dark:border-error-500/25 dark:bg-error-500/10">
        <strong class="block font-semibold text-error-800 dark:text-error-200">Nem todas as cobranças saíram</strong>
        <ul class="mt-2 list-inside list-disc text-error-700 dark:text-error-300">
            @foreach ((array) $errors->get('emissao') as $falha)
                <li>{{ $falha }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if ((float) $valorConfigurado <= 0)
    <a href="{{ route('admin.financeiro.configuracao') }}"
       class="mb-6 flex items-center gap-4 rounded-2xl border border-warning-200 bg-warning-50 p-5 dark:border-warning-500/25 dark:bg-warning-500/10">
        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-warning-500 text-white">
            <x-admin.icon name="alert" class="h-6 w-6" />
        </span>
        <div>
            <strong class="block text-sm font-semibold text-warning-800 dark:text-warning-200">O valor da mensalidade ainda não foi definido</strong>
            <span class="text-sm text-warning-700 dark:text-warning-300">Sem ele não dá para gerar o mês. Leva meio minuto.</span>
        </div>
    </a>
@endif

{{-- Os números do mês, antes da lista. --}}
<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([
        ['Previsto', $dinheiro($resumo['previsto']), $resumo['total'].' mensalidade(s)', 'text-gray-800 dark:text-white/90'],
        ['Recebido', $dinheiro($resumo['recebido']), 'pagas neste mês', 'text-success-600 dark:text-success-400'],
        ['Em aberto', $dinheiro($resumo['em_aberto']), 'ainda não pagas', 'text-warning-600 dark:text-warning-400'],
        ['Vencidas', $dinheiro($resumo['valor_vencido']), $resumo['vencidas'].' passou do vencimento', 'text-error-600 dark:text-error-400'],
    ] as [$rotulo, $valor, $nota, $cor])
        <x-admin.card>
            <span class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $rotulo }}</span>
            <strong class="mt-1 block text-2xl font-semibold {{ $cor }}">{{ $valor }}</strong>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $nota }}</span>
        </x-admin.card>
    @endforeach
</div>

<x-admin.card :padding="false">
    <x-slot:title>{{ ucfirst($mes->translatedFormat('F \d\e Y')) }}</x-slot:title>
    <x-slot:subtitle>
        Mensalidade de {{ $dinheiro($valorConfigurado) }}, vencendo todo dia {{ $diaVencimento }}.
        @if ($isentas > 0) {{ $isentas }} associada(s) isenta(s). @endif
    </x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('admin.financeiro.configuracao')" icon="settings">Configuração</x-admin.button>

        @php
            $semCobranca = $mensalidades
                ->where('status', \App\Enums\MensalidadeStatus::Pendente)
                ->whereNull('cobranca_externa_id');
        @endphp

        @if ($asaasLigado && $semCobranca->isNotEmpty())
            <form method="post" action="{{ route('admin.financeiro.emitir') }}" class="inline-flex"
                  onsubmit="return confirm('Emitir no Asaas {{ $semCobranca->count() }} cobrança(s)? O associado recebe o link para pagar.');">
                @csrf
                <input type="hidden" name="competencia" value="{{ $competencia }}">
                <x-admin.button type="submit" variant="primary" icon="inbox">
                    Emitir no Asaas ({{ $semCobranca->count() }})
                </x-admin.button>
            </form>
        @endif

        @if ($mensalidades->isEmpty())
            <form method="post" action="{{ route('admin.financeiro.gerar') }}">
                @csrf
                <input type="hidden" name="competencia" value="{{ $competencia }}">
                <x-admin.button type="submit" variant="primary" icon="plus">Gerar este mês</x-admin.button>
            </form>
        @endif
    </x-slot:actions>

    <div class="border-b border-gray-100 p-5 dark:border-gray-800">
        <form method="get" action="{{ route('admin.financeiro.index') }}" class="flex flex-col gap-3 sm:flex-row">
            <select name="competencia" class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                @foreach ($competencias as $opcao)
                    <option value="{{ $opcao }}" @selected($competencia === $opcao)>
                        {{ ucfirst(\Illuminate\Support\Carbon::parse($opcao.'-01')->translatedFormat('F \d\e Y')) }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">Todas as situações</option>
                @foreach (\App\Enums\MensalidadeStatus::options() as $valor => $rotulo)
                    <option value="{{ $valor }}" @selected(request('status') === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select>

            <x-admin.button type="submit" icon="search">Ver</x-admin.button>
        </form>
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($mensalidades as $mensalidade)
            <div class="flex flex-col gap-3 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <strong class="block text-sm font-semibold text-gray-800 dark:text-white/90">
                        {{ $mensalidade->company?->name ?? 'Empresa removida' }}
                    </strong>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                        vence {{ $mensalidade->vencimento->format('d/m/Y') }} &middot; {{ $dinheiro($mensalidade->valor) }}
                        @if ($mensalidade->pago_em) &middot; pago em {{ $mensalidade->pago_em->format('d/m/Y') }} @endif
                        @if ($mensalidade->formaLabel()) ({{ $mensalidade->formaLabel() }}) @endif
                    </span>

                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $mensalidade->status->badgeClasses() }}">
                            {{ $mensalidade->status->label() }}
                        </span>

                        @if ($mensalidade->estaVencida())
                            <span class="text-xs font-medium text-error-600 dark:text-error-400">
                                {{ $mensalidade->diasDeAtraso() }} dia(s) de atraso
                            </span>
                        @endif

                        @if ($mensalidade->observacao)
                            <span class="text-xs italic text-gray-400">{{ $mensalidade->observacao }}</span>
                        @endif

                        @if ($mensalidade->cobranca_url)
                            <a href="{{ $mensalidade->cobranca_url }}" target="_blank" rel="noopener noreferrer"
                               class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-400">ver cobrança</a>
                        @elseif ($asaasLigado && $mensalidade->status === \App\Enums\MensalidadeStatus::Pendente)
                            <span class="text-xs text-gray-400">sem cobrança emitida</span>
                        @endif
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    @if ($mensalidade->status === \App\Enums\MensalidadeStatus::Paga)
                        <x-admin.confirm-form :action="route('admin.financeiro.reabrir', $mensalidade)" method="post"
                                              icon="restore" message="Desfazer a baixa deste pagamento?">
                            Desfazer baixa
                        </x-admin.confirm-form>
                    @else
                        <form method="post" action="{{ route('admin.financeiro.pagar', $mensalidade) }}" class="flex flex-wrap items-center gap-2">
                            @csrf
                            <select name="forma_pagamento" class="rounded-lg border border-gray-200 bg-white px-2 py-2 text-xs text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                <option value="">Forma...</option>
                                @foreach (\App\Models\Mensalidade::FORMAS as $valor => $rotulo)
                                    <option value="{{ $valor }}">{{ $rotulo }}</option>
                                @endforeach
                            </select>
                            <x-admin.button type="submit" variant="primary" icon="check">Dar baixa</x-admin.button>
                        </form>

                        <form method="post" action="{{ route('admin.financeiro.situacao', $mensalidade) }}">
                            @csrf
                            <input type="hidden" name="status" value="isenta">
                            <x-admin.button type="submit" icon="x">Isentar</x-admin.button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="inbox" title="Nada gerado para este mês."
                                 description="Gerar o mês cria uma mensalidade para cada associada publicada e não isenta. Fazer duas vezes não cobra duas vezes.">
                <x-slot:actions>
                    <form method="post" action="{{ route('admin.financeiro.gerar') }}">
                        @csrf
                        <input type="hidden" name="competencia" value="{{ $competencia }}">
                        <x-admin.button type="submit" variant="primary" icon="plus">Gerar {{ $mes->translatedFormat('F') }}</x-admin.button>
                    </form>
                </x-slot:actions>
            </x-admin.empty-state>
        @endforelse
    </div>
</x-admin.card>
@endsection
