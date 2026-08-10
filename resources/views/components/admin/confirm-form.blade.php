@props([
    'action',
    'method' => 'delete',
    'message' => 'Tem certeza?',
    'variant' => 'danger',
    'icon' => 'trash',
])

<form method="post" action="{{ $action }}" class="inline-flex"
      onsubmit="return confirm(@js($message));">
    @csrf
    @method($method)
    <x-admin.button type="submit" :variant="$variant" :icon="$icon" {{ $attributes }}>{{ $slot }}</x-admin.button>
</form>
