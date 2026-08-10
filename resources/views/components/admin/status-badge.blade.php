@props(['status', 'published' => null])

@php
    $status = $status instanceof \App\Enums\ModerationStatus
        ? $status
        : (\App\Enums\ModerationStatus::tryFrom((string) $status) ?? \App\Enums\ModerationStatus::Pending);

    $label = $status->label();

    // Aprovado mas despublicado merece um rotulo proprio: e um estado real
    // que o operador precisa distinguir de "aguardando analise".
    if ($status === \App\Enums\ModerationStatus::Approved && $published === false) {
        $label = 'Despublicado';
        $classes = 'bg-gray-100 text-gray-600 dark:bg-white/[0.06] dark:text-gray-300';
    } else {
        $classes = $status->badgeClasses();
    }
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {$classes}"]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $label }}
</span>
