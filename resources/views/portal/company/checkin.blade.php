@extends('layouts.company')

@section('title', 'Check-in')

@section('content')
{{--
    Tela que o QR abre no celular de quem chega.

    É curta de propósito: quem está nela está em pé, na porta da reunião, com
    o crachá numa mão e o celular na outra.
--}}
<div class="mx-auto max-w-lg space-y-6">
    @if (! $reuniao)
        <x-admin.card title="Nenhuma reunião acontecendo agora">
            <p class="text-sm text-gray-600 dark:text-gray-300">
                O check-in fica aberto de uma hora antes até três horas depois do fim da reunião.
                Se a reunião está começando agora, tente de novo em instantes.
            </p>

            <div class="mt-4">
                <x-admin.button :href="route('empresa.reunioes.index')" icon="calendar">Ver minhas reuniões</x-admin.button>
            </div>
        </x-admin.card>
    @elseif ($presenca?->fezCheckin())
        <x-admin.card>
            <div class="py-4 text-center">
                <div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-success-500 text-3xl text-white">✓</div>

                <h2 class="mt-4 text-lg font-semibold text-gray-800 dark:text-white/90">Presença registrada</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    {{ $empresa?->name }} — {{ $reuniao->title }}
                </p>
                <p class="mt-0.5 text-xs text-gray-400">
                    às {{ $presenca->checked_in_at->format('H:i') }}
                </p>
            </div>
        </x-admin.card>
    @else
        <x-admin.card :title="$reuniao->title"
                      :subtitle="$reuniao->scheduled_at?->translatedFormat('l, d/m \à\s H:i').($reuniao->location ? ' · '.$reuniao->location : '')">
            <form method="post" action="{{ route('empresa.checkin.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="meeting_id" value="{{ $reuniao->id }}">

                <x-admin.field label="Código mostrado na sala" name="codigo" required
                               hint="Está no telão, no tablet da recepção ou com a diretoria.">
                    <input type="text" name="codigo" required autofocus autocomplete="off"
                           inputmode="text" maxlength="10"
                           class="w-full rounded-xl border border-gray-300 bg-white px-4 py-4 text-center text-2xl font-bold uppercase tracking-[0.3em] text-gray-900 focus:border-brand-400 focus:outline-none focus:ring-4 focus:ring-brand-500/15 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </x-admin.field>

                <button type="submit"
                        class="w-full rounded-xl bg-brand-500 px-5 py-4 text-base font-semibold text-white transition hover:bg-brand-600">
                    Registrar minha presença
                </button>
            </form>

            <p class="mt-4 text-center text-xs text-gray-400">
                Entrando como <strong class="font-medium text-gray-600 dark:text-gray-300">{{ $empresa?->name }}</strong>
            </p>
        </x-admin.card>
    @endif

    @if ($outras->isNotEmpty())
        <x-admin.card title="Outras reuniões abertas agora">
            <ul class="space-y-2 text-sm">
                @foreach ($outras as $outra)
                    <li class="text-gray-600 dark:text-gray-300">{{ $outra->title }}</li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs text-gray-400">
                Fale com a diretoria: o check-in acima vale para a primeira reunião da lista.
            </p>
        </x-admin.card>
    @endif
</div>
@endsection
