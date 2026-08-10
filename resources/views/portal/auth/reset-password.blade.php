@extends('layouts.registration')

@section('title', 'Definir nova senha')

@section('content')
<section class="registration-card">
    <div class="registration-top">
        <aside class="registration-aside">
            <a class="brand" href="{{ route('home') }}">
                <span class="brand-mark">PG</span>
                <span><strong>Tech PG</strong><small>Definir senha</small></span>
            </a>

            <div class="aside-copy">
                <h2>Escolha uma senha nova.</h2>
                <p>Depois de confirmar, você entra direto no painel.</p>
            </div>

            <div class="aside-note">
                Use pelo menos 8 caracteres, com letras e números.
            </div>
        </aside>

        <div class="registration-main">
            <div class="form-heading">
                <span class="eyebrow">Acesso</span>
                <h1>Definir nova senha</h1>
            </div>

            @include('portal.admin.partials.errors')

            <form class="company-form" method="post" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <label>E-mail
                    <input name="email" type="email" value="{{ old('email', $email) }}" inputmode="email"
                           autocomplete="email" required readonly>
                </label>

                <label>Nova senha
                    <input name="password" type="password" autocomplete="new-password" required autofocus>
                </label>

                <label>Confirmar nova senha
                    <input name="password_confirmation" type="password" autocomplete="new-password" required>
                </label>

                <div class="form-actions">
                    <button class="primary-button" type="submit">Salvar e entrar</button>
                    <a class="secondary-button" href="{{ route('entrar') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
