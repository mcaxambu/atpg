@extends('layouts.admin')

@section('title', 'Lixeira de membros')
@section('page_heading', 'Lixeira de membros')

@section('content')
<x-admin.card :padding="false">
    <x-slot:title>{{ $members->total() }} na lixeira</x-slot:title>
    <x-slot:subtitle>Registros excluídos podem ser restaurados. A exclusão definitiva apaga também a foto.</x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('admin.members.index')" variant="ghost">Voltar aos membros</x-admin.button>
    </x-slot:actions>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($members as $member)
            <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <x-admin.avatar :photo="$member->photo_path" :initials="$member->avatar_initials" :color="$member->avatar_color" />
                    <div class="min-w-0">
                        <strong class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $member->name }}</strong>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                            {{ $member->company?->name ?? 'Independente' }} &middot; excluído {{ $member->deleted_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
                <div class="flex shrink-0 gap-2">
                    <x-admin.confirm-form :action="route('admin.members.restore', $member->id)" method="patch"
                                          variant="success" icon="restore"
                                          message="Restaurar {{ $member->name }}?">Restaurar</x-admin.confirm-form>
                    <x-admin.confirm-form :action="route('admin.members.force-delete', $member->id)"
                                          message="Excluir {{ $member->name }} DEFINITIVAMENTE? Esta ação não pode ser desfeita.">Excluir de vez</x-admin.confirm-form>
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="trash" title="Lixeira vazia." description="Nenhum membro excluído." />
        @endforelse
    </div>

    @if ($members->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $members->links() }}</div>
    @endif
</x-admin.card>
@endsection
