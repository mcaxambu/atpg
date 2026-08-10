@extends('layouts.admin')

@section('title', $user->exists ? 'Editar usuário' : 'Novo usuário')
@section('page_heading', $user->exists ? 'Editar usuário' : 'Novo usuário')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $action = $user->exists ? route('admin.users.update', $user) : route('admin.users.store');
@endphp

<form method="post" action="{{ $action }}" class="max-w-2xl space-y-6">
    @csrf
    @if ($user->exists) @method('put') @endif

    <x-admin.card title="Dados de acesso">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin.field label="Nome" name="name" required class="sm:col-span-2">
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="E-mail" name="email" required class="sm:col-span-2">
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="Senha" name="password" :required="! $user->exists"
                           hint="{{ $user->exists ? 'Deixe em branco para manter a senha atual.' : 'Mínimo de 8 caracteres, com letras e números.' }}">
                <input type="password" name="password" autocomplete="new-password" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="Confirmar senha" name="password_confirmation">
                <input type="password" name="password_confirmation" autocomplete="new-password" class="{{ $input }}">
            </x-admin.field>
        </div>
    </x-admin.card>

    <div class="flex gap-2">
        <x-admin.button type="submit" variant="primary" icon="check">
            {{ $user->exists ? 'Salvar alterações' : 'Criar usuário' }}
        </x-admin.button>
        <x-admin.button :href="route('admin.users.index')" variant="ghost">Cancelar</x-admin.button>
    </div>
</form>
@endsection
