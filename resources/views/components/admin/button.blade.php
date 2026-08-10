@props(['variant' => 'secondary', 'href' => null, 'icon' => null, 'type' => 'button'])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 disabled:cursor-not-allowed disabled:opacity-60';

    $variants = [
        'primary' => 'bg-brand-500 text-white shadow-theme-xs hover:bg-brand-600',
        'secondary' => 'border border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.04]',
        'success' => 'border border-success-200 text-success-700 hover:bg-success-50 dark:border-success-500/30 dark:text-success-300 dark:hover:bg-success-500/10',
        'danger' => 'border border-error-200 text-error-700 hover:bg-error-50 dark:border-error-500/30 dark:text-error-300 dark:hover:bg-error-500/10',
        'ghost' => 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/[0.05]',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['secondary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-admin.icon :name="$icon" class="h-4 w-4" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-admin.icon :name="$icon" class="h-4 w-4" />@endif
        {{ $slot }}
    </button>
@endif
