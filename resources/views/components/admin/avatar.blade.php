@props(['photo' => null, 'initials' => '?', 'color' => '#0f4c81', 'size' => 'h-11 w-11', 'contain' => false])

<div class="grid shrink-0 place-items-center overflow-hidden rounded-xl text-sm font-semibold text-white {{ $size }}"
     style="background: {{ $color }}">
    @if ($photo)
        {{-- asset() respeita o forceRootUrl do prefixo /atpg; Storage::url() não. --}}
        <img src="{{ asset('storage/'.$photo) }}" alt="" loading="lazy"
             class="h-full w-full {{ $contain ? 'bg-white object-contain p-1.5' : 'object-cover' }}">
    @else
        {{ $initials }}
    @endif
</div>
