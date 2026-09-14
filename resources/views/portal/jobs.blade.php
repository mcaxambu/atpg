@extends('layouts.portal')

@section('title', 'Vagas | Portal Associação Tech PG')

@section('content')
<section class="page-section">
    <div class="page-title">
        <span class="eyebrow">Oportunidades no ecossistema</span>
        <h1>Vagas abertas nas empresas associadas</h1>
        <p>Publicadas pelas próprias empresas e conferidas pela associação antes de entrar no ar.</p>
    </div>

    <div class="filters-bar">
        <form class="filters-form job-filters" method="get" action="{{ route('vagas.index') }}">
            <input name="q" value="{{ request()->query('q') }}" placeholder="Buscar por cargo, empresa ou cidade">

            <select name="tipo">
                <option value="">Todos os contratos</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(request()->query('tipo') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>

            <select name="modelo">
                <option value="">Todos os modelos</option>
                @foreach ($workplaces as $place)
                    <option value="{{ $place->value }}" @selected(request()->query('modelo') === $place->value)>{{ $place->label() }}</option>
                @endforeach
            </select>

            <button class="primary-button" type="submit">Filtrar</button>
            <a class="secondary-button" href="{{ route('vagas.index') }}">Limpar</a>
        </form>
    </div>

    <p class="result-line">
        {{ $jobs->total() }} {{ $jobs->total() === 1 ? 'vaga encontrada' : 'vagas encontradas' }}
    </p>

    <div class="job-list">
        @forelse ($jobs as $job)
            <article class="job-card">
                <div class="job-card-company">
                    <div class="company-logo">
                        @if ($job->company?->logo_path)
                            <img src="{{ asset('storage/'.$job->company->logo_path) }}" alt="Logo {{ $job->company->name }}">
                        @else
                            {{ $job->company?->display_initials ?: '?' }}
                        @endif
                    </div>
                </div>

                <div class="job-card-body">
                    <h2><a href="{{ route('vagas.show', $job) }}">{{ $job->title }}</a></h2>
                    <p class="job-company-name">{{ $job->company?->name }}</p>

                    {{-- unique(): em vaga remota o modelo e o local sao a mesma palavra. --}}
                    <div class="job-tags">
                        @foreach (collect([$job->type->label(), $job->workplace->label(), $job->locationLabel(), $job->seniority])->filter()->unique() as $etiqueta)
                            <span class="segment">{{ $etiqueta }}</span>
                        @endforeach
                    </div>

                    <p class="job-excerpt">{{ $job->description_excerpt }}</p>
                </div>

                <div class="job-card-meta">
                    @if ($job->salaryLabel())
                        <strong class="job-salary">{{ $job->salaryLabel() }}</strong>
                    @endif

                    @if ($job->closes_at)
                        <span class="job-deadline">Até {{ $job->closes_at->format('d/m/Y') }}</span>
                    @endif

                    <a class="primary-button" href="{{ route('vagas.show', $job) }}">Ver vaga</a>
                </div>
            </article>
        @empty
            {{--
                Bloco proprio, fora do grid de 3 colunas do .job-card: sem logo
                nem coluna de acao, o texto era espremido na coluna de 72px.
            --}}
            <article class="job-empty">
                <h2>Nenhuma vaga aberta no momento</h2>
                <p>
                    As empresas associadas publicam vagas por aqui. Se a sua empresa é associada,
                    entre no painel para cadastrar a primeira.
                </p>
                <a class="secondary-button" href="{{ route('companies.register.create') }}">Quero associar minha empresa</a>
            </article>
        @endforelse
    </div>

    @if ($jobs->hasPages())
        <div class="pagination-wrap">{{ $jobs->links() }}</div>
    @endif
</section>
@endsection
