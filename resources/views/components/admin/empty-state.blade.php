@props(['icon' => 'inbox', 'title', 'description' => null])

<div {{ $attributes->merge(['class' => 'px-5 py-14 text-center']) }}>
    <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-white/[0.05] dark:text-gray-500">
        <x-admin.icon :name="$icon" class="h-6 w-6" />
    </span>
    <strong class="mt-4 block text-sm font-semibold text-gray-800 dark:text-white/90">{{ $title }}</strong>
    @if ($description)
        <span class="mx-auto mt-1 block max-w-md text-sm text-gray-500 dark:text-gray-400">{{ $description }}</span>
    @endif
    @isset($actions)
        <div class="mt-5 flex flex-wrap justify-center gap-2">{{ $actions }}</div>
    @endisset
</div>
