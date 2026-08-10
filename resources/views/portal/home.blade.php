@extends('layouts.portal')

@section('title', 'Portal Associação Tech PG')

@section('content')
<section class="hero">
    <div class="hero-content">
        <span class="eyebrow">Associação de tecnologia de Ponta Grossa</span>
        @php($heroBanner = $cmsBanners->first())
        <h1>{{ $heroBanner?->title ?: 'Conectando empresas, profissionais e inovação regional' }}</h1>
        <p>{{ $heroBanner?->description ?: 'Uma plataforma institucional para dar visibilidade ao ecossistema tech, organizar associados, fortalecer a representação setorial e aproximar oportunidades.' }}</p>
        <div class="hero-actions">
            <a class="primary-button" href="{{ route('members.index') }}">Conhecer membros</a>
            <a class="secondary-button light" href="{{ route('join') }}">Associar empresa</a>
            <a class="ghost-button" href="{{ route('events') }}">Agenda e eventos</a>
        </div>
    </div>
    <div class="hero-panel">
        <div class="live-badge">Ecossistema regional</div>
        <div class="network-card">
            <strong>Rede profissional qualificada</strong>
            <span>Perfis, empresas, especialidades, eventos e conteúdos institucionais em um único ambiente.</span>
            <div class="hero-insights">
                <div><strong>{{ $companyCount }}</strong><span>empresas</span></div>
                <div><strong>{{ $memberCount }}</strong><span>membros</span></div>
                <div><strong>{{ $specialtyCount }}</strong><span>áreas</span></div>
            </div>
        </div>
        <div class="mini-profile-row">
            @foreach ($featuredMembers->take(4) as $member)
                <div class="avatar small" style="background: {{ $member->avatar_color }}">{{ $member->avatar_initials }}</div>
            @endforeach
            <span>profissionais e empresas conectados</span>
        </div>
    </div>
</section>

@if ($cmsHighlights->isNotEmpty())
<section class="section highlight-section">
    <div class="section-heading row">
        <div>
            <span class="eyebrow">Destaques do portal</span>
            <h2>Prioridades e oportunidades em evidência.</h2>
            <p>Conteúdos selecionados no CMS para orientar empresas, associados e parceiros.</p>
        </div>
        <a class="secondary-button" href="{{ route('join') }}">Participar</a>
    </div>
    <div class="highlight-grid">
        @foreach ($cmsHighlights as $highlight)
            <article>
                @if ($highlight->image_path)
                    <img src="{{ asset('storage/' . $highlight->image_path) }}" alt="{{ $highlight->title }}">
                @endif
                <div>
                    <span>{{ $highlight->subtitle ?: 'Destaque ATPG' }}</span>
                    <h3>{{ $highlight->title }}</h3>
                    <p>{{ $highlight->description }}</p>
                    @if ($highlight->link_url)
                        <a href="{{ $highlight->link_url }}">{{ $highlight->button_label ?: 'Saiba mais' }}</a>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
</section>
@endif

<section class="stats-section">
    <article class="stat-card"><strong>{{ $memberCount }}</strong><span>membros</span></article>
    <article class="stat-card"><strong>{{ $companyCount }}</strong><span>empresas</span></article>
    <article class="stat-card"><strong>{{ $specialtyCount }}</strong><span>especialidades</span></article>
    <article class="stat-card"><strong>{{ $siteSettings['ecosystem_years'] ?? 10 }}</strong><span>anos de ecossistema</span></article>
</section>

@if ($latestEvents->isNotEmpty())
<section class="section home-events-section">
    <div class="home-events-shell">
        <div class="section-heading">
            <span class="eyebrow">Agenda viva</span>
            <h2>Próximos encontros da comunidade.</h2>
            <p>Eventos ajudam a transformar cadastro em relacionamento real entre associados, empresas, universidades e parceiros institucionais.</p>
            <a class="secondary-button" href="{{ route('events') }}">Ver agenda completa</a>
        </div>
        <div class="home-events-list">
            @foreach ($latestEvents as $event)
                <a class="home-event-card" href="{{ route('events.show', $event) }}">
                    <span>{{ $event->event_date?->format('d/m') ?: 'Em breve' }}</span>
                    <div>
                        <strong>{{ $event->title }}</strong>
                        <small>{{ $event->type }} @if($event->location) / {{ $event->location }} @endif</small>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="section">
    <div class="section-heading">
        <span class="eyebrow">Proposta da associação</span>
        <h2>Uma vitrine qualificada para quem constrói tecnologia na cidade.</h2>
        <p>O portal organiza perfis profissionais, empresas associadas e áreas de atuação para facilitar parcerias, contratações, eventos e projetos coletivos.</p>
    </div>
    <div class="feature-grid">
        <article><h3>Mapeamento de talentos</h3><p>Perfis padronizados mostram experiência, especialidades, projetos e canais de contato.</p></article>
        <article><h3>Visibilidade para empresas</h3><p>Empresas associadas ganham uma página de apresentação conectada aos seus membros.</p></article>
        <article><h3>Base para evolução</h3><p>A estrutura Laravel já antecipa CRUD, autenticação, permissões e área administrativa.</p></article>
    </div>
</section>

<section class="section companies-showcase-section">
    <div class="section-heading row">
        <div>
            <span class="eyebrow">Empresas associadas</span>
            <h2>Organizações que movem tecnologia na região.</h2>
            <p>Uma vitrine para empresas participantes, seus segmentos e conexões com profissionais do ecossistema.</p>
        </div>
        <a class="secondary-button" href="{{ route('companies.index') }}">Ver empresas</a>
    </div>
    <div class="company-showcase-grid">
        @foreach ($featuredCompanies as $company)
            @include('portal.partials.company-card', ['company' => $company])
        @endforeach
    </div>
</section>

<section class="section association-section">
    <div class="section-heading row">
        <div>
            <span class="eyebrow">Como associação</span>
            <h2>O portal também organiza a vida institucional.</h2>
            <p>Associações de tecnologia fortes costumam combinar representação setorial, agenda recorrente, benefícios claros, governança participativa e visibilidade para seus membros.</p>
        </div>
        <a class="secondary-button" href="{{ route('governance') }}">Ver governança</a>
    </div>
    <div class="association-grid">
        <a href="{{ route('benefits') }}"><strong>Benefícios</strong><span>Vitrine, networking, capacitação e oportunidades para associados.</span></a>
        <a href="{{ route('events') }}"><strong>Eventos</strong><span>Agenda pública para encontros, workshops, demo days e fóruns.</span></a>
        <a href="{{ route('governance') }}"><strong>Governança</strong><span>Diretoria, conselhos, comitês, transparência e ritos de decisão.</span></a>
        <a href="{{ route('join') }}"><strong>Associe-se</strong><span>Fluxo público com triagem antes da publicação no portal.</span></a>
    </div>
</section>

@if ($cmsProjects->isNotEmpty())
<section class="section projects-preview-section">
    <div class="section-heading row">
        <div>
            <span class="eyebrow">Projetos</span>
            <h2>Iniciativas que movem a associação para além do cadastro.</h2>
        </div>
        <a class="secondary-button" href="{{ route('projects') }}">Ver projetos</a>
    </div>
    <div class="project-grid compact">
        @foreach ($cmsProjects->take(3) as $project)
            <article class="project-card">
                <div>
                    <span>{{ $project->subtitle ?: 'Iniciativa ATPG' }}</span>
                    <h3>{{ $project->title }}</h3>
                    <p>{{ $project->description }}</p>
                </div>
            </article>
        @endforeach
    </div>
</section>
@endif

@if ($cmsPartners->isNotEmpty())
<section class="section muted-section">
    <div class="section-heading">
        <span class="eyebrow">Parceiros</span>
        <h2>Conexões institucionais que ampliam a força do ecossistema.</h2>
    </div>
    <div class="partner-grid">
        @foreach ($cmsPartners as $partner)
            @include('portal.partials.partner-card', ['partner' => $partner])
        @endforeach
    </div>
</section>
@endif

@if ($cmsTestimonials->isNotEmpty())
<section class="section">
    <div class="section-heading">
        <span class="eyebrow">Vozes do ecossistema</span>
        <h2>Quem participa ajuda a dar forma a uma agenda comum.</h2>
        <p>Depoimentos cadastrados no CMS reforçam pertencimento, confiança e colaboração entre empresas, profissionais e instituições.</p>
    </div>
    <div class="testimonial-grid">
        @foreach ($cmsTestimonials as $testimonial)
            <article>
                <p>"{{ $testimonial->description }}"</p>
                <strong>{{ $testimonial->title }}</strong>
                @if ($testimonial->subtitle)
                    <span>{{ $testimonial->subtitle }}</span>
                @endif
            </article>
        @endforeach
    </div>
</section>
@endif

<section class="section">
    <div class="section-heading row">
        <div>
            <span class="eyebrow">Notícias</span>
            <h2>Comunicados e movimentos do ecossistema.</h2>
            <p>Use o CMS para publicar novidades, agenda, parcerias, oportunidades e conteúdos importantes para os associados.</p>
        </div>
        <a class="secondary-button" href="{{ route('posts.index') }}">Ver notícias</a>
    </div>
    <div class="home-news-grid">
        @forelse ($latestPosts as $post)
            <div class="{{ $loop->first ? 'home-news-featured' : 'home-news-secondary' }}">
                @include('portal.partials.post-card', ['post' => $post])
            </div>
        @empty
            <article class="content-card empty-state">
                <h2>Nenhuma notícia publicada ainda</h2>
                <p>Assim que o admin publicar conteúdos no CMS, eles aparecerão nesta área da home.</p>
            </article>
        @endforelse
    </div>
</section>

<section class="section muted-section">
    <div class="section-heading row">
        <div>
            <span class="eyebrow">Membros em destaque</span>
            <h2>Profissionais prontos para conexão.</h2>
        </div>
        <a class="secondary-button" href="{{ route('members.index') }}">Ver todos</a>
    </div>
    <div class="cards-grid">
        @foreach ($featuredMembers->take(3) as $member)
            @include('portal.partials.member-card', ['member' => $member])
        @endforeach
    </div>
</section>
@endsection
