@extends('layouts.admin')

@section('title', $topic->title)
@section('page_heading', 'Tópico do fórum')

@section('content')
<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <x-admin.card>
            <h1 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $topic->title }}</h1>

            <div class="mt-3 flex gap-3">
                <x-admin.avatar :photo="$topic->author?->fotoNoForum()"
                                :initials="$topic->author?->iniciaisNoForum() ?? '?'" :round="true" />

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $topic->author?->assinaturaNoForum() ?? 'Associado' }}</p>
                    <p class="text-xs text-gray-400">{{ $topic->created_at->format('d/m/Y H:i') }} · {{ $topic->category?->name }}</p>

                    <div class="prose-admin mt-3 text-sm text-gray-700 dark:text-gray-300">{!! $topic->rendered_body !!}</div>
                </div>
            </div>
        </x-admin.card>

        <x-admin.card :padding="false">
            <x-slot:title>{{ $topic->replies->count() }} {{ $topic->replies->count() === 1 ? 'resposta' : 'respostas' }}</x-slot:title>

            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($topic->replies as $resposta)
                    <div class="flex gap-3 p-5 {{ $resposta->is_hidden ? 'bg-error-50/40 dark:bg-error-500/5' : '' }}">
                        <x-admin.avatar :photo="$resposta->author?->fotoNoForum()"
                                        :initials="$resposta->author?->iniciaisNoForum() ?? '?'" :round="true" />

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $resposta->author?->assinaturaNoForum() ?? 'Associado' }}</p>
                                @if ($resposta->is_hidden)
                                    <span class="rounded-full bg-error-50 px-2 py-0.5 text-[11px] text-error-700 dark:bg-error-500/10 dark:text-error-300">oculta</span>
                                @endif
                            </div>

                            <p class="text-xs text-gray-400">{{ $resposta->created_at->format('d/m/Y H:i') }}</p>

                            <div class="prose-admin mt-2 text-sm text-gray-700 dark:text-gray-300">{!! $resposta->rendered_body !!}</div>
                        </div>

                        <form method="post" action="{{ route('admin.forum.replies.toggle', $resposta) }}" class="shrink-0">
                            @csrf
                            @method('patch')
                            <x-admin.button type="submit">{{ $resposta->is_hidden ? 'Mostrar' : 'Ocultar' }}</x-admin.button>
                        </form>
                    </div>
                @empty
                    <x-admin.empty-state icon="quote" title="Sem respostas." description="Ninguém respondeu a este tópico." />
                @endforelse
            </div>
        </x-admin.card>
    </div>

    <div class="space-y-6">
        <x-admin.card title="Moderação"
                      subtitle="Ocultar tira da vista sem destruir o que foi escrito. Tudo aqui é reversível.">
            <div class="space-y-2">
                @foreach ([
                    'fixar' => $topic->is_pinned ? 'Desafixar do topo' : 'Fixar no topo',
                    'fechar' => $topic->is_closed ? 'Reabrir para respostas' : 'Fechar para respostas',
                    'ocultar' => $topic->is_hidden ? 'Mostrar aos associados' : 'Ocultar dos associados',
                ] as $acao => $rotulo)
                    <form method="post" action="{{ route('admin.forum.topics.toggle', $topic) }}">
                        @csrf
                        @method('patch')
                        <input type="hidden" name="acao" value="{{ $acao }}">
                        <x-admin.button type="submit" class="w-full">{{ $rotulo }}</x-admin.button>
                    </form>
                @endforeach
            </div>

            <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-800">
                <x-admin.confirm-form :action="route('admin.forum.topics.destroy', $topic)"
                                      message="Excluir &quot;{{ $topic->title }}&quot; e todas as respostas? Prefira ocultar.">
                    Excluir tópico
                </x-admin.confirm-form>
            </div>
        </x-admin.card>

        <x-admin.button :href="route('admin.forum.topics.index')" variant="ghost">Voltar aos tópicos</x-admin.button>
    </div>
</div>
@endsection
