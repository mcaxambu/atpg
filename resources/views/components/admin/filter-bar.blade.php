@props(['action', 'placeholder' => 'Buscar...', 'value' => null])

<form method="get" action="{{ $action }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
    <div class="relative flex-1">
        <x-admin.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
        <input type="search" name="q" value="{{ $value ?? request('q') }}" placeholder="{{ $placeholder }}"
               class="w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-9 pr-3 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
    </div>

    {{ $slot }}

    <div class="flex gap-2">
        <x-admin.button type="submit" variant="primary" icon="search">Filtrar</x-admin.button>
        @if (request()->hasAny(['q', 'status', 'segment', 'company', 'specialty']))
            <x-admin.button :href="$action" variant="ghost">Limpar</x-admin.button>
        @endif
    </div>
</form>
