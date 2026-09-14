@extends('layouts.portal')

@section('title', ($cmsPage?->title ?? 'Sobre') . ' | Portal Associação Tech PG')

@section('content')
<section class="page-section">
    <div class="page-title">
        <span class="eyebrow">Sobre a iniciativa</span>
        <h1>{{ $cmsPage?->title ?? 'Um ponto de encontro para tecnologia em Ponta Grossa' }}</h1>
        <p>{{ $cmsPage?->excerpt ?? 'O Portal Associação Tech PG nasce para organizar informações profissionais, dar visibilidade a empresas locais e facilitar novas conexões no ecossistema.' }}</p>
    </div>

    @if ($cmsPage?->hero_image)
        <figure class="page-feature-image"><img src="{{ asset('storage/' . $cmsPage->hero_image) }}" alt="{{ $cmsPage->title }}"></figure>
    @endif

    @if ($cmsPage?->body)
        <article class="content-card institutional-content rich-text">{!! $cmsPage->rendered_body !!}</article>
    @endif

    {{-- Missão e visão: dois blocos largos, gerenciados no CMS. --}}
    @if ($purposeItems->isNotEmpty())
        <div class="purpose-grid">
            @foreach ($purposeItems as $item)
                <article class="purpose-card">
                    <span class="eyebrow">{{ $item->subtitle ?: 'Propósito' }}</span>
                    <h2>{{ $item->title }}</h2>
                    <p>{{ $item->description }}</p>
                </article>
            @endforeach
        </div>
    @endif

    {{-- Valores: cards menores, um por valor. --}}
    @if ($valueItems->isNotEmpty())
        <div class="section-heading">
            <span class="eyebrow">O que nos guia</span>
            <h2>Nossos valores</h2>
        </div>

        <div class="values-grid">
            @foreach ($valueItems as $item)
                <article class="value-card">
                    <h3>{{ $item->title }}</h3>
                    <p>{{ $item->description }}</p>
                </article>
            @endforeach
        </div>
    @endif

    <div class="feature-grid">
        <article><h3>Conexão</h3><p>Aproxime profissionais, empresas, universidades, eventos e oportunidades de negocio.</p></article>
        <article><h3>Representatividade</h3><p>Mostre a diversidade de competências existentes em Ponta Grossa e região.</p></article>
        <article><h3>Evolução</h3><p>Base preparada para perfis editaveis, moderacao, área administrativa e futuras integrações.</p></article>
    </div>
</section>
@endsection
