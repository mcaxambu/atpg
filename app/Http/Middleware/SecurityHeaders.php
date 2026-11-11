<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabecalhos de seguranca do portal.
 *
 * O site nao mandava NENHUM deles. Sao instrucoes para o navegador do
 * visitante — nao substituem validacao no servidor, mas fecham a classe de
 * ataque que depende do navegador obedecer o atacante em vez de nos:
 * sequestro de clique, adivinhacao de tipo de arquivo, vazamento do endereco
 * na saida e, principalmente, script injetado de fora.
 *
 * A CSP lista as origens que o portal REALMENTE usa, levantadas do codigo:
 *
 * - cdn.jsdelivr.net — o editor de textos (TinyMCE) carrega de la;
 * - fonts.googleapis.com / fonts.gstatic.com — as fontes do layout;
 * - youtube.com — video embutido dentro do texto rico (o SafeHtml so aceita
 *   iframe do YouTube; sem esta linha o video pararia de aparecer);
 * - viacep.com.br — a busca de endereco por CEP nos formularios de cadastro.
 *
 * 'unsafe-inline' e 'unsafe-eval' continuam: Alpine.js avalia expressao em
 * atributo e o TinyMCE monta codigo em tempo de execucao. Tirar isso exige
 * trocar as duas bibliotecas, nao e um ajuste de cabecalho. O que esta CSP ja
 * impede e o caso que importa aqui: script vindo de OUTRO dominio, que e como
 * um XSS armazenado costuma virar roubo de sessao.
 *
 * Imagem fica em `https:` de proposito — conteudo do CMS aponta para foto de
 * fora, e imagem nao executa.
 */
class SecurityHeaders
{
    private const CSP = [
        "default-src 'self'",
        "base-uri 'self'",
        "object-src 'none'",
        "frame-ancestors 'self'",
        "form-action 'self'",
        // O beacon de estatisticas e injetado pela propria Cloudflare na borda.
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://static.cloudflareinsights.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
        "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net",
        "img-src 'self' data: blob: https:",
        "media-src 'self' https:",
        "connect-src 'self' https://viacep.com.br https://cdn.jsdelivr.net https://cloudflareinsights.com",
        "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $response->headers->set('Content-Security-Policy', implode('; ', self::CSP));

        // HSTS so sob https: mandar em http nao tem efeito e, em
        // desenvolvimento, prenderia o navegador num esquema que nao existe la.
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
