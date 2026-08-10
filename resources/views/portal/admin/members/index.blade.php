@extends('layouts.admin')

@section('title', 'Membros')
@section('page_heading', 'Membros')

@section('content')
<x-admin.card :padding="false">
    <x-slot:title>{{ $members->total() }} {{ $members->total() === 1 ? 'membro' : 'membros' }}</x-slot:title>
    <x-slot:subtitle>Perfis profissionais do diretório da associação.</x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('admin.members.trash')" variant="ghost" icon="trash">Lixeira</x-admin.button>
        <x-admin.button :href="route('admin.members.create')" variant="primary" icon="plus">Novo membro</x-admin.button>
    </x-slot:actions>

    <div class="border-b border-gray-100 p-5 dark:border-gray-800">
        <x-admin.filter-bar :action="route('admin.members.index')" placeholder="Buscar por nome, cargo, cidade, empresa...">
            <select name="status" class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">Todas as situações</option>
                @foreach (\App\Enums\ModerationStatus::options() as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }} ({{ $statusCounts[$value] ?? 0 }})</option>
                @endforeach
            </select>

            <select name="specialty" class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">Todas as especialidades</option>
                @foreach ($specialties as $specialty)
                    <option value="{{ $specialty->id }}" @selected(request('specialty') == $specialty->id)>{{ $specialty->name }}</option>
                @endforeach
            </select>

            <select name="company" class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">Todas as empresas</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected(request('company') == $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
        </x-admin.filter-bar>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-white/[0.02]">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Membro</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Empresa</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Especialidades</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Situação</th>
                    <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($members as $member)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <x-admin.avatar :photo="$member->photo_path" :initials="$member->avatar_initials" :color="$member->avatar_color" size="h-10 w-10" />
                                <div class="min-w-0">
                                    <strong class="flex items-center gap-1.5 truncate text-sm font-semibold text-gray-800 dark:text-white/90">
                                        {{ $member->name }}
                                        @if ($member->is_featured)
                                            <x-admin.icon name="star" class="h-3.5 w-3.5 text-warning-500" />
                                        @endif
                                    </strong>
                                    <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ $member->role ?: 'Cargo não informado' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $member->company?->name ?? 'Independente' }}</td>
                        <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $member->specialties->pluck('name')->join(', ') ?: '—' }}</td>
                        <td class="px-5 py-4"><x-admin.status-badge :status="$member->status" :published="$member->is_active" /></td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-admin.button :href="route('admin.members.show', $member)" icon="eye">Ver</x-admin.button>
                                <x-admin.button :href="route('admin.members.edit', $member)" icon="edit">Editar</x-admin.button>
                                <x-admin.confirm-form :action="route('admin.members.destroy', $member)"
                                                      message="Mover {{ $member->name }} para a lixeira?">Excluir</x-admin.confirm-form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-admin.empty-state icon="users" title="Nenhum membro encontrado."
                                                 description="Ajuste os filtros ou cadastre o primeiro membro.">
                                <x-slot:actions>
                                    <x-admin.button :href="route('admin.members.create')" variant="primary" icon="plus">Novo membro</x-admin.button>
                                </x-slot:actions>
                            </x-admin.empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($members->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $members->links() }}</div>
    @endif
</x-admin.card>
@endsection
