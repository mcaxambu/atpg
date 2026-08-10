@extends('layouts.admin')

@section('title', 'Membros pendentes')
@section('page_heading', 'Membros aguardando análise')

@section('content')
<x-admin.card :padding="false">
    <x-slot:title>{{ $members->total() }} na fila</x-slot:title>
    <x-slot:subtitle>Profissionais que se cadastraram pelo portal e aguardam aprovação.</x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('admin.members.index')" variant="ghost">Ver todos os membros</x-admin.button>
    </x-slot:actions>

    <div class="border-b border-gray-100 p-5 dark:border-gray-800">
        <x-admin.filter-bar :action="route('admin.members.pending')" placeholder="Buscar na fila..." />
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($members as $member)
            <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex min-w-0 gap-4">
                    <x-admin.avatar :photo="$member->photo_path" :initials="$member->avatar_initials" :color="$member->avatar_color" size="h-14 w-14" />
                    <div class="min-w-0 space-y-1">
                        <strong class="block text-base font-semibold text-gray-800 dark:text-white/90">{{ $member->name }}</strong>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $member->role ?: 'Cargo não informado' }} &middot; {{ $member->company?->name ?? 'Profissional independente' }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $member->city ?: 'Cidade não informada' }} &middot; {{ $member->experience_years }} anos de experiência
                            @if ($member->email) &middot; {{ $member->email }} @endif
                        </p>
                        @if ($member->specialties->isNotEmpty())
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                @foreach ($member->specialties as $specialty)
                                    <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">{{ $specialty->name }}</span>
                                @endforeach
                            </div>
                        @endif
                        <p class="text-xs text-gray-400">Recebido {{ $member->created_at->diffForHumans() }}</p>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    <x-admin.button :href="route('admin.members.show', $member)" icon="eye">Analisar</x-admin.button>
                    <x-admin.button :href="route('admin.members.edit', $member)" icon="edit">Editar</x-admin.button>
                    <x-admin.reject-dialog :action="route('admin.members.reject', $member)" :subject="$member->name" />
                    <x-admin.confirm-form :action="route('admin.members.approve', $member)" method="patch"
                                          variant="success" icon="check"
                                          message="Aprovar e publicar {{ $member->name }} no portal?">Aprovar</x-admin.confirm-form>
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="check" title="Fila vazia."
                                 description="Nenhum membro aguardando análise no momento." />
        @endforelse
    </div>

    @if ($members->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $members->links() }}</div>
    @endif
</x-admin.card>
@endsection
