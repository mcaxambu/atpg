{{-- Uma linha da lista por etapa. --}}
<div class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.crm.show', $item) }}" class="truncate text-sm font-semibold text-gray-800 hover:text-brand-600 dark:text-white/90">{{ $item->name }}</a>
            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600 dark:bg-white/[0.06] dark:text-gray-300">{{ $item->kindLabel() }}</span>

            @if ($item->atrasado())
                <span class="rounded-full bg-error-50 px-2 py-0.5 text-[11px] font-medium text-error-700 dark:bg-error-500/10 dark:text-error-300">atrasado</span>
            @endif
        </div>

        <p class="mt-0.5 truncate text-sm text-gray-500 dark:text-gray-400">
            {{ $item->next_action ?: 'Sem próximo passo definido' }}
            @if ($item->next_action_at)
                — {{ $item->next_action_at->format('d/m/Y') }}
            @endif
        </p>
    </div>

    <div class="flex shrink-0 items-center gap-3">
        <span class="hidden text-xs text-gray-500 sm:inline dark:text-gray-400">{{ $item->owner?->name ?? 'sem responsável' }}</span>
        <x-admin.button :href="route('admin.crm.show', $item)" icon="eye">Abrir</x-admin.button>
    </div>
</div>
