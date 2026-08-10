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
            <span>{{ $post->category }}</span>
            <span>{{ $post->published_at?->format('d/m/Y') }}</span>
            <span>{{ $post->reading_time }}</span>
        </div>
        <h3><a href="{{ route('posts.show', $post) }}">{{ $post->title }}</a></h3>
        <p>{{ $post->excerpt_text }}</p>
        <a class="post-link" href="{{ route('posts.show', $post) }}">Ler notícia</a>
    </div>
</article>
