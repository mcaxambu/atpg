@extends('layouts.admin')

@section('title', $ata->title)
@section('page_heading', 'Ata de reunião')

@section('content')
<div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $ata->title }}</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Reunião de {{ $ata->meeting_date->translatedFormat('d \d\e F \d\e Y') }}
            @if ($ata->uploader) &middot; enviada por {{ $ata->uploader->name }} @endif
            &middot; {{ $ata->is_published ? 'disponível para as empresas' : 'rascunho' }}
        </p>
    </div>

    <div class="flex flex-wrap gap-2">
        <x-admin.button :href="route('admin.atas.index')" variant="ghost">Voltar</x-admin.button>
        @if ($ata->hasFile())
            <x-admin.button :href="route('admin.atas.download', $ata)" icon="file">Baixar PDF</x-admin.button>
        @endif
        <x-admin.button :href="route('admin.atas.edit', $ata)" variant="primary" icon="edit">Editar</x-admin.button>
    </div>
</div>

@unless ($ata->is_published)
    <div class="flex items-start gap-3 rounded-2xl border border-warning-200 bg-warning-50 p-5 dark:border-warning-500/30 dark:bg-warning-500/10">
        <x-admin.icon name="alert" class="mt-0.5 h-5 w-5 shrink-0 text-warning-600 dark:text-warning-400" />
        <p class="text-sm text-warning-800 dark:text-warning-200">
            Esta ata é um <strong>rascunho</strong>: as empresas ainda não a enxergam.
            Marque "Disponível para as empresas" na edição quando o texto estiver revisado.
        </p>
    </div>
@endunless

<x-admin.card>
    {{-- HTML já sanitizado no model (html_input: escape). --}}
    <div class="ata-conteudo">
        {!! $ata->rendered_body !!}
    </div>
</x-admin.card>
@endsection
