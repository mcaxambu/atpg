@extends('layouts.portal')

@section('title', ($cmsPage?->title ?? 'Governança') . ' | Portal Associação Tech PG')

@section('content')
<section class="page-section">
    <div class="page-title">
        <span class="eyebrow">Governança e transparência</span>
        <h1>{{ $cmsPage?->title ?? 'Uma associação forte precisa de papéis claros, ritos simples e participação dos associados.' }}</h1>
        <p>{{ $cmsPage?->excerpt ?? 'Esta proposta inicial organiza a estrutura institucional que pode orientar diretoria, conselhos, comitês e grupos de trabalho do ecossistema tech local.' }}</p>
    </div>
    @if ($cmsPage?->hero_image)
        <figure class="page-feature-image"><img src="{{ asset('storage/' . $cmsPage->hero_image) }}" alt="{{ $cmsPage->title }}"></figure>
    @endif
    @if ($cmsPage?->body)
        <article class="content-card institutional-content">{!! nl2br(e($cmsPage->body)) !!}</article>
    @endif

    <div class="governance-grid">
        <article>
            <h3>Diretoria executiva</h3>
            <p>Responsável por estratégia, representação institucional, parcerias, agenda e acompanhamento das entregas.</p>
            <ul>
                <li>Presidencia e vice-presidencia</li>
                <li>Administrativo e financeiro</li>
                <li>Relacionamento com associados</li>
            </ul>
        </article>
        <article>
            <h3>Conselho consultivo</h3>
            <p>Grupo de apoio com liderancas empresariais, educacionais e técnicas para orientar prioridades da associação.</p>
            <ul>
                <li>Empresas associadas</li>
                <li>Instituições de ensino</li>
                <li>Parceiros do ecossistema</li>
            </ul>
        </article>
        <article>
            <h3>Comitês temáticos</h3>
            <p>Grupos de trabalho com objetivos práticos, prazo definido e entregas visíveis para associados.</p>
            <ul>
                <li>Eventos e comunidade</li>
                <li>Educação e talentos</li>
                <li>Inovação e negócios</li>
                <li>Políticas públicas e dados</li>
            </ul>
        </article>
        <article>
            <h3>Transparência</h3>
            <p>Publicação de pautas, atas resumidas, indicadores, prestação de contas e critérios de aprovação de associados.</p>
            <ul>
                <li>Calendario de reuniões</li>
                <li>Indicadores do portal</li>
                <li>Regras de uso de dados</li>
            </ul>
        </article>
    </div>

    <section class="association-strip">
        <div>
            <span class="eyebrow">LGPD e dados</span>
            <h2>Cadastro público deve ter finalidade clara.</h2>
            <p>Os dados enviados por empresas devem ser usados para análise da associação e publicação do perfil após aprovação, com contato administrativo quando necessario.</p>
        </div>
        <a class="secondary-button" href="{{ route('companies.register.create') }}">Ver cadastro público</a>
    </section>
</section>
@endsection
