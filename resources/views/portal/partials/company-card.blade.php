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
        <p>{{ $company->description }}</p>
        <div class="company-meta">
            <span>{{ $company->city }}</span>
            <span>{{ $company->members_count }} membros vinculados</span>
        </div>
    </div>
    <a class="secondary-button" href="{{ route('companies.show', $company->slug) }}">Ver empresa</a>
</article>
