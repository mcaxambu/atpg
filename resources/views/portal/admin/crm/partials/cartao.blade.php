{{-- Cartão do funil. Mostra só o que decide a próxima ação. --}}
<a href="{{ route('admin.crm.show', $item) }}"
   class="block rounded-xl border border-gray-200 bg-white p-3 transition hover:border-brand-300 hover:shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-brand-500/40">
    <div class="flex items-start justify-between gap-2">
        <strong class="min-w-0 flex-1 truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $item->name }}</strong>

        @if ($item->atrasado())
            <span class="shrink-0 rounded-full bg-error-50 px-2 py-0.5 text-[11px] font-medium text-error-700 dark:bg-error-500/10 dark:text-error-300">atrasado</span>
        @elseif ($item->parado())
            <span class="shrink-0 rounded-full bg-warning-50 px-2 py-0.5 text-[11px] font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-300">{{ $item->diasParado() }}d parado</span>
        @endif
    </div>

    @if ($item->contact_name)
        <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">{{ $item->contact_name }}</p>
    @endif

    @if ($item->next_action)
        <p class="mt-2 line-clamp-2 text-xs text-gray-600 dark:text-gray-300">{{ $item->next_action }}</p>
    @endif

    <div class="mt-2 flex items-center justify-between text-[11px] text-gray-400">
        <span>{{ $item->next_action_at?->format('d/m') ?? 'sem data' }}</span>
        <span class="truncate">{{ $item->owner?->name }}</span>
    </div>
</a>
