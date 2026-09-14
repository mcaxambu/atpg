@extends('layouts.portal')

@section('title', $columnist->display_name . ' | Colunas | Portal Associação Tech PG')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($columnist->presentation ?: 'Colunas de '.$columnist->display_name.' no Portal Associação Tech PG.'), 160))

@section('content')
<section class="page-section">
    <p class="job-back"><a href="{{ route('colunas.index') }}">&larr; Todos os colunistas</a></p>

    <header class="columnist-hero">
        <div class="columnist-hero-avatar">
            @if ($columnist->photo_path)
                <img src="{{ asset('storage/'.$columnist->photo_path) }}" alt="{{ $columnist->display_name }}">
            @else
                <span>{{ $columnist->initials }}</span>
            @endif
        </div>

        <div class="columnist-hero-body">
            <span class="eyebrow">Colunista</span>
            <h1>{{ $columnist->display_name }}</h1>

            @if ($columnist->isCompany())
                <p class="columnist-role">
                    Empresa associada
                    @if ($columnist->company?->isVisible())
                        &middot; <a href="{{ route('companies.show', $columnist->company) }}">ver perfil</a>
                    @endif
                </p>
            @elseif ($columnist->member)
                <p class="columnist-role">
                    {{ $columnist->member->role ?: 'Membro da associação' }}
                    @if ($columnist->member->isPubliclyVisible())
                        &middot; <a href="{{ route('members.show', $columnist->member) }}">ver perfil</a>
                    @endif
                </p>
            @endif

            @if ($columnist->presentation)
                <div class="rich-text columnist-bio">{!! $columnist->rendered_presentation !!}</div>
            @endif
        </div>
    </header>

    <div class="section-heading">
        <span class="eyebrow">{{ $columns->total() }} {{ $columns->total() === 1 ? 'texto publicado' : 'textos publicados' }}</span>
        <h2>Colunas de {{ $columnist->display_name }}</h2>
    </div>

    <div class="post-grid">
        @forelse ($columns as $column)
            @include('portal.partials.post-card', ['post' => $column])
        @empty
            <article class="job-empty">
                <h2>Nenhuma coluna publicada ainda</h2>
                <p>Os textos aparecem aqui assim que forem publicados pela associação.</p>
            </article>
        @endforelse
    </div>

    @if ($columns->hasPages())
        <div class="pagination-wrap">{{ $columns->links() }}</div>
    @endif
</section>
@endsection
