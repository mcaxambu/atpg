{{-- Mesma tela para empresa e membro; o layout vem do controller. --}}
@extends($layout)

@section('title', 'Minhas colunas')

@section('content')
<x-admin.card :padding="false">
    <x-slot:title>{{ $columns->total() }} {{ $columns->total() === 1 ? 'coluna' : 'colunas' }}</x-slot:title>
    <x-slot:subtitle>Assinadas por <strong>{{ $columnist?->byline }}</strong>. Cada texto passa pela análise da associação antes de ir ao ar.</x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route($rotaBase.'.create')" variant="primary" icon="plus">Escrever coluna</x-admin.button>
    </x-slot:actions>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($columns as $column)
            <div class="flex flex-col gap-3 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0 space-y-1">
                    <strong class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $column->title }}</strong>

                    <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                        {{ $column->excerpt_text }}
                    </p>

                    <p class="text-xs text-gray-400">
                        @if ($column->published_at)
                            Publicada em {{ $column->published_at->format('d/m/Y') }}
                        @else
                            Enviada em {{ $column->created_at->format('d/m/Y') }}
                        @endif
                    </p>

                    @if ($column->isRejected() && $column->rejection_reason)
                        <p class="mt-1 rounded-lg bg-error-50 px-3 py-2 text-xs text-error-700 dark:bg-error-500/10 dark:text-error-300">
                            <strong>Não aprovada:</strong> {{ $column->rejection_reason }}
                        </p>
                    @endif
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-white/[0.06] dark:text-gray-300">
                        @if ($column->isPending())
                            Em análise
                        @elseif ($column->isRejected())
                            Rejeitada
                        @elseif ($column->is_published)
                            No ar
                        @else
                            Aprovada, fora do ar
                        @endif
                    </span>

                    @if ($column->isApproved() && $column->is_published)
                        <x-admin.button :href="route('posts.show', $column)" target="_blank" icon="globe">Ver</x-admin.button>
                    @endif

                    <x-admin.button :href="route($rotaBase.'.edit', $column->id)" icon="edit">Editar</x-admin.button>

                    <x-admin.confirm-form :action="route($rotaBase.'.destroy', $column->id)"
                                          message="Remover &quot;{{ $column->title }}&quot;?">Remover</x-admin.confirm-form>
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="news" title="Você ainda não escreveu nenhuma coluna."
                                 description="Escreva o primeiro texto; a associação analisa e publica no portal.">
                <x-slot:actions>
                    <x-admin.button :href="route($rotaBase.'.create')" variant="primary" icon="plus">Escrever coluna</x-admin.button>
                </x-slot:actions>
            </x-admin.empty-state>
        @endforelse
    </div>

    @if ($columns->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $columns->links() }}</div>
    @endif
</x-admin.card>
@endsection
