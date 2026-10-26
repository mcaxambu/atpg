@extends($layout)

@section('title', $topic->title)

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
@endphp

<div class="space-y-6">
    <x-admin.card>
        <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
            <a href="{{ route($rotaBase.'.index') }}" class="hover:text-brand-600">Fórum</a>
            <span>·</span>
            <a href="{{ route($rotaBase.'.index', ['categoria' => $topic->category?->slug]) }}" class="hover:text-brand-600">{{ $topic->category?->name }}</a>

            @if ($topic->is_closed)
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-500 dark:bg-white/[0.06] dark:text-gray-400">fechado para respostas</span>
            @endif
        </div>

        <h1 class="mt-2 text-xl font-semibold text-gray-800 dark:text-white/90">{{ $topic->title }}</h1>

        <div class="mt-4 flex gap-3">
            <x-admin.avatar :photo="$topic->author?->fotoNoForum()"
                            :initials="$topic->author?->iniciaisNoForum() ?? '?'" :round="true" />

            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $topic->author?->assinaturaNoForum() ?? 'Associado' }}</p>
                <p class="text-xs text-gray-400">{{ $topic->created_at->format('d/m/Y H:i') }}</p>

                <div class="prose-admin mt-3 text-sm text-gray-700 dark:text-gray-300">{!! $topic->rendered_body !!}</div>

                @if ($topic->podeSerEditadoPor(auth()->user()))
                    <div class="mt-3">
                        <x-admin.button :href="route($rotaBase.'.edit', $topic->slug)" icon="edit">Editar meu tópico</x-admin.button>
                    </div>
                @endif
            </div>
        </div>
    </x-admin.card>

    <x-admin.card :padding="false">
        <x-slot:title>{{ $respostas->count() }} {{ $respostas->count() === 1 ? 'resposta' : 'respostas' }}</x-slot:title>

        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($respostas as $resposta)
                <div class="flex gap-3 p-5">
                    <x-admin.avatar :photo="$resposta->author?->fotoNoForum()"
                                    :initials="$resposta->author?->iniciaisNoForum() ?? '?'" :round="true" />

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $resposta->author?->assinaturaNoForum() ?? 'Associado' }}</p>
                        <p class="text-xs text-gray-400">{{ $resposta->created_at->format('d/m/Y H:i') }}</p>

                        <div class="prose-admin mt-2 text-sm text-gray-700 dark:text-gray-300">{!! $resposta->rendered_body !!}</div>
                    </div>
                </div>
            @empty
                <x-admin.empty-state icon="quote" title="Ninguém respondeu ainda."
                                     description="Seja o primeiro a responder." />
            @endforelse
        </div>
    </x-admin.card>

    @if ($topic->is_closed)
        <x-admin.card>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                A diretoria fechou este tópico para novas respostas. O conteúdo continua aqui para leitura.
            </p>
        </x-admin.card>
    @else
        <x-admin.card title="Responder">
            <form method="post" action="{{ route($rotaBase.'.reply', $topic->slug) }}" class="space-y-4">
                @csrf

                <x-admin.field name="body" required>
                    <textarea name="body" data-editor rows="6" required class="{{ $input }}">{{ old('body') }}</textarea>
                </x-admin.field>

                <x-admin.button type="submit" variant="primary" icon="check">Publicar resposta</x-admin.button>
            </form>
        </x-admin.card>
    @endif
</div>
@endsection

@push('scripts')
    @vite('resources/js/editor.js')
@endpush
