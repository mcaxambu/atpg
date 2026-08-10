@extends('layouts.portal')

@section('title', $title . ' | Portal Associação Tech PG')

@section('content')
<article class="post-page legal-page">
    <header class="post-hero legal-hero">
        <div>
            <span class="eyebrow">{{ $eyebrow }}</span>
            <h1>{{ $title }}</h1>
            <p>{{ $excerpt }}</p>
        </div>
    </header>

    <div class="legal-content">
        @foreach ($sections as $section)
            <section class="content-card legal-card">
                <h2>{{ $section['title'] }}</h2>
                <p>{{ $section['body'] }}</p>
            </section>
        @endforeach

        <section class="content-card legal-card">
            <h2>Observação importante</h2>
            <p>Este texto é uma versão institucional inicial para apresentação do portal. A redação final deve ser revisada pela assessoria jurídica da associação antes da publicação definitiva.</p>
        </section>
    </div>
</article>
@endsection
