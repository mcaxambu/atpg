@extends('layouts.company')

@section('title', $ata->title)

@section('content')
<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <a href="{{ route('empresa.atas.index') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
            <x-admin.icon name="chevron-down" class="h-4 w-4 rotate-90" />Voltar às atas
        </a>
        <h1 class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $ata->title }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Reunião de {{ $ata->meeting_date->translatedFormat('d \d\e F \d\e Y') }}
        </p>
    </div>

    @if ($ata->hasFile())
        <x-admin.button :href="route('empresa.atas.download', $ata->id)" variant="primary" icon="file" class="shrink-0">
            Baixar PDF assinado
        </x-admin.button>
    @endif
</div>

<x-admin.card>
    {{-- O HTML vem do Markdown já sanitizado no model (html_input: escape). --}}
    <div class="ata-conteudo">
        {!! $ata->rendered_body !!}
    </div>
</x-admin.card>

@push('scripts')
@endpush
@endsection
