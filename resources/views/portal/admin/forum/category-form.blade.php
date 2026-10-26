@extends('layouts.admin')

@section('title', $categoria->exists ? 'Editar assunto' : 'Novo assunto')
@section('page_heading', $categoria->exists ? 'Editar assunto' : 'Novo assunto')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $action = $categoria->exists ? route('admin.forum.categories.update', $categoria) : route('admin.forum.categories.store');
@endphp

<form method="post" action="{{ $action }}" class="max-w-2xl space-y-6">
    @csrf
    @if ($categoria->exists) @method('put') @endif

    <x-admin.card title="Assunto">
        <div class="space-y-4">
            <x-admin.field label="Nome" name="name" required>
                <input type="text" name="name" maxlength="80" required value="{{ old('name', $categoria->name) }}"
                       placeholder="Indicações e parcerias" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="Descrição" name="description" hint="Uma linha dizendo o que cabe aqui.">
                <input type="text" name="description" maxlength="160" value="{{ old('description', $categoria->description) }}" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="Posição" name="position" hint="Menor aparece primeiro na lista dos associados.">
                <input type="number" name="position" min="0" max="999" value="{{ old('position', $categoria->position ?? 0) }}" class="{{ $input }}">
            </x-admin.field>

            <label class="flex items-start gap-3">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $categoria->is_active ?? true))
                       class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-500">
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    Aceita tópicos novos
                    <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">
                        Desmarcado, o assunto some da lista de quem vai abrir tópico, mas o que já foi escrito continua legível.
                    </span>
                </span>
            </label>
        </div>
    </x-admin.card>

    <div class="flex flex-wrap items-center gap-3">
        <x-admin.button type="submit" variant="primary" icon="check">Salvar</x-admin.button>
        <x-admin.button :href="route('admin.forum.categories.index')" variant="ghost">Cancelar</x-admin.button>
    </div>
</form>
@endsection
