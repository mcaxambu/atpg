@extends('layouts.admin')

@section('title', 'Empresas')
@section('page_heading', 'Empresas')

@section('content')
<x-admin.card :padding="false">
    <x-slot:title>{{ $companies->total() }} {{ $companies->total() === 1 ? 'empresa' : 'empresas' }}</x-slot:title>
    <x-slot:subtitle>Cadastro completo, aprovação e publicação no diretório.</x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('admin.companies.trash')" variant="ghost" icon="trash">Lixeira</x-admin.button>
        <x-admin.button :href="route('admin.companies.create')" variant="primary" icon="plus">Nova empresa</x-admin.button>
    </x-slot:actions>

    <div class="border-b border-gray-100 p-5 dark:border-gray-800">
        <x-admin.filter-bar :action="route('admin.companies.index')" placeholder="Buscar por nome, CNPJ, segmento, cidade...">
            <select name="status" class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">Todas as situações</option>
                @foreach (\App\Enums\ModerationStatus::options() as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>
                        {{ $label }} ({{ $statusCounts[$value] ?? 0 }})
                    </option>
                @endforeach
            </select>

            <select name="segment" class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">Todos os segmentos</option>
                @foreach ($segments as $segment)
                    <option value="{{ $segment }}" @selected(request('segment') === $segment)>{{ $segment }}</option>
                @endforeach
            </select>
        </x-admin.filter-bar>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-white/[0.02]">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Empresa</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Segmento</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Membros</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Situação</th>
                    <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($companies as $company)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <x-admin.avatar :photo="$company->logo_path" :initials="$company->display_initials" size="h-10 w-10" contain />
                                <div class="min-w-0">
                                    <strong class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $company->name }}</strong>
                                    <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ $company->city }}{{ $company->state ? '/'.$company->state : '' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $company->segment ?: '—' }}</td>
                        <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $company->members_count }}</td>
                        <td class="px-5 py-4"><x-admin.status-badge :status="$company->status" :published="$company->is_active" /></td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-admin.button :href="route('admin.companies.show', $company)" icon="eye">Ver</x-admin.button>
                                <x-admin.button :href="route('admin.companies.edit', $company)" icon="edit">Editar</x-admin.button>
                                <x-admin.confirm-form :action="route('admin.companies.destroy', $company)"
                                                      message="Mover {{ $company->name }} para a lixeira?">Excluir</x-admin.confirm-form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-admin.empty-state icon="building"
                                                 title="Nenhuma empresa encontrada."
                                                 description="Ajuste os filtros ou cadastre a primeira empresa do portal.">
                                <x-slot:actions>
                                    <x-admin.button :href="route('admin.companies.create')" variant="primary" icon="plus">Nova empresa</x-admin.button>
                                </x-slot:actions>
                            </x-admin.empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($companies->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $companies->links() }}</div>
    @endif
</x-admin.card>
@endsection
