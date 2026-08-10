@extends('layouts.portal')

@section('title', $event->title . ' | Portal Associação Tech PG')

@section('content')
<article class="post-page">
    <a class="back-link" href="{{ route('events') }}">Voltar para eventos</a>

    <header class="post-hero">
        <div>
            <span class="eyebrow">{{ $event->type }}</span>
            <h1>{{ $event->title }}</h1>
            <div class="post-meta">
                <span>{{ $event->event_date?->format('d/m/Y') ?: 'Data a definir' }}</span>
                @if ($event->starts_at)
                    <span>{{ $event->starts_at->format('H:i') }}@if($event->ends_at) - {{ $event->ends_at->format('H:i') }}@endif</span>
                @endif
                @if ($event->location)
                    <span>{{ $event->location }}</span>
                @endif
            </div>
            <p>{{ $event->description }}</p>
            @if ($event->registration_url)
                <div class="hero-actions">
                    <a class="primary-button" href="{{ $event->registration_url }}" target="_blank" rel="noopener">Inscrever-se</a>
                </div>
            @endif
        </div>
    </header>

    @if ($event->cover_image)
        <figure class="post-featured-image">
            <img src="{{ asset('storage/' . $event->cover_image) }}" alt="Capa {{ $event->title }}">
        </figure>
    @endif
</article>
@endsection
