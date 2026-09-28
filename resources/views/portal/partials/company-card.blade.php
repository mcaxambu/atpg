<article class="company-card">
    <div class="company-logo">
        @if ($company->logo_path)
            <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Logo {{ $company->name }}">
        @else
            {{ $company->initials }}
        @endif
    </div>
    <div>
        <h3>{{ $company->name }}</h3>
        <span class="segment">{{ $company->segment }}</span>
        {{--
            `description_excerpt` e nao `description`: a descricao e guardada em
            HTML desde a virada do editor, e impressa crua aqui o cartao mostrava
            as tags como texto ("<p>Consultoria em..."). O resumo ja vem sem
            marcacao e cortado no tamanho do cartao.
        --}}
        <p>{{ $company->description_excerpt }}</p>
        <div class="company-meta">
            <span>{{ $company->city }}</span>
            <span>{{ $company->members_count }} membros vinculados</span>
        </div>
    </div>
    <a class="secondary-button" href="{{ route('companies.show', $company->slug) }}">Ver empresa</a>
</article>
