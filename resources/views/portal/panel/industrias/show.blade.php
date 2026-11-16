{{-- Ficha da indústria para o associado: sem contato, sem anotação. --}}
@extends($layout)

@section('title', $industria->name)
@section('page_heading', 'Indústria')

@section('content')
@php
    $encaixe = $industria->specialties->whereIn('id', $minhasEspecialidades);
@endphp

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

    <x-admin.button :href="route('painel.industrias.index')" variant="ghost">Voltar</x-admin.button>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <x-admin.card title="Do que esta indústria precisa">
            <div class="flex flex-wrap gap-2">
                @forelse ($industria->specialties as $especialidade)
                    @php $minha = $minhasEspecialidades->contains($especialidade->id); @endphp
                    <span @class([
                        'rounded-full px-3 py-1.5 text-xs font-medium',
                        'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-300' => $minha,
                        'bg-gray-100 text-gray-600 dark:bg-white/[0.06] dark:text-gray-300' => ! $minha,
                    ])>{{ $especialidade->name }}</span>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Ainda não mapeado.</p>
                @endforelse
            </div>

            @if ($encaixe->isNotEmpty())
                <p class="mt-4 border-t border-gray-100 pt-4 text-sm text-success-700 dark:border-gray-800 dark:text-success-300">
                    Em verde está o que <strong>você</strong> faz: {{ $encaixe->count() }} de {{ $industria->specialties->count() }}.
                    Fale com a diretoria se quiser ser apresentado.
                </p>
            @elseif ($minhasEspecialidades->isNotEmpty())
                <p class="mt-4 border-t border-gray-100 pt-4 text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                    Nada aqui bate com o que você marcou no seu perfil.
                </p>
            @endif
        </x-admin.card>

        @if ($industria->description)
            <x-admin.card title="Sobre">
                <p class="whitespace-pre-line text-sm text-gray-600 dark:text-gray-300">{{ $industria->description }}</p>
            </x-admin.card>
        @endif

        {{-- Só para a diretoria: ver quem mais atende não ajuda a associada a
             trabalhar, e azeda a convivência entre quem concorre. --}}
        @if ($atendem->isNotEmpty())
            <x-admin.card :title="$atendem->count().' associadas podem atender'">
                @foreach ($atendem as $outra)
                    <div class="border-b border-gray-100 py-2 text-sm text-gray-700 last:border-0 dark:border-gray-800 dark:text-gray-200">
                        {{ $outra->name }}
                        <span class="text-xs text-gray-400">— {{ $outra->specialties->count() }} de {{ $industria->specialties->count() }}</span>
                    </div>
                @endforeach
            </x-admin.card>
        @endif
    </div>

    <x-admin.card title="Onde fica">
        <dl class="space-y-3 text-sm">
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Cidade</dt>
                <dd class="text-gray-700 dark:text-gray-200">{{ $industria->city }}/{{ $industria->state }}</dd>
            </div>
            @if ($industria->neighborhood)
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Bairro</dt>
                    <dd class="text-gray-700 dark:text-gray-200">{{ $industria->neighborhood }}</dd>
                </div>
            @endif
            @if ($industria->site_url)
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Site</dt>
                    <dd>
                        <a href="{{ $industria->site_url }}" target="_blank" rel="noopener noreferrer"
                           class="text-brand-600 hover:underline dark:text-brand-400">abrir</a>
                    </dd>
                </div>
            @endif
        </dl>
    </x-admin.card>
</div>
@endsection
