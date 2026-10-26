@extends('layouts.admin')

@section('title', 'Participação nas reuniões')
@section('page_heading', 'Participação nas reuniões')

@section('content')
{{--
    São duas medidas diferentes, e a tela não as mistura:

    RESPOSTA é o que a empresa respondeu à convocação — existe desde a primeira
    reunião agendada. PRESENÇA só existe depois que a diretoria registra a lista
    de presentes, o que encerra a reunião. Reunião futura não entra na conta de
    presença: senão toda reunião marcada contaria como falta de todo mundo.
--}}
<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <x-admin.card>
        <span class="text-xs font-medium uppercase tracking-wide text-gray-400">Reuniões realizadas</span>
        <strong class="mt-1 block text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $resumo['reunioes_realizadas'] }}</strong>
        <span class="text-xs text-gray-500 dark:text-gray-400">de {{ $resumo['reunioes_total'] }} cadastradas</span>
    </x-admin.card>

    <x-admin.card>
        <span class="text-xs font-medium uppercase tracking-wide text-gray-400">Presença média</span>
        @if ($resumo['media_presenca'] === null)
            <strong class="mt-1 block text-2xl font-semibold text-gray-400">—</strong>
            <span class="text-xs text-gray-500 dark:text-gray-400">nenhuma reunião encerrada ainda</span>
        @else
            <strong class="mt-1 block text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $resumo['media_presenca'] }}%</strong>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $resumo['presentes_por_reuniao'] }} empresas por reunião</span>
        @endif
    </x-admin.card>

    <x-admin.card>
        <span class="text-xs font-medium uppercase tracking-wide text-gray-400">Respondem à convocação</span>
        <strong class="mt-1 block text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $resumo['taxa_resposta'] }}%</strong>
        <span class="text-xs text-gray-500 dark:text-gray-400">de {{ $resumo['convocacoes'] }} convocações enviadas</span>
    </x-admin.card>
</div>

@if ($resumo['media_presenca'] === null)
    <x-admin.card class="mb-6">
        <div class="flex gap-3">
            <x-admin.icon name="alert" class="mt-0.5 h-5 w-5 shrink-0 text-warning-500" />
            <div class="text-sm text-gray-600 dark:text-gray-300">
                <strong class="font-medium text-gray-800 dark:text-white/90">Ainda não há presença para medir.</strong>
                <p class="mt-1">
                    A presença é registrada na tela da reunião, no bloco "Presentes no dia" — e é isso que marca a
                    reunião como realizada. Enquanto nenhuma for encerrada, esta página mostra apenas quem respondeu
                    à convocação. Não assumimos que ninguém foi.
                </p>
            </div>
        </div>
    </x-admin.card>
@endif

<x-admin.card :padding="false">
    <x-slot:title>{{ $linhas->count() }} {{ $linhas->count() === 1 ? 'empresa convocada' : 'empresas convocadas' }}</x-slot:title>
    <x-slot:subtitle>Clique na empresa para ver reunião a reunião.</x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('admin.reunioes.index')" variant="ghost" icon="calendar">Ver reuniões</x-admin.button>
    </x-slot:actions>

    <div class="flex flex-wrap gap-2 border-b border-gray-100 p-5 dark:border-gray-800">
        @foreach ([
            'nome' => 'Por nome',
            'presenca' => 'Mais presentes',
            'ausencia' => 'Mais ausentes',
            'resposta' => 'Mais respondem',
        ] as $valor => $rotulo)
            <a href="{{ route('admin.reunioes.participacao', ['ordem' => $valor]) }}"
               class="rounded-lg px-3 py-2 text-sm {{ $ordem === $valor ? 'bg-brand-50 font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/[0.04]' }}">
                {{ $rotulo }}
            </a>
        @endforeach
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-white/[0.02]">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Empresa</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Convocações</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Respondeu</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Presença</th>
                    <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500"></th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($linhas as $linha)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-4">
                            <a href="{{ route('admin.reunioes.participacao.empresa', $linha['empresa']) }}"
                               class="flex items-center gap-3">
                                <x-admin.avatar :photo="$linha['empresa']->logo_path" :initials="$linha['empresa']->initials" :contain="true" />
                                <span class="text-sm font-medium text-gray-800 hover:text-brand-600 dark:text-white/90">{{ $linha['empresa']->name }}</span>
                            </a>
                        </td>

                        <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $linha['convocacoes'] }}</td>

                        <td class="px-5 py-4">
                            <span class="text-sm text-gray-700 dark:text-gray-200">{{ $linha['taxa_resposta'] }}%</span>
                            <span class="block text-xs text-gray-400">{{ $linha['responderam'] }} de {{ $linha['convocacoes'] }} · {{ $linha['confirmadas'] }} confirmadas</span>
                        </td>

                        <td class="px-5 py-4">
                            @if ($linha['taxa_presenca'] === null)
                                <span class="text-sm text-gray-400">sem dados</span>
                                <span class="block text-xs text-gray-400">nenhuma reunião encerrada</span>
                            @else
                                <span class="text-sm font-medium {{ $linha['taxa_presenca'] >= 70 ? 'text-success-600 dark:text-success-400' : ($linha['taxa_presenca'] >= 40 ? 'text-warning-600 dark:text-warning-400' : 'text-error-600 dark:text-error-400') }}">
                                    {{ $linha['taxa_presenca'] }}%
                                </span>
                                <span class="block text-xs text-gray-400">{{ $linha['presencas'] }} de {{ $linha['realizadas'] }} reuniões</span>
                            @endif
                        </td>

                        <td class="px-5 py-4 text-right">
                            <x-admin.button :href="route('admin.reunioes.participacao.empresa', $linha['empresa'])" icon="eye">Abrir</x-admin.button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-admin.empty-state icon="calendar" title="Nenhuma empresa convocada ainda."
                                                 description="Quando uma reunião for criada e a lista gerada, as empresas aparecem aqui." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin.card>
@endsection
