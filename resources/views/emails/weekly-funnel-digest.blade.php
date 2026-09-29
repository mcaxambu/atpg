@component('mail::message')
# Novos associados

@if ($atrasados->isNotEmpty())
## Passou da data ({{ $atrasados->count() }})

@foreach ($atrasados as $p)
- **{{ $p->name }}** — {{ $p->next_action ?: 'sem descrição do próximo passo' }} (venceu em {{ $p->next_action_at->format('d/m') }}{{ $p->owner ? ', com '.$p->owner->name : '' }})
@endforeach
@endif

@if ($parados->isNotEmpty())
## Parados há semanas ({{ $parados->count() }})

@foreach ($parados as $p)
- **{{ $p->name }}** — {{ $p->stage->label() }}, sem andar há {{ $p->diasParado() }} dias
@endforeach
@endif

@if ($atrasados->isNotEmpty() || $parados->isNotEmpty())
@component('mail::button', ['url' => $funil])
Abrir o funil
@endcomponent
@endif

@if ($incompletos->isNotEmpty())
## Entraram e não completaram o perfil ({{ $incompletos->count() }})

@foreach ($incompletos as $i)
- **{{ $i->associado->name }}** — {{ $i->concluidos() }} de {{ $i->total() }} passos, há {{ $i->diasDesdeAprovacao() }} dias no portal
@endforeach

@component('mail::button', ['url' => $acompanhamento])
Ver o acompanhamento
@endcomponent
@endif

@if ($atrasados->isEmpty() && $parados->isEmpty() && $incompletos->isEmpty())
Nada pendente por aqui nesta semana.
@endif

{{ $siteName }}
@endcomponent
