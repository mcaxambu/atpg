@extends('layouts.admin')

@section('title', 'Empresas pendentes')
@section('page_heading', 'Empresas aguardando análise')

@section('content')
<x-admin.card :padding="false">
    <x-slot:title>{{ $companies->total() }} na fila</x-slot:title>
    <x-slot:subtitle>Cadastros enviados pelo formulário público, aguardando decisão da associação.</x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('admin.companies.index')" variant="ghost">Ver todas as empresas</x-admin.button>
    </x-slot:actions>

    <div class="border-b border-gray-100 p-5 dark:border-gray-800">
        <x-admin.filter-bar :action="route('admin.companies.pending')" placeholder="Buscar na fila..." />
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($companies as $company)
            <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex min-w-0 gap-4">
                    <x-admin.avatar :photo="$company->logo_path" :initials="$company->display_initials" size="h-14 w-14" contain />
                    <div class="min-w-0 space-y-1">
                        <strong class="block text-base font-semibold text-gray-800 dark:text-white/90">{{ $company->name }}</strong>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $company->legal_name ?: 'Razão social não informada' }} &middot; {{ $company->cnpj ?: 'CNPJ não informado' }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $company->segment ?: 'Segmento não informado' }} &middot; {{ $company->city }}{{ $company->state ? '/'.$company->state : '' }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $company->contact_name ?: 'Responsável não informado' }}
                            @if ($company->contact_role) ({{ $company->contact_role }}) @endif
                            @if ($company->email) &middot; {{ $company->email }} @endif
                            @if ($company->whatsapp) &middot; {{ $company->whatsapp }} @endif
                        </p>
                        <p class="text-xs text-gray-400">Recebido {{ $company->created_at->diffForHumans() }}</p>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    <x-admin.button :href="route('admin.companies.show', $company)" icon="eye">Analisar</x-admin.button>
                    <x-admin.button :href="route('admin.companies.edit', $company)" icon="edit">Editar</x-admin.button>
                    <x-admin.reject-dialog :action="route('admin.companies.reject', $company)" :subject="$company->name" />
                    <x-admin.confirm-form :action="route('admin.companies.approve', $company)" method="patch"
                                          variant="success" icon="check"
                                          message="Aprovar e publicar {{ $company->name }} no portal?">Aprovar</x-admin.confirm-form>
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="check" title="Fila vazia."
                                 description="Nenhuma empresa aguardando análise no momento." />
        @endforelse
    </div>

    @if ($companies->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $companies->links() }}</div>
    @endif
</x-admin.card>
@endsection
