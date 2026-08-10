@extends('layouts.portal')

@section('title', ($cmsPage?->title ?? 'Associe-se') . ' | Portal Associação Tech PG')

@section('content')
<section class="page-hero join-hero">
    <span class="eyebrow">Faca parte da associação</span>
    <h1>{{ $cmsPage?->title ?? 'Entre para uma rede que fortalece tecnologia, negócios e inovação em Ponta Grossa.' }}</h1>
    <p>{{ $cmsPage?->excerpt ?? 'Associe sua empresa para ganhar visibilidade, participar da agenda do ecossistema, acessar conexões qualificadas e contribuir com uma pauta coletiva para o setor.' }}</p>
    <div class="hero-actions">
        <a class="primary-button" href="{{ route('companies.register.create') }}">Cadastrar empresa</a>
        <a class="secondary-button light" href="{{ route('benefits') }}">Ver benefícios</a>
    </div>
</section>

@if ($cmsPage?->body)
<section class="section">
    <article class="content-card institutional-content">{!! nl2br(e($cmsPage->body)) !!}</article>
</section>
@endif

<section class="section">
    <div class="section-heading">
        <span class="eyebrow">Jornada de associação</span>
        <h2>Um fluxo simples, com curadoria antes da publicação.</h2>
        <p>O cadastro público não cria senha. Ele envia os dados para análise administrativa e, após aprovação, a empresa passa a aparecer no portal.</p>
    </div>
    <div class="journey-grid">
        <article><span>1</span><h3>Envio do cadastro</h3><p>A empresa informa dados institucionais, contatos, segmento, endereço e logo.</p></article>
        <article><span>2</span><h3>Análise administrativa</h3><p>A associação confere as informações e pode ajustar o perfil antes da publicação.</p></article>
        <article><span>3</span><h3>Publicação no portal</h3><p>A empresa ganha vitrine pública e pode vincular membros, especialidades e projetos.</p></article>
    </div>
</section>

<section class="section muted-section">
    <div class="section-heading row">
        <div>
            <span class="eyebrow">Valor para associados</span>
            <h2>O que a empresa passa a ter no portal.</h2>
        </div>
        <a class="secondary-button" href="{{ route('companies.register.create') }}">Iniciar cadastro</a>
    </div>
    <div class="benefit-grid">
        <article><span>Visibilidade</span><h3>Perfil público qualificado</h3><p>Página com logo, segmento, descrição, contato, cidade e membros vinculados.</p></article>
        <article><span>Conexão</span><h3>Rede local organizada</h3><p>Fácilite parcerias, indicações, contratações e oportunidades entre empresas e profissionais.</p></article>
        <article><span>Institucional</span><h3>Representatividade setorial</h3><p>Fortaleca pautas comuns com universidades, entidades, mercado e poder público.</p></article>
    </div>
</section>

@if ($cmsTestimonials->isNotEmpty())
<section class="section">
    <div class="section-heading">
        <span class="eyebrow">Vozes do ecossistema</span>
        <h2>Depoimentos de quem ajuda a construir a comunidade.</h2>
    </div>
    <div class="testimonial-grid">
        @foreach ($cmsTestimonials as $testimonial)
            <article>
                <p>{{ $testimonial->description }}</p>
                <strong>{{ $testimonial->title }}</strong>
                @if ($testimonial->subtitle)<span>{{ $testimonial->subtitle }}</span>@endif
            </article>
        @endforeach
    </div>
</section>
@endif
@endsection
