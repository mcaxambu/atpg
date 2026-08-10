@extends('layouts.portal')

@section('title', $company->name . ' | Portal Associação Tech PG')

@section('content')
<section class="profile-page">
    <a class="back-link" href="{{ route('companies.index') }}">Voltar para empresas</a>

    <article class="profile-cover">
        <div class="profile-hero-row">
            <div class="company-profile-logo">
                @if ($company->logo_path)
                    <img src="{{ asset('storage/' . $company->logo_path) }}" alt="Logo {{ $company->name }}">
                @else
                    {{ $company->initials }}
                @endif
            </div>
            <div>
                <h1>{{ $company->name }}</h1>
                <p>{{ $company->segment }}</p>
                <div class="profile-meta">
                    <span>{{ $company->city }}{{ $company->state ? ', ' . $company->state : '' }}</span>
                    <span>{{ $company->members->count() }} membros vinculados</span>
                    @if ($company->contact_name)
                        <span>Contato: {{ $company->contact_name }}</span>
                    @endif
                </div>
            </div>
            @if ($company->email)
                <a class="primary-button contact-button" href="mailto:{{ $company->email }}">Entrar em contato</a>
            @endif
        </div>
    </article>

    <div class="profile-grid">
        <div class="profile-main">
            <section class="content-card">
                <h2>Sobre a empresa</h2>
                <p>{{ $company->description }}</p>
            </section>

            <section class="content-card">
                <h2>Membros vinculados</h2>
                @if ($company->members->isNotEmpty())
                    <div class="table-like">
                        @foreach ($company->members as $member)
                            <div class="table-row">
                                <div class="avatar small" style="background: {{ $member->avatar_color }}">{{ $member->avatar_initials }}</div>
                                <strong>{{ $member->name }}</strong>
                                <span>{{ $member->role }}</span>
                                <span>{{ $member->experience_years }} anos</span>
                                <a href="{{ route('members.show', $member->slug) }}">Ver perfil</a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p>Nenhum membro publicado vinculado a está empresa ainda.</p>
                @endif
            </section>
        </div>

        <aside class="profile-sidebar">
            <section class="content-card">
                <h2>Dados comerciais</h2>
                <div class="meta-list">
                    @if ($company->legal_name)<span>Razão social: {{ $company->legal_name }}</span>@endif
                    @if ($company->cnpj)<span>CNPJ: {{ $company->cnpj }}</span>@endif
                    @if ($company->contact_role)<span>Responsável: {{ $company->contact_role }}</span>@endif
                    @if ($company->neighborhood || $company->address)
                        <span>{{ trim(($company->address ?? '') . ', ' . ($company->address_number ?? ''), ', ') }}</span>
                        <span>{{ $company->neighborhood }}{{ $company->zip_code ? ' - CEP ' . $company->zip_code : '' }}</span>
                    @endif
                </div>
            </section>

            <section class="content-card">
                <h2>Links</h2>
                <div class="links-list">
                    @if ($company->site_url)<a href="{{ $company->site_url }}" target="_blank" rel="noopener">Site</a>@endif
                    @if ($company->whatsapp)<a href="https://wa.me/{{ preg_replace('/\D+/', '', $company->whatsapp) }}" target="_blank" rel="noopener">WhatsApp</a>@endif
                    @if ($company->email)<a href="mailto:{{ $company->email }}">E-mail</a>@endif
                </div>
            </section>
        </aside>
    </div>
</section>
@endsection
