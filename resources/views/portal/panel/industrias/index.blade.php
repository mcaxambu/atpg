{{-- Mesma lista da diretoria, sem contato nem anotação; o layout vem do controller. --}}
@extends($layout)

@section('title', 'Indústrias')
@section('page_heading', 'Indústrias da região')

@section('content')
@php
    $input = 'rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
@endphp

{{--
    Quem entra aqui quer saber uma coisa: onde EU me encaixo. Por isso o aviso
    de perfil incompleto vem antes da lista — sem especialidade marcada, o
    filtro "só as que eu atendo" devolve vazio e parece defeito.
--}}
@if ($empresa && $minhasEspecialidades->isEmpty())
    <a href="{{ route('empresa.perfil.edit') }}"
       class="mb-6 flex items-center gap-4 rounded-2xl border border-warning-200 bg-warning-50 p-5 dark:border-warning-500/25 dark:bg-warning-500/10">
        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-warning-500 text-white">
            <x-admin.icon name="alert" class="h-6 w-6" />
        </span>
        <div>
            <strong class="block text-sm font-semibold text-warning-800 dark:text-warning-200">
                Marque o que a sua empresa sabe fazer
            </strong>
            <span class="text-sm text-warning-700 dark:text-warning-300">
                É assim que o portal mostra em quais indústrias você se encaixa — e é assim que a diretoria sabe te indicar.
            </span>
        </div>
    </a>
@endif

<x-admin.card :padding="false">
    <x-slot:title>{{ $industrias->total() }} {{ $industrias->total() === 1 ? 'indústria' : 'indústrias' }}</x-slot:title>
    <x-slot:subtitle>Indústrias mapeadas pela associação e o que cada uma precisa.</x-slot:subtitle>

    <div class="border-b border-gray-100 p-5 dark:border-gray-800">
        <form method="get" action="{{ route('painel.industrias.index') }}" class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <input name="q" value="{{ request('q') }}" placeholder="Buscar indústria..." class="{{ $input }} w-full sm:flex-1">

            <select name="setor" class="{{ $input }}">
                <option value="">Todos os setores</option>
                @foreach ($setores as $setor)
                    <option value="{{ $setor }}" @selected(request('setor') === $setor)>{{ $setor }}</option>
                @endforeach
            </select>

            <select name="especialidade" class="{{ $input }}">
                <option value="">Qualquer necessidade</option>
                @foreach ($especialidades as $especialidade)
                    <option value="{{ $especialidade->id }}" @selected(request()->integer('especialidade') === $especialidade->id)>
                        Precisa de {{ $especialidade->name }}
                    </option>
                @endforeach
            </select>

            @if ($minhasEspecialidades->isNotEmpty())
                <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2.5 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="minhas" value="1" @checked(request()->boolean('minhas'))
                           class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                    Só as que eu atendo
                </label>
            @endif

            <x-admin.button type="submit" icon="search">Buscar</x-admin.button>
        </form>
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($industrias as $industria)
            @php
                $encaixe = $industria->specialties->whereIn('id', $minhasEspecialidades);
            @endphp
            <div class="flex flex-col gap-3 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <a href="{{ route('painel.industrias.show', $industria->slug) }}"
                       class="text-sm font-semibold text-gray-800 hover:text-brand-600 dark:text-white/90">{{ $industria->name }}</a>

                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                        {{ $industria->sector ?: 'setor não informado' }}
                        @if ($industria->porteLabel()) &middot; {{ $industria->porteLabel() }} @endif
                        @if ($industria->neighborhood) &middot; {{ $industria->neighborhood }} @endif
                    </span>

                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($industria->specialties as $especialidade)
                            @php $minha = $minhasEspecialidades->contains($especialidade->id); @endphp
                            <span @class([
                                'rounded-full px-2.5 py-1 text-xs',
                                'bg-success-50 font-medium text-success-700 dark:bg-success-500/15 dark:text-success-300' => $minha,
                                'bg-gray-100 text-gray-600 dark:bg-white/[0.06] dark:text-gray-300' => ! $minha,
                            ])>{{ $especialidade->name }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="shrink-0 text-right">
                    @if ($encaixe->isNotEmpty())
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-3 py-1.5 text-xs font-semibold text-success-700 dark:bg-success-500/15 dark:text-success-300">
                            <x-admin.icon name="check" class="h-3.5 w-3.5" />
                            você atende {{ $encaixe->count() }} de {{ $industria->specialties->count() }}
                        </span>
                    @endif
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="building" title="Nenhuma indústria encontrada."
                                 description="A associação ainda está montando esse mapa. Volte em alguns dias." />
        @endforelse
    </div>

    @if ($industrias->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $industrias->links() }}</div>
    @endif
</x-admin.card>
@endsection
