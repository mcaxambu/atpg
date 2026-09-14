@extends('layouts.portal')

@section('title', 'Colunas | Portal Associação Tech PG')
@section('meta_description', 'Opinião e análise de quem faz o ecossistema de tecnologia de Ponta Grossa: colunas assinadas por profissionais e empresas associadas.')

@section('content')
<section class="page-section">
    <div class="page-title">
        <span class="eyebrow">Opinião do ecossistema</span>
        <h1>Colunas</h1>
        <p>Textos assinados por profissionais e empresas associadas, sobre tecnologia, negócios e o desenvolvimento de Ponta Grossa.</p>
    </div>

    @if ($latestColumns->isNotEmpty())
        <div class="section-heading">
            <span class="eyebrow">Publicado recentemente</span>
            <h2>Últimas colunas</h2>
        </div>

        <div class="post-grid">
            @foreach ($latestColumns as $column)
                @include('portal.partials.post-card', ['post' => $column])
            @endforeach
        </div>
    @endif

    <div class="section-heading">
        <span class="eyebrow">Quem escreve</span>
        <h2>Nossos colunistas</h2>
    </div>

    <div class="columnist-grid">
        @forelse ($columnists as $columnist)
            <article class="columnist-card">
                <a class="columnist-avatar" href="{{ route('colunas.show', $columnist->slug) }}">
                    @if ($columnist->photo_path)
                        <img src="{{ asset('storage/'.$columnist->photo_path) }}" alt="{{ $columnist->display_name }}">
                    @else
                        <span>{{ $columnist->initials }}</span>
                    @endif
                </a>

                <div class="columnist-body">
                    <h3><a href="{{ route('colunas.show', $columnist->slug) }}">{{ $columnist->display_name }}</a></h3>

                    @if ($columnist->isCompany())
                        <span class="segment">Empresa associada</span>
                    @elseif ($columnist->member?->role)
                        <span class="segment">{{ $columnist->member->role }}</span>
                    @endif

                    @if ($columnist->headline || $columnist->presentation)
                        <p>{{ \Illuminate\Support\Str::limit(strip_tags($columnist->headline ?: $columnist->presentation), 150) }}</p>
                    @endif

                    <span class="columnist-count">
                        {{ $columnist->posts_count }} {{ $columnist->posts_count === 1 ? 'coluna publicada' : 'colunas publicadas' }}
                    </span>
                </div>
            </article>
        @empty
            <article class="job-empty">
                <h2>Nenhum colunista publicando ainda</h2>
                <p>
                    Membros e empresas associadas podem assinar coluna no portal.
                    Fale com a diretoria se quiser escrever.
                </p>
                <a class="secondary-button" href="{{ route('join') }}">Quero me associar</a>
            </article>
        @endforelse
    </div>
</section>
@endsection
