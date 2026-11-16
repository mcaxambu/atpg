@extends('layouts.admin')

@section('title', 'Indústrias')
@section('page_heading', 'Indústrias')

@section('content')
{{--
    O aviso de associadas sem especialidade vem primeiro de propósito: com
    elas em branco o cruzamento devolve vazio, e a tela parece quebrada
    quando na verdade falta o cadastro do outro lado.
--}}
@if ($semEspecialidade > 0)
    <a href="{{ route('admin.companies.index') }}"
       class="mb-6 flex items-center gap-4 rounded-2xl border border-warning-200 bg-warning-50 p-5 dark:border-warning-500/25 dark:bg-warning-500/10">
        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-warning-500 text-white">
            <x-admin.icon name="alert" class="h-6 w-6" />
        </span>
        <div>
            <strong class="block text-sm font-semibold text-warning-800 dark:text-warning-200">
                {{ $semEspecialidade }} {{ $semEspecialidade === 1 ? 'associada ainda não marcou' : 'associadas ainda não marcaram' }} o que sabem fazer
            </strong>
            <span class="text-sm text-warning-700 dark:text-warning-300">
                Enquanto isso, elas não aparecem em nenhum cruzamento. Abra a empresa e marque as especialidades.
            </span>
        </div>
    </a>
@endif

<x-admin.card :padding="false">
    <x-slot:title>{{ $total }} {{ $total === 1 ? 'indústria' : 'indústrias' }}</x-slot:title>
    <x-slot:subtitle>O mercado da associação. Marque do que cada uma precisa para o cruzamento funcionar.</x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('admin.industrias.create')" variant="primary" icon="plus">Nova indústria</x-admin.button>
    </x-slot:actions>

    <div class="border-b border-gray-100 p-5 dark:border-gray-800">
        <form method="get" action="{{ route('admin.industrias.index') }}" class="flex flex-col gap-3 sm:flex-row">
            <input name="q" value="{{ request('q') }}" placeholder="Buscar por nome, setor ou bairro..."
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white">

            <select name="setor" class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">Todos os setores</option>
                @foreach ($setores as $setor)
                    <option value="{{ $setor }}" @selected(request('setor') === $setor)>{{ $setor }}</option>
                @endforeach
            </select>

            <select name="especialidade" class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">Precisa de qualquer coisa</option>
                @foreach ($especialidades as $especialidade)
                    <option value="{{ $especialidade->id }}" @selected(request()->integer('especialidade') === $especialidade->id)>
                        Precisa de {{ $especialidade->name }}
                    </option>
                @endforeach
            </select>

            <x-admin.button type="submit" icon="search">Buscar</x-admin.button>
        </form>
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($industrias as $industria)
            <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <a href="{{ route('admin.industrias.show', $industria) }}"
                       class="text-sm font-semibold text-gray-800 hover:text-brand-600 dark:text-white/90">{{ $industria->name }}</a>

                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                        {{ $industria->sector ?: 'setor não informado' }}
                        @if ($industria->porteLabel()) &middot; {{ $industria->porteLabel() }} @endif
                        @if ($industria->neighborhood) &middot; {{ $industria->neighborhood }} @endif
                        @unless ($industria->is_active) &middot; <span class="text-gray-400">inativa</span> @endunless
                    </span>

                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @forelse ($industria->specialties as $especialidade)
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-600 dark:bg-white/[0.06] dark:text-gray-300">
                                {{ $especialidade->name }}
                            </span>
                        @empty
                            <span class="text-xs text-warning-600 dark:text-warning-400">sem necessidade marcada — fora dos cruzamentos</span>
                        @endforelse
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    <x-admin.button :href="route('admin.industrias.show', $industria)" variant="primary" icon="eye">Abrir</x-admin.button>
                    <x-admin.button :href="route('admin.industrias.edit', $industria)" icon="edit">Editar</x-admin.button>
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="building" title="Nenhuma indústria cadastrada."
                                 description="Cadastre as indústrias da região e marque do que cada uma precisa — é assim que o portal mostra quem do grupo pode atender.">
                <x-slot:actions>
                    <x-admin.button :href="route('admin.industrias.create')" variant="primary" icon="plus">Nova indústria</x-admin.button>
                </x-slot:actions>
            </x-admin.empty-state>
        @endforelse
    </div>

    @if ($industrias->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $industrias->links() }}</div>
    @endif
</x-admin.card>
@endsection
