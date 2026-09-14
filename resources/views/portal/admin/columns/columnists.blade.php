@extends('layouts.admin')

@section('title', 'Colunistas')
@section('page_heading', 'Colunistas')

@section('content')
<x-admin.card :padding="false">
    <x-slot:title>{{ $columnists->count() }} {{ $columnists->count() === 1 ? 'colunista' : 'colunistas' }}</x-slot:title>
    <x-slot:subtitle>
        A marcação é feita no cadastro: abra o membro ou a empresa e ligue a caixa "É colunista do portal".
    </x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('admin.columns.index')" icon="news">Ver colunas</x-admin.button>
    </x-slot:actions>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($columnists as $columnist)
            @php
                $origem = $columnist->company ?? $columnist->member;
                $rotaDoCadastro = $columnist->isCompany()
                    ? route('admin.companies.edit', $columnist->company_id)
                    : route('admin.members.edit', $columnist->member_id);
            @endphp

            <div class="flex flex-col gap-3 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <x-admin.avatar :photo="$columnist->photo_path" :initials="$columnist->initials"
                                    :contain="$columnist->isCompany()" size="h-11 w-11" />
                    <div class="min-w-0">
                        <strong class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">
                            {{ $columnist->byline }}
                        </strong>
                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400">
                            {{ $columnist->isCompany() ? 'Empresa' : 'Membro' }} &middot; /colunas/{{ $columnist->slug }}
                        </span>
                        <span class="mt-0.5 block text-xs text-gray-400">
                            {{ $columnist->posts_count }} {{ $columnist->posts_count === 1 ? 'coluna' : 'colunas' }}
                        </span>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    @if (! $columnist->is_active)
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-white/[0.06] dark:text-gray-300">Desativado</span>
                    @elseif (! $columnist->isPubliclyVisible())
                        <span class="rounded-full bg-warning-50 px-2.5 py-1 text-xs font-medium text-warning-700 dark:bg-warning-500/15 dark:text-warning-300">Cadastro fora do ar</span>
                    @else
                        <span class="rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-700 dark:bg-success-500/15 dark:text-success-300">No ar</span>
                    @endif

                    @if ($columnist->isPubliclyVisible())
                        <x-admin.button :href="route('colunas.show', $columnist->slug)" target="_blank" icon="globe">Ver página</x-admin.button>
                    @endif

                    <x-admin.button :href="$rotaDoCadastro" icon="edit">Abrir cadastro</x-admin.button>
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="users" title="Nenhum colunista ainda."
                                 description="Abra um membro ou uma empresa e marque a caixa &quot;É colunista do portal&quot;." />
        @endforelse
    </div>
</x-admin.card>
@endsection
