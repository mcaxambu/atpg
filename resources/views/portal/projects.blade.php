@extends('layouts.portal')

@section('title', ($cmsPage?->title ?? 'Projetos') . ' | Portal Associação Tech PG')

@section('content')
<section class="page-hero projects-hero">
    <span class="eyebrow">Projetos e iniciativas</span>
    <h1>{{ $cmsPage?->title ?? 'Frentes coletivas para transformar relacionamento em impacto regional.' }}</h1>
    <p>{{ $cmsPage?->excerpt ?? 'Use está área para apresentar programas de capacitação, encontros, grupos de trabalho, parcerias institucionais e iniciativas de inovação aberta da associação.' }}</p>
</section>

@if ($cmsPage?->body)
<section class="section">
    <article class="content-card institutional-content rich-text">{!! $cmsPage->rendered_body !!}</article>
</section>
@endif

<section class="section">
    <div class="section-heading row">
        <div>
            <span class="eyebrow">Iniciativas em destaque</span>
            <h2>Projetos que podem mobilizar empresas, profissionais e parceiros.</h2>
        </div>
        <a class="secondary-button" href="{{ route('events') }}">Ver agenda</a>
    </div>
    <div class="project-grid">
        @forelse ($cmsProjects as $project)
            <article class="project-card">
                @if ($project->image_path)
                    <img src="{{ asset('storage/' . $project->image_path) }}" alt="{{ $project->title }}">
                @endif
                <div>
                    <span>{{ $project->subtitle ?: 'Iniciativa ATPG' }}</span>
                    <h3>{{ $project->title }}</h3>
                    <p>{{ $project->description }}</p>
                    @if ($project->link_url)
                        <a class="post-link" href="{{ $project->link_url }}">{{ $project->button_label ?: 'Saiba mais' }}</a>
                    @endif
                </div>
            </article>
        @empty
            <article class="content-card empty-state">
                <h2>Nenhum projeto publicado ainda</h2>
                <p>Cadastre projetos no CMS para alimentar está página automaticamente.</p>
            </article>
        @endforelse
    </div>
</section>

@if ($cmsPartners->isNotEmpty())
<section class="section muted-section">
    <div class="section-heading">
        <span class="eyebrow">Parceiros</span>
        <h2>Instituições que ajudam a fortalecer o ecossistema.</h2>
    </div>
    <div class="partner-grid">
        @foreach ($cmsPartners as $partner)
            @include('portal.partials.partner-card', ['partner' => $partner])
        @endforeach
    </div>
</section>
@endif
@endsection
