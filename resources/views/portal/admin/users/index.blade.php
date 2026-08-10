@extends('layouts.admin')

@section('title', 'Usuários')
@section('page_heading', 'Usuários do painel')

@section('content')
<x-admin.card :padding="false">
    <x-slot:title>{{ $users->total() }} {{ $users->total() === 1 ? 'usuário' : 'usuários' }}</x-slot:title>
    <x-slot:subtitle>Quem tem acesso ao painel administrativo do portal.</x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('admin.users.create')" variant="primary" icon="plus">Novo usuário</x-admin.button>
    </x-slot:actions>

    <div class="border-b border-gray-100 p-5 dark:border-gray-800">
        <x-admin.filter-bar :action="route('admin.users.index')" placeholder="Buscar por nome ou e-mail..." />
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($users as $user)
            <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-500 text-sm font-semibold text-white">
                        {{ str($user->name)->substr(0, 1)->upper() }}
                    </span>
                    <div class="min-w-0">
                        <strong class="flex items-center gap-2 truncate text-sm font-semibold text-gray-800 dark:text-white/90">
                            {{ $user->name }}
                            @if ($user->is(auth()->user()))
                                <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-bold text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">VOCÊ</span>
                            @endif
                            <span @class([
                                'rounded-full px-2 py-0.5 text-[10px] font-bold uppercase',
                                'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300' => $user->isAdmin(),
                                'bg-gray-100 text-gray-600 dark:bg-white/[0.06] dark:text-gray-300' => ! $user->isAdmin(),
                            ])>{{ $user->role->label() }}</span>
                            @unless ($user->hasActivatedAccess())
                                <span class="rounded-full bg-warning-50 px-2 py-0.5 text-[10px] font-bold text-warning-700 dark:bg-warning-500/15 dark:text-warning-300">CONVITE PENDENTE</span>
                            @endunless
                        </strong>
                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400">
                            {{ $user->email }}@if ($user->company) &middot; {{ $user->company->name }} @endif
                        </span>
                    </div>
                </div>
                <div class="flex shrink-0 gap-2">
                    <x-admin.button :href="route('admin.users.edit', $user)" icon="edit">Editar</x-admin.button>
                    @unless ($user->is(auth()->user()))
                        <x-admin.confirm-form :action="route('admin.users.destroy', $user)"
                                              message="Remover o acesso de {{ $user->name }}?">Remover</x-admin.confirm-form>
                    @endunless
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="shield" title="Nenhum usuário encontrado." />
        @endforelse
    </div>

    @if ($users->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $users->links() }}</div>
    @endif
</x-admin.card>
@endsection
