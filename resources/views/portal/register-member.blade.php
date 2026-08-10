@extends('layouts.registration')

@section('title', 'Cadastro de membro | Portal Associação Tech PG')

@section('content')
<section class="registration-card">
    <div class="registration-header">
        <a class="registration-brand" href="{{ route('home') }}">
            <span class="admin-brand-mark">PG</span>
            <span><strong>Tech PG</strong><small>Cadastro de membro</small></span>
        </a>
        <div>
            <span class="admin-kicker">Portal Associação Tech PG</span>
            <h1>Solicite seu cadastro profissional</h1>
            <p>Preencha seus dados para entrar no diretório da associação. O perfil fica pendente até aprovação.</p>
        </div>
    </div>

    @if (session('registration_success'))
        <div class="registration-success">
            <strong>Cadastro realizado com sucesso.</strong>
            <p>Recebemos suas informações. A associação fará a conferência dos dados antes de publicar seu perfil no portal.</p>
            <a class="secondary-button" href="{{ route('home') }}">Voltar ao início</a>
        </div>
    @else
        @include('portal.admin.partials.errors')

        <form class="tail-form" method="post" action="{{ route('members.store') }}" enctype="multipart/form-data">
            @csrf
            @include('portal.partials.form-guard')
            <div class="tail-form-grid">
                <label>Nome completo<input name="name" value="{{ old('name') }}" placeholder="Nome completo" required></label>
                <label>Cargo ou função<input name="role" value="{{ old('role') }}" placeholder="Ex: Desenvolvedor, gestor, consultor"></label>
                <label>Empresa
                    <select name="company_id">
                        <option value="">Selecione uma empresa</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Cidade<input name="city" value="{{ old('city', 'Ponta Grossa, PR') }}" placeholder="Ponta Grossa, PR"></label>
                <label>Tempo de profissão<input name="experience_years" type="number" min="0" max="80" value="{{ old('experience_years') }}" placeholder="Ex: 10"></label>
                <label>E-mail<input name="email" type="email" value="{{ old('email') }}" placeholder="email@exemplo.com" required></label>
                <label>Site<input name="site_url" value="{{ old('site_url') }}" placeholder="https://"></label>
                <label>LinkedIn<input name="linkedin_url" value="{{ old('linkedin_url') }}" placeholder="https://linkedin.com/in/..."></label>
                <label>Instagram<input name="instagram_url" value="{{ old('instagram_url') }}" placeholder="@usuário"></label>
                <label>WhatsApp<input name="whatsapp" value="{{ old('whatsapp') }}" placeholder="(42) 99999-9999"></label>
                <label>Foto de perfil <small>opcional, até 2 MB</small><input name="photo" type="file" accept="image/*"></label>
            </div>

            <fieldset class="tail-checkbox-panel">
                <legend>Especialidades</legend>
                <div class="tail-checkbox-grid">
                    @foreach ($specialties as $specialty)
                        <label>
                            <input type="checkbox" name="specialties[]" value="{{ $specialty->id }}" @checked(in_array($specialty->id, old('specialties', [])))>
                            <span>{{ $specialty->name }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <label>Resumo profissional<textarea name="summary" rows="4" placeholder="Descreva sua atuação profissional">{{ old('summary') }}</textarea></label>
            <label>Experiências <small>uma por linha</small><textarea name="experiences" rows="4" placeholder="Liste experiências relevantes">{{ old('experiences') }}</textarea></label>
            <label>Projetos <small>um por linha</small><textarea name="projects" rows="4" placeholder="Liste projetos realizados">{{ old('projects') }}</textarea></label>
            <label>Certificações <small>uma por linha</small><textarea name="certifications" rows="3" placeholder="Liste certificações">{{ old('certifications') }}</textarea></label>
            <label class="consent-check">
                <input type="checkbox" name="privacy_consent" value="1" @checked(old('privacy_consent')) required>
                <span>
                    Autorizo o tratamento dos meus dados para análise e publicação do perfil no portal,
                    conforme a <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Política de Privacidade</a>
                    e a <a href="{{ route('lgpd') }}" target="_blank" rel="noopener">LGPD</a>.
                </span>
            </label>

            <div class="form-actions">
                <button class="primary-button" type="submit">Enviar cadastro</button>
                <a class="secondary-button" href="{{ route('home') }}">Cancelar</a>
            </div>
        </form>
    @endif
</section>
@endsection
