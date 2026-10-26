@extends('layouts.admin')

@section('title', 'Assuntos do fórum')
@section('page_heading', 'Assuntos do fórum')

@section('content')
<x-admin.card :padding="false">
    <x-slot:title>{{ $categorias->count() }} {{ $categorias->count() === 1 ? 'assunto' : 'assuntos' }}</x-slot:title>
    <x-slot:subtitle>As gavetas onde os associados abrem tópicos. A ordem aqui é a ordem que eles veem.</x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('admin.forum.categories.create')" variant="primary" icon="plus">Novo assunto</x-admin.button>
    </x-slot:actions>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($categorias as $categoria)
            <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <strong class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $categoria->name }}</strong>

                        @unless ($categoria->is_active)
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-500 dark:bg-white/[0.06] dark:text-gray-400">desativado</span>
                        @endunless
                    </div>

                    @if ($categoria->description)
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $categoria->description }}</p>
                    @endif

                    <p class="mt-1 text-xs text-gray-400">
                        posição {{ $categoria->position }} · {{ $categoria->topics_count }} {{ $categoria->topics_count === 1 ? 'tópico' : 'tópicos' }}
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <x-admin.button :href="route('admin.forum.categories.edit', $categoria)" icon="edit">Editar</x-admin.button>

                    @if ($categoria->topics_count === 0)
                        <x-admin.confirm-form :action="route('admin.forum.categories.destroy', $categoria)"
                                              message="Excluir o assunto &quot;{{ $categoria->name }}&quot;?">Excluir</x-admin.confirm-form>
                    @endif
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="tag" title="Nenhum assunto criado."
                                 description="Sem assunto, os associados não conseguem abrir tópico. Crie os primeiros.">
                <x-slot:actions>
                    <x-admin.button :href="route('admin.forum.categories.create')" variant="primary" icon="plus">Novo assunto</x-admin.button>
                </x-slot:actions>
            </x-admin.empty-state>
        @endforelse
    </div>
</x-admin.card>
@endsection
