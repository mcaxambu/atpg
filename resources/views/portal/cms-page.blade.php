@extends('layouts.portal')

@section('title', $page->title . ' | Portal Associação Tech PG')

@section('content')
<article class="post-page">
    <header class="post-hero">
        <div>
            <span class="eyebrow">Página institucional</span>
            <h1>{{ $page->title }}</h1>
            @if ($page->excerpt)
                <p>{{ $page->excerpt }}</p>
            @endif
        </div>
    </header>

    @if ($page->hero_image)
        <figure class="post-featured-image">
            <img src="{{ asset('storage/' . $page->hero_image) }}" alt="Imagem {{ $page->title }}">
        </figure>
    @endif

    <div class="post-content">
        {!! nl2br(e($page->body)) !!}
    </div>
</article>
@endsection
