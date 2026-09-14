@extends('layouts.portal')

@section('title', $job->title.' | Vagas | Portal Associação Tech PG')

@section('content')
@php
    $linhas = fn (?string $texto) => collect(preg_split('/\r\n|\r|\n/', (string) $texto))
        ->map(fn ($l) => trim($l))
        ->filter();
@endphp

<section class="page-section">
    <div class="page-title">
        <span class="eyebrow">{{ $job->company?->name }}</span>
        <h1>{{ $job->title }}</h1>
        {{-- unique(): em vaga remota o modelo e o local sao a mesma palavra. --}}
        <p>{{ collect([$job->type->label(), $job->workplace->label(), $job->locationLabel()])->filter()->unique()->join(' · ') }}</p>
    </div>

    @if (session('application_sent'))
        <div class="job-success">
            <h2>Candidatura enviada.</h2>
            <p>
                Seus dados e seu currículo foram encaminhados para {{ $job->company?->name }}.
                A empresa entra em contato diretamente com você — a associação não participa da seleção.
            </p>
            <a class="secondary-button" href="{{ route('vagas.index') }}">Ver outras vagas</a>
        </div>
    @endif

    <div class="job-detail">
        <div class="job-detail-main">
            <article class="job-block">
                <h2>Sobre a vaga</h2>
                <div class="job-text rich-text">{!! $job->rendered_description !!}</div>
            </article>

            @if (filled($job->requirements))
                <article class="job-block">
                    <h2>Requisitos</h2>
                    <ul class="job-bullets">
                        @foreach ($linhas($job->requirements) as $linha)
                            <li>{{ $linha }}</li>
                        @endforeach
                    </ul>
                </article>
            @endif

            @if (filled($job->benefits))
                <article class="job-block">
                    <h2>Benefícios</h2>
                    <ul class="job-bullets">
                        @foreach ($linhas($job->benefits) as $linha)
                            <li>{{ $linha }}</li>
                        @endforeach
                    </ul>
                </article>
            @endif

            @unless (session('application_sent'))
                <article class="job-block" id="candidatar">
                    <h2>Candidatar-se</h2>

                    @if ($errors->any())
                        <div class="form-errors">
                            <strong>Revise os campos abaixo:</strong>
                            <ul>
                                @foreach ($errors->all() as $erro)
                                    <li>{{ $erro }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form class="member-form" method="post" action="{{ route('vagas.candidatura', $job) }}"
                          enctype="multipart/form-data">
                        @csrf
                        @include('portal.partials.form-guard')

                        <div class="form-grid">
                            <label class="field-wide">
                                <span>Nome completo *</span>
                                <input type="text" name="name" value="{{ old('name') }}" required>
                            </label>

                            <label>
                                <span>E-mail *</span>
                                <input type="email" name="email" value="{{ old('email') }}" required>
                            </label>

                            <label>
                                <span>Telefone / WhatsApp</span>
                                <input type="text" name="phone" data-mask="phone" value="{{ old('phone') }}">
                            </label>

                            <label class="field-wide">
                                <span>LinkedIn</span>
                                <input type="url" name="linkedin_url" value="{{ old('linkedin_url') }}"
                                       placeholder="https://linkedin.com/in/seu-perfil">
                            </label>

                            <label class="field-wide">
                                <span>Currículo * <small>(PDF, DOC ou DOCX, até 5 MB)</small></span>
                                <input type="file" name="resume" accept=".pdf,.doc,.docx" required>
                            </label>

                            <label class="field-wide">
                                <span>Mensagem para a empresa</span>
                                <textarea name="message" rows="5" placeholder="Conte por que você se interessou por esta vaga.">{{ old('message') }}</textarea>
                            </label>

                            <label class="field-wide job-consent">
                                <input type="checkbox" name="consent" value="1" required @checked(old('consent'))>
                                <span>
                                    Autorizo o envio dos meus dados e do meu currículo para
                                    <strong>{{ $job->company?->name }}</strong>, para fins deste processo seletivo.
                                    Saiba mais na <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Política de Privacidade</a>.
                                </span>
                            </label>
                        </div>

                        <button class="primary-button" type="submit">Enviar candidatura</button>
                    </form>
                </article>
            @endunless
        </div>

        <aside class="job-detail-side">
            <div class="job-summary">
                <h2>Resumo</h2>
                <dl>
                    <dt>Empresa</dt>
                    <dd>
                        @if ($job->company?->isVisible())
                            <a href="{{ route('companies.show', $job->company) }}">{{ $job->company->name }}</a>
                        @else
                            {{ $job->company?->name }}
                        @endif
                    </dd>

                    <dt>Contrato</dt>
                    <dd>{{ $job->type->label() }}</dd>

                    <dt>Modelo</dt>
                    <dd>{{ $job->workplace->label() }}</dd>

                    {{-- Em vaga remota, "Local" repetiria o que "Modelo" ja diz. --}}
                    @if ($job->locationLabel() !== $job->workplace->label())
                        <dt>Local</dt>
                        <dd>{{ $job->locationLabel() }}</dd>
                    @endif

                    @if ($job->seniority)
                        <dt>Senioridade</dt>
                        <dd>{{ $job->seniority }}</dd>
                    @endif

                    @if ($job->salaryLabel())
                        <dt>Faixa salarial</dt>
                        <dd>{{ $job->salaryLabel() }}</dd>
                    @endif

                    <dt>Prazo</dt>
                    <dd>{{ $job->closes_at ? 'Até '.$job->closes_at->format('d/m/Y') : 'Sem data definida' }}</dd>
                </dl>

                @unless (session('application_sent'))
                    <a class="primary-button" href="#candidatar">Candidatar-se</a>
                @endunless
            </div>

            <p class="job-note">
                A associação apenas hospeda o anúncio. A seleção e o contato com os candidatos
                são feitos pela empresa.
            </p>
        </aside>
    </div>

    <p class="job-back"><a href="{{ route('vagas.index') }}">&larr; Ver todas as vagas</a></p>
</section>
@endsection
