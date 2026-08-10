@extends('layouts.portal')

@section('title', 'Notícias | Portal Associação Tech PG')

@section('content')
<section class="page-section">
    <div class="page-title">
        <span class="eyebrow">Notícias e comunicados</span>
        <h1>Atualizações do ecossistema tech de Ponta Grossa</h1>
        <p>Comunicados da associação, eventos, parcerias, oportunidades, cases e conteúdos relevantes para empresas e profissionais de tecnologia.</p>
    </div>

    @if ($featured)
        @include('portal.partials.post-card-featured', ['post' => $featured])
    @endif

    @if ($otherPosts->isNotEmpty())
        <div class="post-grid">
            @foreach ($otherPosts as $post)
                @include('portal.partials.post-card', ['post' => $post])
            @endforeach
        </div>
    @endif

    @if ($posts->isEmpty())
        <article class="content-card empty-state">
            <h2>Nenhuma notícia publicada ainda</h2>
            <p>As publicações criadas e publicadas pelo admin aparecerão aqui.</p>
        </article>
    @endif

    @if ($posts->hasPages())
        <div class="pagination-shell">
            {{ $posts->links('vendor.pagination.portal') }}
        </div>
    @endif
</section>
@endsection
