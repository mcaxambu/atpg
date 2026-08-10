{{-- Destaque da listagem de noticias: capa a esquerda, texto a direita.
     Reaproveita as classes .post-* para herdar hover, cores e tipografia. --}}
<article class="post-card post-featured">
    <a class="post-cover" href="{{ route('posts.show', $post) }}">
        @if ($post->cover_image)
            <img src="{{ asset('storage/' . $post->cover_image) }}" alt="Capa {{ $post->title }}">
        @else
            <span>{{ $post->category }}</span>
        @endif
    </a>
    <div class="post-card-body">
        <span class="post-badge">Destaque</span>
        <div class="post-meta">
            <span>{{ $post->category }}</span>
            <span>{{ $post->published_at?->format('d/m/Y') }}</span>
            <span>{{ $post->reading_time }}</span>
        </div>
        <h2><a href="{{ route('posts.show', $post) }}">{{ $post->title }}</a></h2>
        <p>{{ $post->excerpt_text }}</p>
        <a class="post-link" href="{{ route('posts.show', $post) }}">Ler notícia</a>
    </div>
</article>
