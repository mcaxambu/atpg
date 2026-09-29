@extends('layouts.admin')

@section('title', 'Acompanhamento de novos associados')
@section('page_heading', 'Acompanhamento')

@section('content')
<x-admin.card :padding="false">
    <x-slot:title>{{ $associados->count() }} {{ $associados->count() === 1 ? 'associado' : 'associados' }}</x-slot:title>
    <x-slot:subtitle>
        Quem entrou nos últimos {{ $janela }} dias e ainda não completou o perfil.
        Os passos são conferidos no sistema — ninguém marca nada à mão.
    </x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('admin.crm.onboarding', ['completos' => $mostrarCompletos ? null : 1])"
                        :variant="$mostrarCompletos ? 'primary' : 'secondary'" icon="check">
            {{ $mostrarCompletos ? 'Ocultar quem já completou' : 'Mostrar também os completos' }}
        </x-admin.button>
        <x-admin.button :href="route('admin.crm.index')" variant="ghost" icon="handshake">Ir para o funil</x-admin.button>
    </x-slot:actions>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($associados as $progresso)
            @php
                $associado = $progresso->associado;
                $ehEmpresa = $associado instanceof \App\Models\Company;
            @endphp

            <div class="p-5">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex min-w-0 gap-3">
                        <x-admin.avatar :photo="$ehEmpresa ? $associado->logo_path : $associado->photo_path"
                                        :initials="$ehEmpresa ? $associado->initials : $associado->avatar_initials"
                                        :contain="$ehEmpresa" />

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ $ehEmpresa ? route('admin.companies.show', $associado) : route('admin.members.show', $associado) }}"
                                   class="truncate text-sm font-semibold text-gray-800 hover:text-brand-600 dark:text-white/90">{{ $associado->name }}</a>
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600 dark:bg-white/[0.06] dark:text-gray-300">
                                    {{ $ehEmpresa ? 'Empresa' : 'Membro' }}
                                </span>
                            </div>

                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                no portal há {{ $progresso->diasDesdeAprovacao() }} dias
                            </p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                        <div class="w-36">
                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ $progresso->concluidos() }}/{{ $progresso->total() }}</span>
                                <span>{{ $progresso->percentual() }}%</span>
                            </div>
                            <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/[0.06]">
                                <div class="h-full rounded-full {{ $progresso->completo() ? 'bg-success-500' : 'bg-brand-500' }}"
                                     style="width: {{ $progresso->percentual() }}%"></div>
                            </div>
                        </div>

                        @if (! $progresso->completo() && filled($associado->email))
                            <form method="post" action="{{ route('admin.crm.onboarding.remind') }}"
                                  onsubmit="return confirm('Enviar lembrete para {{ $associado->email }}?');">
                                @csrf
                                <input type="hidden" name="tipo" value="{{ $ehEmpresa ? 'company' : 'member' }}">
                                <input type="hidden" name="id" value="{{ $associado->id }}">
                                <x-admin.button type="submit" icon="inbox">Enviar lembrete</x-admin.button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($progresso->passos as $passo)
                        <span title="{{ $passo['ajuda'] }}"
                              class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs
                                     {{ $passo['feito']
                                        ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-300'
                                        : 'bg-gray-100 text-gray-500 dark:bg-white/[0.06] dark:text-gray-400' }}">
                            <x-admin.icon :name="$passo['feito'] ? 'check' : 'x'" class="h-3 w-3" />
                            {{ $passo['titulo'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="check" title="Ninguém pendente por aqui."
                                 description="Todos os associados recentes completaram o que precisavam." />
        @endforelse
    </div>
</x-admin.card>
@endsection
