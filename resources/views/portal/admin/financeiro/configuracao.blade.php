@extends('layouts.admin')

@section('title', 'Configuração do financeiro')
@section('page_heading', 'Configuração da mensalidade')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
@endphp

<form method="post" action="{{ route('admin.financeiro.configuracao.salvar') }}" class="space-y-6">
    @csrf

    <x-admin.card title="Valor e vencimento"
                  subtitle="Vale para as próximas gerações. O que já foi gerado não muda sozinho — mensalidade emitida é compromisso registrado.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin.field label="Valor mensal (R$)" name="valor" required>
                <input type="number" name="valor" step="0.01" min="0" required
                       value="{{ old('valor', $valor) }}" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="Dia do vencimento" name="dia" required
                           hint="De 1 a 28 — dia 29, 30 e 31 não existem em todo mês.">
                <input type="number" name="dia" min="1" max="28" required
                       value="{{ old('dia', $dia) }}" class="{{ $input }}">
            </x-admin.field>
        </div>
    </x-admin.card>

    <x-admin.card title="Associadas isentas"
                  subtitle="Quem está marcado aqui não entra na geração do mês. Serve para honorários, fundadores e casos combinados.">
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($isentas as $empresa)
                <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                    <input type="checkbox" name="isentas[]" value="{{ $empresa->id }}"
                           @checked(in_array($empresa->id, old('isentas', $isentas->where('mensalidade_isenta', true)->pluck('id')->all())))
                           class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    {{ $empresa->name }}
                </label>
            @endforeach
        </div>
    </x-admin.card>

    {{--
        Honestidade sobre o que ainda não existe: sem provedor escolhido, a
        baixa é manual. Dizer isso na tela evita alguém esperar o boleto sair
        sozinho e descobrir no fim do mês que ninguém foi cobrado.
    --}}
    <x-admin.card title="Cobrança automática">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            Ainda não há integração com provedor de pagamento. Hoje o portal <strong>controla</strong> as mensalidades —
            quem deve, quem pagou, quanto entrou — e a baixa é feita à mão nesta tela. O envio de boleto ou Pix e a
            baixa automática entram quando o provedor for escolhido, sem refazer nada do que está aqui.
        </p>
    </x-admin.card>

    <div class="flex flex-wrap gap-2">
        <x-admin.button type="submit" variant="primary" icon="check">Salvar</x-admin.button>
        <x-admin.button :href="route('admin.financeiro.index')" variant="ghost">Voltar</x-admin.button>
    </div>
</form>
@endsection
