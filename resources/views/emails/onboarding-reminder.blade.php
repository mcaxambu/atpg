@component('mail::message')
# Olá, {{ $nome }}

Que bom ter vocês na {{ $siteName }}.

Passando para avisar que faltam alguns passos para o perfil ficar completo no portal —
são rápidos e fazem diferença em quem encontra vocês por lá:

@foreach ($pendentes as $passo)
- **{{ $passo['titulo'] }}** — {{ $passo['ajuda'] }}
@endforeach

@component('mail::button', ['url' => $painel])
Abrir meu painel
@endcomponent

Qualquer dúvida, é só responder este e-mail.

Abraço,<br>
{{ $siteName }}
@endcomponent
