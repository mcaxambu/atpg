@extends('layouts.portal')

@section('title', $post->title . ' | Portal Associação Tech PG')

@section('content')
<article class="post-page">
    <a class="back-link" href="{{ route('posts.index') }}">Voltar para notícias</a>

    {{-- A capa da propria noticia e o fundo do cabecalho. Antes havia uma foto
         de banco de imagens fixa aqui e a capa aparecia de novo logo abaixo:
         duas imagens concorrendo pela mesma funcao. --}}
    <header class="post-hero @if ($post->cover_image) has-cover @endif"
            @if ($post->cover_image) style="--post-cover: url('{{ asset('storage/' . $post->cover_image) }}')" @endif>
        <div>
            <span class="eyebrow">{{ $post->category }}</span>
            <h1>{{ $post->title }}</h1>
            <div class="post-meta">
                <span>{{ $post->published_at?->format('d/m/Y H:i') }}</span>
                <span>{{ $post->reading_time }} de leitura</span>
            </div>
            @if ($post->excerpt)
                <p>{{ $post->excerpt }}</p>
            @endif
        </div>
    </header>

    <div class="post-content">
        {!! $post->rendered_body !!}
    </div>

    @if ($relatedPosts->isNotEmpty())
        <section class="section related-posts">
            <div class="section-heading row">
                <div>
                    <span class="eyebrow">Leia também</span>
                    <h2>Outras notícias da associação</h2>
                </div>
                <a class="secondary-button" href="{{ route('posts.index') }}">Ver todas</a>
            </div>
            <div class="post-grid">
                @foreach ($relatedPosts as $relatedPost)
                    @include('portal.partials.post-card', ['post' => $relatedPost])
                @endforeach
            </div>
        </section>
    @endif
</article>
@endsection
