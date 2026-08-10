@extends('layouts.admin')

@section('title', 'Atas de reunião')
@section('page_heading', 'Atas de reunião')

@section('content')
<x-admin.card :padding="false">
    <x-slot:title>{{ $total }} {{ $total === 1 ? 'ata' : 'atas' }}</x-slot:title>
    <x-slot:subtitle>Visíveis para as empresas associadas, dentro do painel delas. Não aparecem no site público.</x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('admin.atas.create')" variant="primary" icon="plus">Nova ata</x-admin.button>
    </x-slot:actions>

    <div class="border-b border-gray-100 p-5 dark:border-gray-800">
        <x-admin.filter-bar :action="route('admin.atas.index')" placeholder="Buscar por título ou resumo..." />
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-white/[0.02]">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Ata</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Reunião</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Arquivo</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Situação</th>
                    <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($atas as $ata)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400">
                                    <x-admin.icon name="file" class="h-5 w-5" />
                                </span>
                                <div class="min-w-0">
                                    <strong class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $ata->title }}</strong>
                                    @if ($ata->summary)
                                        <span class="block max-w-md truncate text-xs text-gray-500 dark:text-gray-400">{{ $ata->summary }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                            {{ $ata->meeting_date->translatedFormat('d \d\e F \d\e Y') }}
                        </td>
                        {{-- Uma ata pode ter PDF, texto em Markdown, ou os dois. --}}
                        <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">
                            @if ($ata->hasFile())
                                <span class="block truncate">{{ $ata->file_name }}</span>
                                <span class="text-xs">PDF, {{ $ata->file_size_for_humans }}</span>
                            @endif
                            @if ($ata->hasBody())
                                <span class="mt-1 inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">
                                    <x-admin.icon name="file" class="h-3 w-3" />Texto
                                </span>
                            @endif
                            @unless ($ata->hasFile() || $ata->hasBody())
                                <span class="text-xs text-gray-400">sem conteúdo</span>
                            @endunless
                        </td>
                        <td class="px-5 py-4">
                            @if ($ata->is_published)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-700 dark:bg-success-500/15 dark:text-success-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>Disponível
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-white/[0.06] dark:text-gray-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>Rascunho
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                @if ($ata->hasFile())
                                    <x-admin.button :href="route('admin.atas.download', $ata)" icon="file">Baixar</x-admin.button>
                                @endif
                                @if ($ata->hasBody())
                                    <x-admin.button :href="route('admin.atas.show', $ata)" icon="eye">Ler</x-admin.button>
                                @endif
                                <x-admin.button :href="route('admin.atas.edit', $ata)" icon="edit">Editar</x-admin.button>
                                <x-admin.confirm-form :action="route('admin.atas.destroy', $ata)"
                                                      message="Remover a ata &quot;{{ $ata->title }}&quot;?">Excluir</x-admin.confirm-form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-admin.empty-state icon="file" title="Nenhuma ata publicada."
                                                 description="Envie o PDF das reuniões para que as empresas associadas tenham acesso.">
                                <x-slot:actions>
                                    <x-admin.button :href="route('admin.atas.create')" variant="primary" icon="plus">Nova ata</x-admin.button>
                                </x-slot:actions>
                            </x-admin.empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($atas->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $atas->links() }}</div>
    @endif
</x-admin.card>
@endsection
