@extends('layouts.membro')

@section('title', 'Meu acesso')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
@endphp

<div>
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Meu acesso</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        E-mail e senha usados para entrar. Alterar aqui não mexe no seu cadastro do diretório.
    </p>
</div>

<div class="grid max-w-4xl gap-6 lg:grid-cols-2">
    <form method="post" action="{{ route('membro.conta.update') }}" class="space-y-4">
        @csrf
        @method('put')

        <x-admin.card title="Dados pessoais">
            <div class="space-y-4">
                <x-admin.field label="Nome" name="name" required>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="{{ $input }}">
                </x-admin.field>

                <x-admin.field label="E-mail" name="email" required hint="É também o seu login.">
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="{{ $input }}">
                </x-admin.field>

                <x-admin.button type="submit" variant="primary" icon="check">Salvar</x-admin.button>
            </div>
        </x-admin.card>
    </form>

    <form method="post" action="{{ route('membro.conta.senha') }}" class="space-y-4">
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
