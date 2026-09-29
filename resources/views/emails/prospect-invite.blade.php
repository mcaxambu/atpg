@component('mail::message')
# Olá, {{ $nome }}

@if ($mensagem)
{{ $mensagem }}
@else
Foi muito bom conversar com você sobre a {{ $siteName }}.

Se fizer sentido seguir, o próximo passo é preencher o cadastro. Leva poucos minutos,
e a diretoria analisa em seguida.
@endif

@component('mail::button', ['url' => $link])
Preencher o cadastro
@endcomponent

Se o botão não funcionar, copie e cole este endereço no navegador:

{{ $link }}

@if ($assinatura)
{{ $assinatura }}
@else
Abraço,<br>
{{ $siteName }}
@endif
@endcomponent
