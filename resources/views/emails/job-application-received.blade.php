@component('mail::message')
# Olá, {{ $recipientName }}

Você recebeu uma nova candidatura para a vaga **{{ $job?->title }}**.

@component('mail::panel')
**{{ $application->name }}** se candidatou em {{ $application->created_at->format('d/m/Y \à\s H:i') }}.
@endcomponent

Esta vaga já soma **{{ $total }}** {{ $total === 1 ? 'candidatura' : 'candidaturas' }}.

@component('mail::button', ['url' => $panelUrl])
Ver a candidatura no painel
@endcomponent

{{--
    Contato e curriculo ficam de fora deste e-mail de proposito: sao dados
    pessoais de terceiro, e o painel tem controle de acesso que o e-mail nao tem.
--}}
O contato e o currículo estão no painel, na página desta vaga. Use os dados apenas
para este processo seletivo e apague a candidatura quando ela não for mais necessária.

@if ($supportEmail)
Dúvidas? Fale com a gente em {{ $supportEmail }}.
@endif

Abraço,<br>
{{ $siteName }}
@endcomponent
