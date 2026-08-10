@php
    /**
     * Cartão de parceiro. O link é opcional: parceiro sem site cadastrado
     * aparece igual, só não é clicável.
     *
     * A URL é normalizada aqui porque no CMS é comum digitar "empresa.com.br"
     * sem o esquema — o navegador trataria isso como caminho relativo e o link
     * quebraria dentro do próprio portal.
     */
    $destino = trim((string) $partner->link_url);

    if ($destino !== '' && ! preg_match('#^(https?:)?//#i', $destino) && ! str_starts_with($destino, '/')) {
        $destino = 'https://'.$destino;
    }

    $temLink = $destino !== '';
@endphp

<article @class(['is-linked' => $temLink])>
    @if ($temLink)
        <a href="{{ $destino }}" target="_blank" rel="noopener noreferrer"
           title="Abrir o site de {{ $partner->title }}">
    @endif

    @if ($partner->image_path)
        <img src="{{ asset('storage/'.$partner->image_path) }}" alt="{{ $partner->title }}" loading="lazy">
    @else
        <strong>{{ str($partner->title)->substr(0, 2)->upper() }}</strong>
    @endif

    <span>{{ $partner->title }}</span>

    @if ($temLink)
        </a>
    @endif
</article>
