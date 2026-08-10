@extends('layouts.company')

@section('title', 'Colaboradores')

@section('content')
<x-admin.card :padding="false">
    <x-slot:title>{{ $members->total() }} {{ $members->total() === 1 ? 'colaborador' : 'colaboradores' }}</x-slot:title>
    <x-slot:subtitle>Cada cadastro ou alteração passa pela análise da associação antes de aparecer no portal.</x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('empresa.membros.create')" variant="primary" icon="plus">Novo colaborador</x-admin.button>
    </x-slot:actions>

    <div class="border-b border-gray-100 p-5 dark:border-gray-800">
        <x-admin.filter-bar :action="route('empresa.membros.index')" placeholder="Buscar por nome ou cargo..." />
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($members as $member)
            <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 gap-4">
                    <x-admin.avatar :photo="$member->photo_path" :initials="$member->avatar_initials" :color="$member->avatar_color" size="h-12 w-12" />
                    <div class="min-w-0 space-y-1">
                        <strong class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $member->name }}</strong>
                        <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                            {{ $member->role ?: 'Cargo não informado' }} &middot; {{ $member->experience_years }} anos de experiência
                        </p>
                        @if ($member->specialties->isNotEmpty())
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                @foreach ($member->specialties as $specialty)
                                    <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">{{ $specialty->name }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if ($member->isRejected() && $member->rejection_reason)
                            <p class="mt-1 rounded-lg bg-error-50 px-3 py-2 text-xs text-error-700 dark:bg-error-500/10 dark:text-error-300">
                                <strong>Não aprovado:</strong> {{ $member->rejection_reason }}
                            </p>
                        @endif
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <x-admin.status-badge :status="$member->status" :published="$member->is_active" />
                    <x-admin.button :href="route('empresa.membros.edit', $member->id)" icon="edit">Editar</x-admin.button>
                    <x-admin.confirm-form :action="route('empresa.membros.destroy', $member->id)"
                                          message="Remover {{ $member->name }} da sua equipe?">Remover</x-admin.confirm-form>
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="users" title="Nenhum colaborador ainda."
                                 description="Cadastre as pessoas da empresa que devem aparecer no diretório do portal.">
                <x-slot:actions>
                    <x-admin.button :href="route('empresa.membros.create')" variant="primary" icon="plus">Cadastrar colaborador</x-admin.button>
                </x-slot:actions>
            </x-admin.empty-state>
        @endforelse
    </div>

    @if ($members->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $members->links() }}</div>
    @endif
</x-admin.card>
@endsection
