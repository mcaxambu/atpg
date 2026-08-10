@props(['label' => null, 'name' => null, 'hint' => null, 'required' => false])

@php
    $error = $name ? ($errors->first($name) ?: $errors->first(str_replace(['[', ']'], ['.', ''], $name))) : null;
@endphp

<label {{ $attributes->merge(['class' => 'block']) }}>
    @if ($label)
        <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if ($required)<span class="text-error-500">*</span>@endif
        </span>
    @endif

    {{ $slot }}

    @if ($hint && ! $error)
        <span class="mt-1.5 block text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</span>
    @endif

    @if ($error)
        <span class="mt-1.5 flex items-center gap-1 text-xs font-medium text-error-600 dark:text-error-400">
            <x-admin.icon name="alert" class="h-3.5 w-3.5" />{{ $error }}
        </span>
    @endif
</label>
