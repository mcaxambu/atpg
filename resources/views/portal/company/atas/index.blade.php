@extends('layouts.company')

@section('title', 'Atas de reunião')

@section('content')
<div>
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Atas de reunião</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Documentos das reuniões da associação, disponíveis para as empresas associadas.
    </p>
</div>

<x-admin.card :padding="false">
    <x-slot:title>{{ $atas->total() }} {{ $atas->total() === 1 ? 'ata disponível' : 'atas disponíveis' }}</x-slot:title>

    <div class="border-b border-gray-100 p-5 dark:border-gray-800">
        <x-admin.filter-bar :action="route('empresa.atas.index')" placeholder="Buscar por título ou assunto..." />
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($atas as $ata)
            <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400">
                        <x-admin.icon name="file" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <strong class="block text-sm font-semibold text-gray-800 dark:text-white/90">{{ $ata->title }}</strong>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                            Reunião de {{ $ata->meeting_date->translatedFormat('d \d\e F \d\e Y') }}
                            @if ($ata->hasFile()) &middot; PDF, {{ $ata->file_size_for_humans }} @endif
                        </span>
                        @if ($ata->summary)
                            <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $ata->summary }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    @if ($ata->hasBody())
                        <x-admin.button :href="route('empresa.atas.show', $ata->id)" variant="primary" icon="eye">
                            Ler ata
                        </x-admin.button>
                    @endif
                    @if ($ata->hasFile())
                        <x-admin.button :href="route('empresa.atas.download', $ata->id)"
                                        :variant="$ata->hasBody() ? 'secondary' : 'primary'" icon="file">
                            Baixar PDF
                        </x-admin.button>
                    @endif
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="file" title="Nenhuma ata disponível ainda."
                                 description="Quando a associação publicar as atas das reuniões, elas aparecem aqui." />
        @endforelse
    </div>

    @if ($atas->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $atas->links() }}</div>
    @endif
</x-admin.card>
@endsection
