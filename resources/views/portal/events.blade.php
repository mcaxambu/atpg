@extends('layouts.portal')

@section('title', 'Eventos | Portal Associação Tech PG')

@section('content')
<section class="page-section">
    <div class="page-title">
        <span class="eyebrow">Agenda do ecossistema</span>
        <h1>Eventos mantem a associação viva entre uma reuniao e outra.</h1>
        <p>Encontros, workshops, demo days e fóruns publicados pelo CMS da associação.</p>
    </div>

    <div class="event-timeline">
        @forelse ($events as $event)
            <article>
                <div class="event-date">{{ $event->event_date?->format('d/m/Y') ?: 'Data a definir' }}</div>
                <div class="event-body">
                    <span class="segment">{{ $event->type }}</span>
                    <h3>{{ $event->title }}</h3>
                    <p>{{ $event->description }}</p>
                    <a href="{{ route('events.show', $event) }}">Ver detalhes</a>
                </div>
            </article>
        @empty
            <article>
                <div class="event-date">Em breve</div>
                <div class="event-body">
                    <span class="segment">Agenda</span>
                    <h3>Nenhum evento publicado ainda</h3>
                    <p>Os eventos cadastrados e publicados pelo admin aparecerão nesta agenda.</p>
                    <a href="{{ route('companies.register.create') }}">Quero participar da associação</a>
                </div>
            </article>
        @endforelse
    </div>
</section>
@endsection
