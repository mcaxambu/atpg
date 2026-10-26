@extends('layouts.admin')

@section('title', 'Tópicos do fórum')
@section('page_heading', 'Tópicos do fórum')

@section('content')
<x-admin.card :padding="false">
    <x-slot:title>{{ $topicos->total() }} {{ $topicos->total() === 1 ? 'tópico' : 'tópicos' }}</x-slot:title>
    <x-slot:subtitle>
        O fórum não passa por aprovação prévia: conversa que só aparece depois de liberada deixa de ser conversa.
        A moderação age aqui, e tudo é reversível.
    </x-slot:subtitle>

    <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 p-5 dark:border-gray-800">
        @foreach (['todos' => 'Todos', 'ocultos' => 'Ocultos', 'fechados' => 'Fechados'] as $valor => $rotulo)
            <a href="{{ route('admin.forum.topics.index', ['filtro' => $valor]) }}"
               class="rounded-lg px-3 py-2 text-sm {{ $filtro === $valor ? 'bg-brand-50 font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/[0.04]' }}">
                {{ $rotulo }} ({{ $totais[$valor] }})
            </a>
        @endforeach

        <form method="get" action="{{ route('admin.forum.topics.index') }}" class="ml-auto flex gap-2">
            <input type="hidden" name="filtro" value="{{ $filtro }}">
            <input type="search" name="q" value="{{ $busca }}" placeholder="Buscar por título..."
                   class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            <x-admin.button type="submit" icon="search">Buscar</x-admin.button>
        </form>
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($topicos as $topico)
            <div class="flex flex-col gap-3 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('admin.forum.topics.show', $topico) }}"
                           class="truncate text-sm font-semibold text-gray-800 hover:text-brand-600 dark:text-white/90">{{ $topico->title }}</a>

                        @if ($topico->is_pinned)
                            <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[11px] text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">fixado</span>
                        @endif
                        @if ($topico->is_closed)
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-500 dark:bg-white/[0.06] dark:text-gray-400">fechado</span>
                        @endif
                        @if ($topico->is_hidden)
                            <span class="rounded-full bg-error-50 px-2 py-0.5 text-[11px] text-error-700 dark:bg-error-500/10 dark:text-error-300">oculto</span>
                        @endif
                    </div>

                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        {{ $topico->author?->assinaturaNoForum() ?? 'Associado' }}
                        · {{ $topico->category?->name }}
                        · {{ $topico->replies_count }} {{ $topico->replies_count === 1 ? 'resposta' : 'respostas' }}
                        · {{ $topico->ultimaAtividade()?->diffForHumans() }}
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    @foreach (['fixar' => $topico->is_pinned ? 'Desafixar' : 'Fixar', 'fechar' => $topico->is_closed ? 'Reabrir' : 'Fechar', 'ocultar' => $topico->is_hidden ? 'Mostrar' : 'Ocultar'] as $acao => $rotulo)
                        <form method="post" action="{{ route('admin.forum.topics.toggle', $topico) }}">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="acao" value="{{ $acao }}">
                            <x-admin.button type="submit">{{ $rotulo }}</x-admin.button>
                        </form>
                    @endforeach

                    <x-admin.button :href="route('admin.forum.topics.show', $topico)" icon="eye">Ler</x-admin.button>
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="quote" title="Nenhum tópico."
                                 description="Quando os associados começarem a escrever, os tópicos aparecem aqui." />
        @endforelse
    </div>

    @if ($topicos->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $topicos->links() }}</div>
    @endif
</x-admin.card>
@endsection
