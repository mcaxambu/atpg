@extends('layouts.registration')

@section('title', 'Convocação — '.$reuniao->title)

@section('content')
@php
    $minhaResposta = null;

    if (auth()->check() && auth()->user()->company_id) {
        $minhaResposta = $reuniao->attendances->firstWhere('company_id', auth()->user()->company_id);
    }
@endphp

<section class="registration-card">
    <div class="registration-top">
        <aside class="registration-aside">
            <a class="brand" href="{{ route('home') }}">
                <x-brand-logo tone="white" class="brand-logo-branca" />
            </a>

            <div class="aside-copy">
                <span class="convocacao-tipo">{{ $reuniao->type->label() }}</span>
                <h2>{{ $reuniao->title }}</h2>

                <p class="convocacao-quando">
                    <strong>{{ $reuniao->scheduled_at->translatedFormat('l, d \d\e F \d\e Y') }}</strong><br>
                    {{ $reuniao->periodLabel() }}
                </p>

                @if ($reuniao->location)
                    <p class="convocacao-local">{{ $reuniao->location }}</p>
                @endif

                @if ($reuniao->online_url)
                    <p class="convocacao-local">
                        <a href="{{ $reuniao->online_url }}" target="_blank" rel="noopener">Entrar na videochamada</a>
                    </p>
                @endif
            </div>

            <div class="aside-note">
                <a href="{{ route('reuniao.calendario', $reuniao->public_token) }}">Adicionar à minha agenda</a>
            </div>
        </aside>

        <div class="registration-main">
            @if ($reuniao->status === \App\Enums\MeetingStatus::Cancelada)
                <div class="error-message">
                    <strong>Reunião cancelada.</strong>
                    <p>Esta reunião foi cancelada pela associação.</p>
                </div>
            @endif

            @if ($reuniao->description)
                <div class="form-heading">
                    <span class="eyebrow">Convocação</span>
                    <p>{{ $reuniao->description }}</p>
                </div>
            @endif

            @if ($reuniao->agendaItems->isNotEmpty())
                <div class="convocacao-pauta">
                    <h3>Pauta da reunião</h3>
                    <ol>
                        @foreach ($reuniao->agendaItems as $item)
                            <li>
                                <strong>{{ $item->title }}</strong>
                                @if ($item->presenter)
                                    <small>{{ $item->presenter }}</small>
                                @endif
                                @if ($item->description)
                                    <p>{{ $item->description }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif

            @if (session('confirmacao_registrada'))
                <div class="registration-success">
                    <strong>Resposta registrada. Obrigado!</strong>
                    <p>A associação já recebeu sua confirmação. Se precisar mudar, é só responder de novo.</p>
                </div>
            @endif

            @include('portal.admin.partials.errors')

            @if ($reuniao->acceptsConfirmations())
                <form class="company-form convocacao-form" method="post"
                      action="{{ route('reuniao.confirmar', $reuniao->public_token) }}">
                    @csrf

                    <h3>Confirme sua presença</h3>

                    @if ($minhaResposta && $minhaResposta->hasReplied())
                        <p class="convocacao-resposta-atual">
                            Sua resposta atual: <strong>{{ $minhaResposta->status->label() }}</strong>
                        </p>
                    @endif

                    <label>Empresa
                        <select name="company_id" required @disabled($minhaResposta !== null)>
                            <option value="">Selecione a empresa</option>
                            @foreach ($empresas as $empresa)
                                <option value="{{ $empresa->id }}"
                                    @selected(old('company_id', $minhaResposta?->company_id) == $empresa->id)>
                                    {{ $empresa->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    @if ($minhaResposta)
                        <input type="hidden" name="company_id" value="{{ $minhaResposta->company_id }}">
                    @endif

                    <label>Seu nome
                        <input name="responded_by" value="{{ old('responded_by', $minhaResposta?->responded_by ?? auth()->user()?->name) }}"
                               placeholder="Quem está respondendo" required>
                    </label>

                    <fieldset class="convocacao-opcoes">
                        <legend>Vai participar?</legend>
                        @foreach (\App\Enums\AttendanceStatus::replyOptions() as $valor => $rotulo)
                            <label>
                                <input type="radio" name="status" value="{{ $valor }}" required
                                       @checked(old('status', $minhaResposta?->status?->value) === $valor)>
                                <span>{{ $rotulo }}</span>
                            </label>
                        @endforeach
                    </fieldset>

                    <div class="form-actions">
                        <button class="primary-button" type="submit">Enviar resposta</button>
                    </div>

                    @if ($confirmadas > 0)
                        <p class="convocacao-contador">
                            {{ $confirmadas }} {{ $confirmadas === 1 ? 'empresa já confirmou' : 'empresas já confirmaram' }} presença.
                        </p>
                    @endif
                </form>
            @elseif ($reuniao->status !== \App\Enums\MeetingStatus::Cancelada)
                <div class="registration-success">
                    <strong>Confirmações encerradas.</strong>
                    <p>O prazo para confirmar presença nesta reunião já passou.</p>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
