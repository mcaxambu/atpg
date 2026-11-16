@extends('layouts.admin')

@section('title', $industria->name)
@section('page_heading', 'Indústria')

@section('content')
<div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $industria->name }}</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $industria->sector ?: 'setor não informado' }}
            @if ($industria->porteLabel()) &middot; {{ $industria->porteLabel() }} @endif
            @if ($industria->employees) &middot; {{ $industria->employees }} funcionários @endif
            @if ($industria->neighborhood) &middot; {{ $industria->neighborhood }} @endif
        </p>
    </div>

    <div class="flex flex-wrap gap-2">
        <x-admin.button :href="route('admin.industrias.index')" variant="ghost">Voltar</x-admin.button>
        <x-admin.button :href="route('admin.industrias.edit', $industria)" icon="edit">Editar</x-admin.button>
        <x-admin.confirm-form :action="route('admin.industrias.destroy', $industria)" method="delete"
                              icon="trash" message="Remover a indústria &quot;{{ $industria->name }}&quot;?">
            Remover
        </x-admin.confirm-form>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        {{-- A resposta que a tela existe para dar, em primeiro lugar. --}}
        <x-admin.card :title="$atendem->count().' '.($atendem->count() === 1 ? 'associada pode atender' : 'associadas podem atender')"
                      subtitle="Ordenadas por quanto do que a indústria precisa elas cobrem.">
            @forelse ($atendem as $empresa)
                <div class="flex items-center justify-between gap-4 border-b border-gray-100 py-3 last:border-0 dark:border-gray-800">
                    <div class="min-w-0">
                        <a href="{{ route('admin.companies.show', $empresa) }}"
                           class="text-sm font-medium text-gray-800 hover:text-brand-600 dark:text-white/90">{{ $empresa->name }}</a>
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            @foreach ($empresa->specialties as $especialidade)
                                <span class="rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700 dark:bg-success-500/15 dark:text-success-300">
                                    {{ $especialidade->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    <span class="shrink-0 text-xs text-gray-400">
                        {{ $empresa->specialties->count() }} de {{ $industria->specialties->count() }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    @if ($industria->specialties->isEmpty())
                        Esta indústria ainda não tem necessidade marcada, então não há o que cruzar.
                        <a href="{{ route('admin.industrias.edit', $industria) }}" class="text-brand-500 hover:underline">Marcar agora</a>.
                    @else
                        Nenhuma associada marcou as especialidades que esta indústria precisa.
                    @endif
                </p>
            @endforelse
        </x-admin.card>

        @if ($industria->description)
            <x-admin.card title="Sobre">
                <p class="whitespace-pre-line text-sm text-gray-600 dark:text-gray-300">{{ $industria->description }}</p>
            </x-admin.card>
        @endif

        <x-admin.card title="Contato e histórico" subtitle="Só a diretoria enxerga este bloco.">
            <dl class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    'Contato' => $industria->contact_name,
                    'Cargo' => $industria->contact_role,
                    'E-mail' => $industria->contact_email,
                    'Telefone' => $industria->contact_phone,
                    'Último contato' => $industria->last_contact_at?->format('d/m/Y'),
                    'Site' => $industria->site_url,
                ] as $rotulo => $valor)
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $rotulo }}</dt>
                        <dd class="text-sm text-gray-700 dark:text-gray-200">{{ $valor ?: '—' }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($industria->notes)
                <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-800">
                    <p class="whitespace-pre-line text-sm text-gray-600 dark:text-gray-300">{{ $industria->notes }}</p>
                </div>
            @endif
        </x-admin.card>
    </div>

    <x-admin.card title="Do que precisa">
        <div class="flex flex-wrap gap-1.5">
            @forelse ($industria->specialties as $especialidade)
                <span class="rounded-full bg-brand-50 px-3 py-1.5 text-xs font-medium text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                    {{ $especialidade->name }}
                </span>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Nada marcado ainda.</p>
            @endforelse
        </div>
    </x-admin.card>
</div>
@endsection
