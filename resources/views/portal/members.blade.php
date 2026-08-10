@extends('layouts.portal')

@section('title', 'Membros | Portal Associação Tech PG')

@section('content')
<section class="page-section">
    <div class="page-title">
        <span class="eyebrow">Diretório profissional</span>
        <h1>Membros da associação</h1>
        <p>Encontre profissionais por nome, especialidade, empresa ou tempo de experiência.</p>
    </div>

    <div class="filters-bar">
        <form class="filters-form" method="get">
            <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Buscar por nome">
            <select name="specialty">
                <option value="">Todas as especialidades</option>
                @foreach ($specialties as $specialty)
                    <option value="{{ $specialty->slug }}" @selected(($filters['specialty'] ?? '') === $specialty->slug)>{{ $specialty->name }}</option>
                @endforeach
            </select>
            <select name="company">
                <option value="">Todas as empresas</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->slug }}" @selected(($filters['company'] ?? '') === $company->slug)>{{ $company->name }}</option>
                @endforeach
            </select>
            <select name="experience">
                <option value="">Qualquer experiência</option>
                @foreach ([5, 8, 10, 12] as $years)
                    <option value="{{ $years }}" @selected(($filters['experience'] ?? '') == $years)>{{ $years }}+ anos</option>
                @endforeach
            </select>
            <button class="primary-button" type="submit">Filtrar</button>
        </form>
    </div>

    <div class="result-line">{{ $members->total() }} {{ $members->total() === 1 ? 'membro encontrado' : 'membros encontrados' }}</div>
    <div class="cards-grid">
        @forelse ($members as $member)
            @include('portal.partials.member-card', ['member' => $member])
        @empty
            <p class="empty-result">Nenhum membro encontrado com esses filtros.</p>
        @endforelse
    </div>

    @if ($members->hasPages())
        <div class="pagination-shell">{{ $members->links('vendor.pagination.portal') }}</div>
    @endif

    <div id="especialidades" class="specialties-panel">
        <h2>Especialidades mapeadas</h2>
        <div class="chip-list large">
            @foreach ($specialties as $specialty)
                <span>{{ $specialty->name }}</span>
            @endforeach
        </div>
    </div>
</section>
@endsection
