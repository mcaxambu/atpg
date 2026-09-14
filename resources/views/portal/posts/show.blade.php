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
            <span class="eyebrow">{{ $post->isColumn() ? 'Coluna' : $post->category }}</span>
            <h1>{{ $post->title }}</h1>

            @if ($post->isColumn() && $post->columnist)
                <p class="post-hero-byline">
                    por
                    <a href="{{ route('colunas.show', $post->columnist->slug) }}">{{ $post->columnist->byline }}</a>
                </p>
            @endif

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

        {{--
            Assinatura da coluna. O aviso nao e formalidade: coluna e opiniao
            de quem assina, e o leitor precisa distinguir isso de posicao
            oficial da associacao.
        --}}
        @if ($post->isColumn() && $post->columnist)
            <aside class="column-author">
                <div class="column-author-avatar">
                    @if ($post->columnist->photo_path)
                        <img src="{{ asset('storage/'.$post->columnist->photo_path) }}" alt="{{ $post->columnist->display_name }}">
                    @else
                        <span>{{ $post->columnist->initials }}</span>
                    @endif
                </div>

                <div>
                    <span class="eyebrow">Colunista</span>
                    <h3><a href="{{ route('colunas.show', $post->columnist->slug) }}">{{ $post->columnist->byline }}</a></h3>

                    @if ($post->columnist->presentation)
                        <p>{{ \Illuminate\Support\Str::limit(strip_tags($post->columnist->presentation), 220) }}</p>
                    @endif

                    <a class="post-link" href="{{ route('colunas.show', $post->columnist->slug) }}">Ver todas as colunas</a>

                    <p class="column-disclaimer">
                        As opiniões expressas nesta coluna são de responsabilidade de quem assina
                        e não representam necessariamente a posição da associação.
                    </p>
                </div>
            </aside>
        @endif

        {{-- Credito da fonte: quando a noticia nasce de materia de terceiro,
             o leitor precisa saber de onde veio e poder ir ate la. --}}
        @if ($post->source_url)
            <p class="post-source">
                Fonte:
                <a href="{{ $post->source_url }}" target="_blank" rel="noopener nofollow">
                    {{ $post->source_name ?: parse_url($post->source_url, PHP_URL_HOST) }}
                </a>
            </p>
        @endif
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
