@extends('layouts.admin')

@section('title', $specialty->exists ? 'Editar especialidade' : 'Nova especialidade')
@section('page_heading', $specialty->exists ? 'Editar especialidade' : 'Nova especialidade')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $action = $specialty->exists ? route('admin.specialties.update', $specialty) : route('admin.specialties.store');
@endphp

<form method="post" action="{{ $action }}" class="max-w-2xl space-y-6">
    @csrf
    @if ($specialty->exists) @method('put') @endif

    <x-admin.card title="Dados da especialidade"
                  subtitle="Aparece nos filtros do diretório, nos cards e nos perfis dos membros.">
        <div class="space-y-4">
            <x-admin.field label="Nome" name="name" required hint="O slug é gerado automaticamente a partir do nome.">
                <input type="text" name="name" value="{{ old('name', $specialty->name) }}" required autofocus
                       placeholder="Ex.: Desenvolvimento Web" class="{{ $input }}">
            </x-admin.field>

            @if ($specialty->exists)
                <div class="flex flex-wrap gap-6 border-t border-gray-100 pt-4 dark:border-gray-800">
                    <div>
                        <span class="block text-xs font-medium uppercase tracking-wide text-gray-400">Slug atual</span>
                        <code class="mt-1 inline-block rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-white/[0.06] dark:text-gray-400">{{ $specialty->slug }}</code>
                    </div>
                    <div>
                        <span class="block text-xs font-medium uppercase tracking-wide text-gray-400">Membros vinculados</span>
                        <strong class="mt-1 block text-sm text-gray-800 dark:text-gray-200">{{ $specialty->members_count ?? $specialty->members()->count() }}</strong>
                    </div>
                </div>
            @endif
        </div>
    </x-admin.card>

    <div class="flex gap-2">
        <x-admin.button type="submit" variant="primary" icon="check">
            {{ $specialty->exists ? 'Salvar alterações' : 'Cadastrar especialidade' }}
        </x-admin.button>
        <x-admin.button :href="route('admin.specialties.index')" variant="ghost">Cancelar</x-admin.button>
    </div>
</form>
@endsection
