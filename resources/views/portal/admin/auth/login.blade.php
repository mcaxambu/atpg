@extends('layouts.registration')

@section('title', 'Entrar no painel')

@section('content')
<section class="registration-card">
    <div class="registration-top">
        <aside class="registration-aside">
            <a class="brand" href="{{ route('home') }}">
                {{-- O lateral é sempre azul-escuro: versão branca, sem placa. --}}
                <x-brand-logo tone="white" class="brand-logo-branca" />
            </a>

            <div class="aside-copy">
                <h2>Acesse seu painel.</h2>
                <p>
                    <strong>Empresa associada:</strong> atualize os dados, troque a logo e
                    cadastre os colaboradores que aparecem no portal.
                </p>
                <p>
                    <strong>Equipe da associação:</strong> aprove cadastros, publique conteúdo
                    e administre o portal.
                </p>
            </div>

            <div class="aside-note">
                É o mesmo acesso para os dois: ao entrar, você vai direto para o painel da sua conta.
            </div>
        </aside>

        <div class="registration-main">
            <div class="form-heading">
                <span class="eyebrow">Área do associado</span>
                <h1>Entrar</h1>
                <p>Informe seu e-mail e senha para continuar.</p>
            </div>

            @if (session('status'))
                <div class="success-message">{{ session('status') }}</div>
            @endif

            @include('portal.admin.partials.errors')

            <form class="company-form" method="post" action="{{ route('entrar.store') }}">
                @csrf

                <label>E-mail
                    <input name="email" type="email" value="{{ old('email') }}" placeholder="seu@email.com.br" inputmode="email" autocomplete="email" required autofocus>
                </label>

                <label>Senha
                    <input name="password" type="password" placeholder="Sua senha" autocomplete="current-password" required>
                </label>

                <label class="remember-check">
                    <input type="checkbox" name="remember" value="1">
                    <span>Manter conectado neste dispositivo</span>
                </label>

                <p class="forgot-line">
                    <a href="{{ route('password.request') }}">Esqueci minha senha</a>
                </p>

                <div class="form-actions">
                    <button class="primary-button" type="submit">Entrar</button>
                    <a class="secondary-button" href="{{ route('home') }}">Voltar ao portal</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
