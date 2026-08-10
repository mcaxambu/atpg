@extends('layouts.portal')

@section('title', ($cmsPage?->title ?? 'Benefícios') . ' | Portal Associação Tech PG')

@section('content')
<section class="page-section">
    <div class="page-title">
        <span class="eyebrow">Benefícios para associados</span>
        <h1>{{ $cmsPage?->title ?? 'Uma associação precisa gerar valor visível para empresas, profissionais e comunidade.' }}</h1>
        <p>{{ $cmsPage?->excerpt ?? 'O portal organiza uma proposta inicial de benefícios para fortalecer o ecossistema tech de Ponta Grossa com conexão, representatividade, capacitação e oportunidades reais.' }}</p>
    </div>
    @if ($cmsPage?->hero_image)
        <figure class="page-feature-image"><img src="{{ asset('storage/' . $cmsPage->hero_image) }}" alt="{{ $cmsPage->title }}"></figure>
    @endif
    @if ($cmsPage?->body)
        <article class="content-card institutional-content">{!! nl2br(e($cmsPage->body)) !!}</article>
    @endif

    <div class="benefit-grid">
        <article>
            <span>01</span>
            <h3>Vitrine institucional</h3>
            <p>Empresas e membros ganham perfis públicos com especialidades, contatos, projetos e áreas de atuação.</p>
        </article>
        <article>
            <span>02</span>
            <h3>Networking qualificado</h3>
            <p>Agenda de encontros, rodadas de apresentação e conexões entre prestadores, contratantes, universidades e parceiros.</p>
        </article>
        <article>
            <span>03</span>
            <h3>Capacitação continua</h3>
            <p>Trilhas de workshops sobre gestão, segurança, IA, LGPD, vendas, produtos digitais e tecnologia aplicada.</p>
        </article>
        <article>
            <span>04</span>
            <h3>Representação setorial</h3>
            <p>Organização de pautas comuns para diálogo com poder público, entidades empresariais e instituições de ensino.</p>
        </article>
        <article>
            <span>05</span>
            <h3>Banco de oportunidades</h3>
            <p>Base futura para divulgar vagas, demandas de projetos, parcerias, editais, licitações e chamadas de inovação.</p>
        </article>
        <article>
            <span>06</span>
            <h3>Indicadores do ecossistema</h3>
            <p>Mapeamento de empresas, especialidades, tempo de experiência e competências disponiveis na região.</p>
        </article>
    </div>

    <section class="association-strip">
        <div>
            <span class="eyebrow">Proximo passo</span>
            <h2>Transformar cadastro em relacionamento.</h2>
            <p>Depois de aprovar empresas, o portal pode evoluir para planos de associado, agenda de eventos, comunicados, grupos de trabalho e prestação de contas.</p>
        </div>
        <a class="primary-button" href="{{ route('companies.register.create') }}">Cadastrar empresa</a>
    </section>
</section>
@endsection
