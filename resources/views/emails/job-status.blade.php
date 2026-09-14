@component('mail::message')
# Olá, {{ $recipientName }}

@if ($event === \App\Mail\JobStatusMail::APPROVED)
Sua vaga **{{ $job->title }}** foi aprovada e já está publicada no portal do {{ $siteName }}.

@if ($publicUrl)
@component('mail::button', ['url' => $publicUrl])
Ver a vaga no portal
@endcomponent
@endif

As candidaturas chegam pelo próprio portal, com currículo anexado, e ficam disponíveis
no painel da sua empresa. Você recebe um aviso a cada nova inscrição.

@if ($job->closes_at)
Esta vaga sai do ar automaticamente em **{{ $job->closes_at->format('d/m/Y') }}**.
@else
Esta vaga fica no ar até você encerrá-la pelo painel.
@endif
@else
Analisamos sua vaga **{{ $job->title }}** e, por ora, ela **não foi publicada**.

@if ($job->rejection_reason)
@component('mail::panel')
{{ $job->rejection_reason }}
@endcomponent
@endif

Isso não é definitivo: ajustando os pontos acima e salvando a vaga no painel,
ela volta para a fila de análise.

@component('mail::button', ['url' => $panelUrl])
Editar a vaga
@endcomponent
@endif

@if ($supportEmail)
Dúvidas? Fale com a gente em {{ $supportEmail }}.
@endif

Abraço,<br>
{{ $siteName }}
@endcomponent
