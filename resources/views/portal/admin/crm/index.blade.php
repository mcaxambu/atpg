@extends('layouts.admin')

@section('title', 'Novos associados')
@section('page_heading', 'Novos associados')

@section('content')
{{--
    A tela abre pelo que precisa de ação, não pelo quadro.
    Funil bonito com todo mundo parado é o jeito mais comum de um CRM virar
    enfeite: quem entra aqui precisa ver primeiro quem está atrasado.
--}}
<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <x-admin.card>
        <span class="text-xs font-medium uppercase tracking-wide text-gray-400">No funil</span>
        <strong class="mt-1 block text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $resumo['em_andamento'] }}</strong>
        <span class="text-xs text-gray-500 dark:text-gray-400">empresas e pessoas em conversa</span>
    </x-admin.card>

    <x-admin.card>
        <span class="text-xs font-medium uppercase tracking-wide text-gray-400">Atrasados</span>
        <strong class="mt-1 block text-2xl font-semibold {{ $resumo['atrasados'] > 0 ? 'text-error-600 dark:text-error-400' : 'text-gray-800 dark:text-white/90' }}">{{ $resumo['atrasados'] }}</strong>
        <span class="text-xs text-gray-500 dark:text-gray-400">passaram da data do próximo passo</span>
    </x-admin.card>

    <x-admin.card>
        <span class="text-xs font-medium uppercase tracking-wide text-gray-400">Entraram neste mês</span>
        <strong class="mt-1 block text-2xl font-semibold text-success-600 dark:text-success-400">{{ $resumo['associados_no_mes'] }}</strong>
        <span class="text-xs text-gray-500 dark:text-gray-400">viraram associados</span>
    </x-admin.card>
</div>

@if ($atrasados->isNotEmpty())
    <x-admin.card class="mb-6" :padding="false">
        <x-slot:title>Precisa de ação</x-slot:title>
        <x-slot:subtitle>O próximo passo combinado já venceu.</x-slot:subtitle>

        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($atrasados as $item)
                <div class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <a href="{{ route('admin.crm.show', $item) }}" class="block truncate text-sm font-semibold text-gray-800 hover:text-brand-600 dark:text-white/90">{{ $item->name }}</a>
                        <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                            {{ $item->next_action ?: 'Sem descrição do próximo passo' }}
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                        <span class="rounded-full bg-error-50 px-2.5 py-1 text-xs font-medium text-error-700 dark:bg-error-500/10 dark:text-error-300">
                            venceu em {{ $item->next_action_at->format('d/m/Y') }}
                        </span>
                        <span class="hidden text-xs text-gray-500 sm:inline dark:text-gray-400">{{ $item->owner?->name ?? 'sem responsável' }}</span>
                        <x-admin.button :href="route('admin.crm.show', $item)" icon="eye">Abrir</x-admin.button>
                    </div>
                </div>
            @endforeach
        </div>
    </x-admin.card>
@endif

<x-admin.card :padding="false">
    <x-slot:title>{{ $etapaFiltro?->label() ?? 'Funil' }}</x-slot:title>
    <x-slot:subtitle>
        {{ $etapaFiltro?->description() ?? 'Do primeiro contato até a entrada na associação.' }}
    </x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('admin.crm.create')" variant="primary" icon="plus">Novo prospecto</x-admin.button>
    </x-slot:actions>

    <div class="border-b border-gray-100 p-5 dark:border-gray-800">
        <x-admin.filter-bar :action="route('admin.crm.index')" :value="$busca" placeholder="Buscar por nome, contato ou e-mail...">
            <select name="etapa" class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">Funil completo</option>
                @foreach (\App\Enums\ProspectStage::all() as $etapa)
                    <option value="{{ $etapa->value }}" @selected($etapaFiltro === $etapa)>{{ $etapa->label() }}</option>
                @endforeach
            </select>

            <select name="responsavel" class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">Todos os responsáveis</option>
                @foreach ($responsaveis as $pessoa)
                    <option value="{{ $pessoa->id }}" @selected($responsavel === $pessoa->id)>{{ $pessoa->name }}</option>
                @endforeach
            </select>
        </x-admin.filter-bar>
    </div>

    @if ($etapaFiltro)
        {{-- Etapa escolhida: lista completa, com paginação. --}}
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($lista as $item)
                @include('portal.admin.crm.partials.linha', ['item' => $item])
            @empty
                <x-admin.empty-state icon="handshake" title="Nenhum prospecto nesta etapa."
                                     description="Quando alguém chegar aqui, aparece nesta lista." />
            @endforelse
        </div>

        @if ($lista->hasPages())
            <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $lista->links() }}</div>
        @endif
    @else
        <div class="overflow-x-auto p-5">
            <div class="flex min-w-max gap-4">
                @foreach ($colunas as $coluna)
                    <div class="w-72 shrink-0">
                        <div class="mb-3 flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $coluna['etapa']->label() }}</span>
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-white/[0.06] dark:text-gray-300">{{ $coluna['total'] }}</span>
                        </div>

                        <div class="space-y-2">
                            @forelse ($coluna['cartoes'] as $item)
                                @include('portal.admin.crm.partials.cartao', ['item' => $item])
                            @empty
                                <p class="rounded-xl border border-dashed border-gray-200 px-3 py-6 text-center text-xs text-gray-400 dark:border-gray-700">vazio</p>
                            @endforelse

                            @if ($coluna['total'] > $coluna['cartoes']->count())
                                <a href="{{ route('admin.crm.index', ['etapa' => $coluna['etapa']->value]) }}"
                                   class="block rounded-lg px-3 py-2 text-center text-xs font-medium text-brand-600 hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10">
                                    ver os {{ $coluna['total'] }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-admin.card>
@endsection
