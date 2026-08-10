@component('mail::message')
# Olá, {{ $recipientName }}

@if ($event === \App\Mail\RegistrationStatusMail::RECEIVED)
Recebemos seu cadastro de **{{ $kind }}** no {{ $siteName }}.

Ele entrou na fila de análise da associação. Assim que a diretoria concluir a avaliação,
você recebe um novo e-mail com o resultado. Não é necessário enviar o cadastro novamente.
@elseif ($event === \App\Mail\RegistrationStatusMail::APPROVED)
Boas notícias: seu cadastro de **{{ $kind }}** foi **aprovado** e já está publicado no portal.

@if ($publicUrl)
@component('mail::button', ['url' => $publicUrl])
Ver perfil no portal
@endcomponent
@endif

Aproveite para revisar as informações publicadas. Se algo estiver desatualizado,
é só responder este e-mail que atualizamos para você.
@else
Analisamos seu cadastro de **{{ $kind }}** no {{ $siteName }} e, por ora, ele **não foi aprovado**.

@if ($rejectionReason)
@component('mail::panel')
{{ $rejectionReason }}
@endcomponent
@endif

Isso não é definitivo: ajustando os pontos acima, você pode enviar um novo cadastro
a qualquer momento.
@endif

@if ($supportEmail)
Dúvidas? Fale com a gente em {{ $supportEmail }}.
@endif

Abraço,<br>
{{ $siteName }}
@endcomponent
