@extends('layouts.registration')

@section('title', 'Esqueci minha senha')

@section('content')
<section class="registration-card">
    <div class="registration-top">
        <aside class="registration-aside">
            <a class="brand" href="{{ route('home') }}">
                <span class="brand-mark">PG</span>
                <span><strong>Tech PG</strong><small>Recuperar acesso</small></span>
            </a>

            <div class="aside-copy">
                <h2>Recupere o acesso ao painel.</h2>
                <p>Enviamos um link por e-mail para você definir uma nova senha.</p>
            </div>

            <div class="aside-note">
                O link vale por 60 minutos e só pode ser usado uma vez.
            </div>
        </aside>

        <div class="registration-main">
            <div class="form-heading">
                <span class="eyebrow">Recuperação</span>
                <h1>Esqueci minha senha</h1>
                <p>Informe o e-mail cadastrado no painel.</p>
            </div>

            @if (session('status'))
                <div class="success-message">{{ session('status') }}</div>
            @endif

            @include('portal.admin.partials.errors')

            <form class="company-form" method="post" action="{{ route('password.email') }}">
                @csrf

                <label>E-mail
                    <input name="email" type="email" value="{{ old('email') }}" inputmode="email"
                           autocomplete="email" required autofocus>
                </label>

                <div class="form-actions">
                    <button class="primary-button" type="submit">Enviar link</button>
                    <a class="secondary-button" href="{{ route('entrar') }}">Voltar ao acesso</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
