<article class="post-card">
    <a class="post-cover" href="{{ route('posts.show', $post) }}">
        @if ($post->cover_image)
            <img src="{{ asset('storage/' . $post->cover_image) }}" alt="Capa {{ $post->title }}">
        @else
            <span>{{ $post->category }}</span>
        @endif
    </a>
    <div class="post-card-body">
        <div class="post-meta">
            {{-- Coluna e noticia com autor; o selo evita confundir opiniao
                 assinada com noticia institucional da associacao. --}}
            @if ($post->isColumn())
                <span class="post-column-tag">Coluna</span>
            @else
                <span>{{ $post->category }}</span>
            @endif
            <span>{{ $post->published_at?->format('d/m/Y') }}</span>
            <span>{{ $post->reading_time }}</span>
        </div>
        <h3><a href="{{ route('posts.show', $post) }}">{{ $post->title }}</a></h3>

        @if ($post->isColumn() && $post->columnist)
            <p class="post-byline">por <strong>{{ $post->columnist->byline }}</strong></p>
        @endif

        <p>{{ $post->excerpt_text }}</p>
        <a class="post-link" href="{{ route('posts.show', $post) }}">{{ $post->isColumn() ? 'Ler coluna' : 'Ler notícia' }}</a>
    </div>
</article>
