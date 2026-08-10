@extends('layouts.admin')

@section('title', 'Meu perfil')
@section('page_heading', 'Meu perfil')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
@endphp

<div class="grid max-w-4xl gap-6 lg:grid-cols-2">
    <form method="post" action="{{ route('admin.profile.update') }}" class="space-y-4">
        @csrf
        @method('put')

        <x-admin.card title="Dados pessoais">
            <div class="space-y-4">
                <x-admin.field label="Nome" name="name" required>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="{{ $input }}">
                </x-admin.field>

                <x-admin.field label="E-mail" name="email" required>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="{{ $input }}">
                </x-admin.field>

                <x-admin.button type="submit" variant="primary" icon="check">Salvar dados</x-admin.button>
            </div>
        </x-admin.card>
    </form>

    <form method="post" action="{{ route('admin.profile.password') }}" class="space-y-4">
        @csrf
        @method('put')

        <x-admin.card title="Alterar senha">
            <div class="space-y-4">
                <x-admin.field label="Senha atual" name="current_password" required>
                    <input type="password" name="current_password" autocomplete="current-password" required class="{{ $input }}">
                </x-admin.field>

                <x-admin.field label="Nova senha" name="password" required hint="Mínimo de 8 caracteres, com letras e números.">
                    <input type="password" name="password" autocomplete="new-password" required class="{{ $input }}">
                </x-admin.field>

                <x-admin.field label="Confirmar nova senha" name="password_confirmation" required>
                    <input type="password" name="password_confirmation" autocomplete="new-password" required class="{{ $input }}">
                </x-admin.field>

                <x-admin.button type="submit" variant="primary" icon="shield">Alterar senha</x-admin.button>
            </div>
        </x-admin.card>
    </form>
</div>
@endsection
