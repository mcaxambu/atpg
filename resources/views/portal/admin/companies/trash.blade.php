@extends('layouts.admin')

@section('title', 'Lixeira de empresas')
@section('page_heading', 'Lixeira de empresas')

@section('content')
<x-admin.card :padding="false">
    <x-slot:title>{{ $companies->total() }} na lixeira</x-slot:title>
    <x-slot:subtitle>Registros excluídos podem ser restaurados. A exclusão definitiva apaga também a logo.</x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('admin.companies.index')" variant="ghost">Voltar às empresas</x-admin.button>
    </x-slot:actions>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($companies as $company)
            <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <x-admin.avatar :photo="$company->logo_path" :initials="$company->display_initials" contain />
                    <div class="min-w-0">
                        <strong class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $company->name }}</strong>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                            Excluída {{ $company->deleted_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
                <div class="flex shrink-0 gap-2">
                    <x-admin.confirm-form :action="route('admin.companies.restore', $company->id)" method="patch"
                                          variant="success" icon="restore"
                                          message="Restaurar {{ $company->name }}?">Restaurar</x-admin.confirm-form>
                    <x-admin.confirm-form :action="route('admin.companies.force-delete', $company->id)"
                                          message="Excluir {{ $company->name }} DEFINITIVAMENTE? Esta ação não pode ser desfeita.">Excluir de vez</x-admin.confirm-form>
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="trash" title="Lixeira vazia."
                                 description="Nenhuma empresa excluída." />
        @endforelse
    </div>

    @if ($companies->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $companies->links() }}</div>
    @endif
</x-admin.card>
@endsection
