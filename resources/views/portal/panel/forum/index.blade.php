{{-- Mesma tela para empresa e membro; o layout vem do controller. --}}
@extends($layout)

@section('title', 'Fórum')

@section('content')
<div class="grid gap-6 xl:grid-cols-4">
    <div class="space-y-6 xl:col-span-3">
        <x-admin.card :padding="false">
            <x-slot:title>{{ $categoria?->name ?? 'Fórum dos associados' }}</x-slot:title>
            <x-slot:subtitle>
                {{ $categoria?->description ?? 'Espaço fechado: só quem é associado lê e escreve aqui.' }}
            </x-slot:subtitle>
            <x-slot:actions>
                <x-admin.button :href="route($rotaBase.'.create')" variant="primary" icon="plus">Abrir tópico</x-admin.button>
            </x-slot:actions>

            <div class="border-b border-gray-100 p-5 dark:border-gray-800">
                <form method="get" action="{{ route($rotaBase.'.index') }}" class="flex flex-col gap-3 sm:flex-row">
                    @if ($categoria)
                        <input type="hidden" name="categoria" value="{{ $categoria->slug }}">
                    @endif

                    <div class="relative flex-1">
                        <x-admin.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input type="search" name="q" value="{{ $busca }}" placeholder="Buscar no fórum..."
                               class="w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-9 pr-3 text-sm text-gray-800 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    </div>

                    <x-admin.button type="submit" variant="primary" icon="search">Buscar</x-admin.button>
                </form>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($topicos as $topico)
                    <div class="flex gap-3 p-5">
                        <x-admin.avatar :photo="$topico->author?->fotoNoForum()"
                                        :initials="$topico->author?->iniciaisNoForum() ?? '?'"
                                        :round="true" />

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($topico->is_pinned)
                                    <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-medium text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">fixado</span>
                                @endif

                                @if ($topico->is_closed)
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-500 dark:bg-white/[0.06] dark:text-gray-400">fechado</span>
                                @endif

                                <a href="{{ route($rotaBase.'.show', $topico->slug) }}"
                                   class="truncate text-sm font-semibold text-gray-800 hover:text-brand-600 dark:text-white/90">{{ $topico->title }}</a>
                            </div>

                            <p class="mt-0.5 line-clamp-2 text-sm text-gray-500 dark:text-gray-400">{{ $topico->excerpt }}</p>

                            <p class="mt-1 text-xs text-gray-400">
                                {{ $topico->author?->assinaturaNoForum() ?? 'Associado' }}
                                · {{ $topico->category?->name }}
                                · {{ $topico->ultimaAtividade()?->diffForHumans() }}
                            </p>
                        </div>

                        <div class="shrink-0 text-center">
                            <strong class="block text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $topico->replies_count }}</strong>
                            <span class="text-[11px] text-gray-400">{{ $topico->replies_count === 1 ? 'resposta' : 'respostas' }}</span>
                        </div>
                    </div>
                @empty
                    <x-admin.empty-state icon="quote" title="Nenhum tópico por aqui ainda."
                                         description="Abra o primeiro: uma dúvida, uma indicação, uma vaga de parceria.">
                        <x-slot:actions>
                            <x-admin.button :href="route($rotaBase.'.create')" variant="primary" icon="plus">Abrir tópico</x-admin.button>
                        </x-slot:actions>
                    </x-admin.empty-state>
                @endforelse
            </div>

            @if ($topicos->hasPages())
                <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $topicos->links() }}</div>
            @endif
        </x-admin.card>
    </div>

    <div class="space-y-6">
        <x-admin.card title="Assuntos" :padding="false">
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                <a href="{{ route($rotaBase.'.index') }}"
                   class="flex items-center justify-between px-5 py-3 text-sm {{ $categoria === null ? 'font-semibold text-brand-600 dark:text-brand-400' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/[0.03]' }}">
                    Todos
                </a>

                @foreach ($categorias as $item)
                    <a href="{{ route($rotaBase.'.index', ['categoria' => $item->slug]) }}"
                       class="flex items-center justify-between gap-2 px-5 py-3 text-sm {{ $categoria?->id === $item->id ? 'font-semibold text-brand-600 dark:text-brand-400' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/[0.03]' }}">
                        <span class="min-w-0 truncate">{{ $item->name }}</span>
                        <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-500 dark:bg-white/[0.06] dark:text-gray-400">{{ $item->topics_count }}</span>
                    </a>
                @endforeach
            </div>
        </x-admin.card>

        <x-admin.card title="Como funciona aqui">
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <li>Só associados leem e escrevem — nada disto aparece no site público.</li>
                <li>Você assina com seu nome e sua empresa.</li>
                <li>Quem abre um tópico ou responde nele recebe aviso por e-mail das respostas seguintes.</li>
            </ul>
        </x-admin.card>
    </div>
</div>
@endsection
