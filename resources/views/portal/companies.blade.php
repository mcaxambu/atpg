@extends('layouts.portal')

@section('title', 'Empresas | Portal Associação Tech PG')

@section('content')
<section class="page-section">
    <div class="page-title">
        <span class="eyebrow">Empresas associadas</span>
        <h1>Organizações que fortalecem o ecossistema</h1>
        <p>Uma lista inicial de empresas participantes, seus segmentos e vínculos com membros da associação.</p>
    </div>

    <div class="stats-section compact company-stats">
        <article class="stat-card"><strong>{{ $totalCompanies }}</strong><span>empresas publicadas</span></article>
        <article class="stat-card"><strong>{{ $segments->count() }}</strong><span>segmentos ativos</span></article>
        <article class="stat-card"><strong>{{ $totalMembers }}</strong><span>membros vinculados</span></article>
        <article class="stat-card"><strong>PG</strong><span>ecossistema local</span></article>
    </div>

    <div class="filters-bar">
        <form class="filters-form company-filters" method="get" action="{{ route('companies.index') }}">
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar por empresa, segmento ou cidade">
            <select name="segment">
                <option value="">Todos os segmentos</option>
                @foreach ($segments as $segment)
                    <option value="{{ $segment }}" @selected(($filters['segment'] ?? '') === $segment)>{{ $segment }}</option>
                @endforeach
            </select>
            <select name="city">
                <option value="">Todas as cidades</option>
                @foreach ($cities as $city)
                    <option value="{{ $city }}" @selected(($filters['city'] ?? '') === $city)>{{ $city }}</option>
                @endforeach
            </select>
            <button class="primary-button" type="submit">Filtrar</button>
            <a class="secondary-button" href="{{ route('companies.index') }}">Limpar</a>
        </form>
    </div>

    <div class="section-heading row company-callout">
        <div>
            <span class="eyebrow">Cadastro aberto</span>
            <h2>Sua empresa também pode entrar nesta vitrine.</h2>
            <p>O cadastro e público, sem senha, e passa por aprovação antes de aparecer no portal.</p>
        </div>
        <a class="primary-button" href="{{ route('companies.register.create') }}">Cadastrar empresa</a>
    </div>

    <div class="company-grid">
        @forelse ($companies as $company)
            @include('portal.partials.company-card', ['company' => $company])
        @empty
            <article class="content-card empty-state">
                <h2>Nenhuma empresa encontrada</h2>
                <p>Ajuste os filtros ou limpe a busca para ver todas as empresas publicadas.</p>
                <a class="secondary-button" href="{{ route('companies.index') }}">Ver todas</a>
            </article>
        @endforelse
    </div>

    @if ($companies->hasPages())
        <div class="pagination-shell">{{ $companies->links('vendor.pagination.portal') }}</div>
    @endif
</section>
@endsection
