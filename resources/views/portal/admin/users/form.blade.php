@extends('layouts.admin')

@section('title', $user->exists ? 'Editar usuário' : 'Novo usuário')
@section('page_heading', $user->exists ? 'Editar usuário' : 'Novo usuário')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $action = $user->exists ? route('admin.users.update', $user) : route('admin.users.store');

    $currentRole = old('role', $user->role?->value ?? 'admin');
    $currentAbilities = old('abilities', $user->abilities ?? []);
    // Usuário novo nasce com acesso total marcado; ao editar, respeita o que está salvo.
    $currentFullAccess = (bool) old('full_access', $user->exists ? $user->hasFullAccess() : true);
    $isSelf = $user->exists && $user->is(auth()->user());
@endphp

<form method="post" action="{{ $action }}" class="max-w-3xl space-y-6"
      x-data="{ role: '{{ $currentRole }}', fullAccess: {{ $currentFullAccess ? 'true' : 'false' }} }">
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

    <x-admin.card title="Perfil de acesso">
        <x-admin.field name="role" required
                       hint="Define qual painel a pessoa enxerga ao entrar.">
            <div class="space-y-2">
                @foreach ($roles as $role)
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors"
                           :class="role === '{{ $role->value }}'
                               ? 'border-brand-400 bg-brand-50 dark:border-brand-500 dark:bg-brand-500/10'
                               : 'border-gray-200 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-white/[0.03]'">
                        <input type="radio" name="role" value="{{ $role->value }}" x-model="role"
                               class="mt-0.5 h-4 w-4 border-gray-300 text-brand-600 focus:ring-brand-500">
                        <span class="min-w-0">
                            <span class="block text-sm font-medium text-gray-800 dark:text-white">{{ $role->label() }}</span>
                            <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">{{ $role->description() }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </x-admin.field>

        {{-- Empresa: vínculo obrigatório --}}
        <div x-show="role === 'company'" x-cloak class="mt-4">
            <x-admin.field label="Empresa vinculada" name="company_id"
                           hint="O usuário verá apenas os dados desta empresa e os colaboradores dela.">
                <select name="company_id" class="{{ $input }}">
                    <option value="">Selecione a empresa…</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected(old('company_id', $user->company_id) == $company->id)>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
            </x-admin.field>
        </div>

        {{-- Membro: vínculo obrigatório --}}
        <div x-show="role === 'member'" x-cloak class="mt-4">
            <x-admin.field label="Cadastro do membro" name="member_id"
                           hint="O usuário poderá editar apenas este cadastro do diretório.">
                <select name="member_id" class="{{ $input }}">
                    <option value="">Selecione o cadastro…</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->id }}" @selected(old('member_id', $user->member_id) == $member->id)>
                            {{ $member->name }}@if ($member->email) — {{ $member->email }}@endif
                        </option>
                    @endforeach
                </select>
            </x-admin.field>
        </div>
    </x-admin.card>

    {{-- Módulos: só fazem sentido para a diretoria --}}
    <div x-show="role === 'admin'" x-cloak>
        <x-admin.card title="Módulos liberados">
            @error('abilities')
                <p class="mb-4 rounded-lg border border-error-500/30 bg-error-50 px-3 py-2 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">
                    {{ $message }}
                </p>
            @enderror

            <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors"
                   :class="fullAccess
                       ? 'border-brand-400 bg-brand-50 dark:border-brand-500 dark:bg-brand-500/10'
                       : 'border-gray-200 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-white/[0.03]'">
                <input type="checkbox" name="full_access" value="1" x-model="fullAccess"
                       class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                <span>
                    <span class="block text-sm font-medium text-gray-800 dark:text-white">Acesso total (diretoria plena)</span>
                    <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">
                        Enxerga todos os módulos, inclusive os que forem criados no futuro.
                    </span>
                </span>
            </label>

            <div x-show="! fullAccess" x-cloak class="mt-4 space-y-2">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Marque o que este usuário pode acessar. O painel some do menu e a URL direta é bloqueada.
                </p>

                @foreach ($modules as $module)
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-white/[0.03]">
                        <input type="checkbox" name="abilities[]" value="{{ $module->value }}"
                               @checked(in_array($module->value, (array) $currentAbilities, true))
                               class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        <span class="min-w-0">
                            <span class="block text-sm font-medium text-gray-800 dark:text-white">
                                {{ $module->label() }}
                                @if ($module->isSensitive())
                                    <span class="ml-1 rounded bg-warning-50 px-1.5 py-0.5 text-[11px] font-semibold text-warning-600 dark:bg-warning-500/10">
                                        dá controle do painel
                                    </span>
                                @endif
                            </span>
                            <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">{{ $module->description() }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            @if ($isSelf && ! auth()->user()->hasFullAccess())
                <p class="mt-4 rounded-lg border border-warning-500/30 bg-warning-50 px-3 py-2 text-xs text-warning-700 dark:bg-warning-500/10 dark:text-warning-500">
                    Você está editando o próprio usuário: papel, módulos e vínculos não serão alterados.
                </p>
            @endif
        </x-admin.card>
    </div>

    <div class="flex gap-2">
        <x-admin.button type="submit" variant="primary" icon="check">
            {{ $user->exists ? 'Salvar alterações' : 'Criar usuário' }}
        </x-admin.button>
        <x-admin.button :href="route('admin.users.index')" variant="ghost">Cancelar</x-admin.button>
    </div>
</form>
@endsection
