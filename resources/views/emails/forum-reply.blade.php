@component('mail::message')
# Nova resposta no fórum

**{{ $autor }}** respondeu em *{{ $titulo }}*:

@component('mail::panel')
{{ $trecho }}
@endcomponent

@component('mail::button', ['url' => $link])
Ver e responder
@endcomponent

Você recebe este aviso porque abriu este tópico ou respondeu nele.

{{ $siteName }}
@endcomponent
