@props([
    'variant' => 'full',
    'tone' => 'color',
    'plate' => false,
    'plateClass' => 'rounded-xl bg-white p-1.5',
])

@php
    use App\Models\SiteSetting;

    /**
     * Quatro arquivos, combinando duas dimensões:
     *   variant: full (horizontal, com o nome) | symbol (emblema quadrado)
     *   tone:    color (fundos claros)         | white (fundos escuros)
     *
     * A versão branca existe porque o texto da logo é azul-escuro e sumiria
     * no tema escuro do painel e no lateral azul da tela de acesso.
     */
    $arquivo = 'atpg-'
        .($variant === 'symbol' ? 'symbol' : 'logo')
        .($tone === 'white' ? '-branca' : '')
        .'.png';

    // A logo enviada em Configurações do Portal substitui apenas a horizontal
    // colorida: não há campo no painel para símbolo nem para versão branca, e
    // espremer a horizontal num quadrado a deixaria ilegível.
    $custom = ($variant === 'full' && $tone === 'color')
        ? SiteSetting::getValue('logo_path')
        : null;

    $src = $custom
        ? asset('storage/'.$custom)
        : asset('images/'.$arquivo).'?v='.(@filemtime(public_path('images/'.$arquivo)) ?: '1');

    $alt = SiteSetting::getValue('site_name') ?: 'Associação Tech PG';
@endphp

@if ($plate)
    <span class="inline-grid place-items-center {{ $plateClass }}">
        <img src="{{ $src }}" alt="{{ $alt }}" {{ $attributes->merge(['class' => 'object-contain']) }}>
    </span>
@else
    <img src="{{ $src }}" alt="{{ $alt }}" {{ $attributes->merge(['class' => 'object-contain']) }}>
@endif
