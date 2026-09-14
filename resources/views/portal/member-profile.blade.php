@extends('layouts.portal')

@section('title', $member->name . ' | Portal Associação Tech PG')

@section('content')
<section class="profile-page">
    <a class="back-link" href="{{ route('members.index') }}">Voltar para membros</a>

    <article class="profile-cover">
        <div class="profile-hero-row">
            <div class="avatar profile-avatar" style="background: {{ $member->avatar_color }}">{{ $member->avatar_initials }}</div>
            <div>
                <h1>{{ $member->name }}</h1>
                <p>{{ $member->role }}</p>
                <div class="profile-meta">
                    <span>{{ $member->company?->name ?? 'Empresa independente' }}</span>
                    <span>{{ $member->city }}</span>
                    <span>{{ $member->experience_years }} anos de profissao</span>
                </div>
            </div>
            <a class="primary-button contact-button" href="mailto:{{ $member->email }}">Entrar em contato</a>
        </div>
    </article>

    <div class="profile-grid">
        <div class="profile-main">
            <section class="content-card"><h2>Resumo profissional</h2><div class="rich-text">{!! $member->rendered_summary !!}</div></section>
            <section class="content-card"><h2>Experiencias</h2><ul>@foreach ($member->experiences as $experience)<li>{{ $experience->title }}</li>@endforeach</ul></section>
            <section class="content-card"><h2>Projetos realizados</h2><ul>@foreach ($member->projects as $project)<li>{{ $project->title }}</li>@endforeach</ul></section>
            <section class="content-card"><h2>Certificações</h2><ul>@foreach ($member->certifications as $certification)<li>{{ $certification->title }}</li>@endforeach</ul></section>
        </div>
        <aside class="profile-sidebar">
            <section class="content-card">
                <h2>Especialidades</h2>
                <div class="chip-list">
                    @foreach ($member->specialties as $specialty)
                        <span>{{ $specialty->name }}</span>
                    @endforeach
                </div>
            </section>
            <section class="content-card">
                <h2>Links</h2>
                <div class="links-list">
                    <a href="{{ $member->site_url ?: '#' }}">Site</a>
                    <a href="{{ $member->linkedin_url ?: '#' }}">LinkedIn</a>
                    <a href="{{ $member->instagram_url ?: '#' }}">Instagram</a>
                    <a href="{{ $member->whatsapp ? 'https://wa.me/' . preg_replace('/\D+/', '', $member->whatsapp) : '#' }}">WhatsApp</a>
                    <a href="mailto:{{ $member->email }}">E-mail</a>
                </div>
            </section>
        </aside>
    </div>
</section>
@endsection
