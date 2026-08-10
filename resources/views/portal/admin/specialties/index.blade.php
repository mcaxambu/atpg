@extends('layouts.admin')

@section('title', 'Especialidades')
@section('page_heading', 'Especialidades')

@section('content')
<x-admin.card :padding="false">
    <x-slot:title>{{ $total }} {{ $total === 1 ? 'especialidade' : 'especialidades' }}</x-slot:title>
    <x-slot:subtitle>Áreas de atuação usadas nos filtros do diretório e nos perfis dos membros.</x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('admin.specialties.create')" variant="primary" icon="plus">Nova especialidade</x-admin.button>
    </x-slot:actions>

    <div class="border-b border-gray-100 p-5 dark:border-gray-800">
        <x-admin.filter-bar :action="route('admin.specialties.index')" placeholder="Buscar especialidade..." />
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-white/[0.02]">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Especialidade</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Slug</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Membros</th>
                    <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($specialties as $specialty)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-300">
                                    <x-admin.icon name="tag" class="h-4 w-4" />
                                </span>
                                <strong class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $specialty->name }}</strong>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-white/[0.06] dark:text-gray-400">{{ $specialty->slug }}</code>
                        </td>
                        <td class="px-5 py-4">
                            @if ($specialty->members_count > 0)
                                <a href="{{ route('admin.members.index', ['specialty' => $specialty->id]) }}"
                                   class="text-sm font-medium text-brand-500 hover:underline">
                                    {{ $specialty->members_count }} {{ $specialty->members_count === 1 ? 'membro' : 'membros' }}
                                </a>
                            @else
                                <span class="text-sm text-gray-400">sem membros</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <x-admin.button :href="route('admin.specialties.edit', $specialty)" icon="edit">Editar</x-admin.button>
                                <x-admin.confirm-form :action="route('admin.specialties.destroy', $specialty)"
                                                      message="Excluir a especialidade {{ $specialty->name }}?">Excluir</x-admin.confirm-form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <x-admin.empty-state icon="tag" title="Nenhuma especialidade encontrada."
                                                 description="As especialidades alimentam os filtros do diretório de membros.">
                                <x-slot:actions>
                                    <x-admin.button :href="route('admin.specialties.create')" variant="primary" icon="plus">Nova especialidade</x-admin.button>
                                </x-slot:actions>
                            </x-admin.empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($specialties->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $specialties->links() }}</div>
    @endif
</x-admin.card>
@endsection
