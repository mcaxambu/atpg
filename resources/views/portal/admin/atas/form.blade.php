@extends('layouts.admin')

@section('title', $ata->exists ? 'Editar ata' : 'Nova ata')
@section('page_heading', $ata->exists ? 'Editar ata' : 'Nova ata')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $action = $ata->exists ? route('admin.atas.update', $ata) : route('admin.atas.store');
@endphp

<form method="post" action="{{ $action }}" enctype="multipart/form-data" class="max-w-3xl space-y-6">
    @csrf
    @if ($ata->exists) @method('put') @endif

    <x-admin.card title="Dados da reunião">
        <div class="grid gap-4 sm:grid-cols-3">
            <x-admin.field label="Título" name="title" required class="sm:col-span-2">
                <input type="text" name="title" value="{{ old('title', $ata->title) }}" required
                       placeholder="Ex.: Assembleia Geral Ordinária" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="Data da reunião" name="meeting_date" required>
                <input type="date" name="meeting_date" required max="{{ now()->toDateString() }}"
                       value="{{ old('meeting_date', $ata->meeting_date?->toDateString()) }}" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="Resumo" name="summary" class="sm:col-span-3"
                           hint="Opcional. Aparece na listagem para as empresas localizarem a ata.">
                <textarea name="summary" rows="4" class="{{ $input }}">{{ old('summary', $ata->summary) }}</textarea>
            </x-admin.field>
        </div>
    </x-admin.card>

    <x-admin.card title="Texto da ata"
                  subtitle="Em Markdown. É o que fica legível na tela, funciona no celular e entra na busca.">
        <x-admin.field label="Enviar arquivo .md" name="markdown_file"
                       hint="Opcional. O conteúdo do arquivo substitui o campo abaixo.">
            <input type="file" name="markdown_file" accept=".md,.markdown,text/markdown,text/plain"
                   class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-600 hover:file:bg-brand-100 dark:file:bg-brand-500/15 dark:file:text-brand-300">
        </x-admin.field>

        <x-admin.field label="Ou escreva aqui" name="body" class="mt-4"
                       hint="Markdown: # título, ## subtítulo, - lista, **negrito**, [link](url), | tabelas |">
            <textarea name="body" rows="16"
                      class="{{ $input }} font-mono text-xs leading-relaxed"
                      placeholder="# Assembleia Geral Ordinária&#10;&#10;**Data:** 04/08/2026&#10;**Local:** Sede da associação&#10;&#10;## Pauta&#10;&#10;1. Prestação de contas&#10;2. Eleição da diretoria&#10;&#10;## Deliberações&#10;&#10;- Contas aprovadas por unanimidade">{{ old('body', $ata->body) }}</textarea>
        </x-admin.field>
    </x-admin.card>

    <x-admin.card title="Documento assinado (PDF)"
                  subtitle="O registro formal da reunião. Opcional se você preencheu o texto acima.">
        @if ($ata->hasFile())
            <div class="mb-4 flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400">
                    <x-admin.icon name="file" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <strong class="block truncate text-sm text-gray-800 dark:text-gray-200">{{ $ata->file_name }}</strong>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $ata->file_size_for_humans }}</span>
                </div>
                <x-admin.button :href="route('admin.atas.download', $ata)" icon="file">Baixar</x-admin.button>
                <label class="flex items-center gap-2 text-sm text-error-600 dark:text-error-400">
                    <input type="checkbox" name="remove_file" value="1" class="h-4 w-4 rounded border-gray-300 text-error-500">
                    Remover
                </label>
            </div>
        @endif

        <x-admin.field :label="$ata->hasFile() ? 'Substituir o PDF' : 'PDF da ata'" name="file"
                       hint="{{ $ata->hasFile() ? 'Deixe vazio para manter o arquivo atual. ' : '' }}Somente PDF, até 20 MB.">
            <input type="file" name="file" accept="application/pdf"
                   class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-600 hover:file:bg-brand-100 dark:file:bg-brand-500/15 dark:file:text-brand-300">
        </x-admin.field>

        <label class="mt-4 flex items-start gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700">
            <input type="checkbox" name="is_published" value="1"
                   @checked(old('is_published', $ata->exists ? $ata->is_published : true))
                   class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
            <span>
                <strong class="block text-sm font-medium text-gray-800 dark:text-gray-200">Disponível para as empresas</strong>
                <small class="text-xs text-gray-500 dark:text-gray-400">
                    Desmarque para guardar como rascunho — a ata fica só no painel administrativo.
                </small>
            </span>
        </label>

        <p class="mt-4 rounded-lg bg-gray-50 p-3 text-xs leading-relaxed text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
            O arquivo é guardado fora da pasta pública e só é entregue a quem está autenticado.
            Não existe link direto para o PDF.
        </p>
    </x-admin.card>

    <div class="flex gap-2">
        <x-admin.button type="submit" variant="primary" icon="check">
            {{ $ata->exists ? 'Salvar alterações' : 'Publicar ata' }}
        </x-admin.button>
        <x-admin.button :href="route('admin.atas.index')" variant="ghost">Cancelar</x-admin.button>
    </div>
</form>
@endsection
