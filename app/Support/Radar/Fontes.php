<?php

namespace App\Support\Radar;

/**
 * De onde o radar lê.
 *
 * Cada entrada foi TESTADA antes de entrar aqui (16/11/2026): chutar endereço
 * de RSS é como esse tipo de rotina nasce quebrada e fica meses sem ninguém
 * perceber, porque "não achou nada" parece um dia fraco de notícia.
 *
 * Achados do teste que vale guardar:
 *
 * - a aRede responde em `/rss`, e não em `/feed/` (esse redireciona para um
 *   404);
 * - o site da UTFPR recusa conexão de fora do Brasil ou de IP de datacenter:
 *   do servidor ele responde 200, da máquina de desenvolvimento não. A
 *   varredura roda no servidor, então funciona — mas testar do lugar errado
 *   daria "fonte morta" para uma fonte viva.
 */
class Fontes
{
    /**
     * @return array<string, array{nome: string, tipo: string, url: string, host: string}>
     */
    public static function todas(): array
    {
        return [
            'acipg' => [
                'nome' => 'ACIPG',
                'tipo' => 'rss',
                'url' => 'https://acipg.org.br/feed/',
                'host' => 'acipg.org.br',
            ],
            'prefeitura' => [
                'nome' => 'Prefeitura de Ponta Grossa',
                'tipo' => 'rss',
                'url' => 'https://www.pontagrossa.pr.gov.br/feed/',
                'host' => 'www.pontagrossa.pr.gov.br',
            ],
            'dcmais' => [
                'nome' => 'Diário dos Campos',
                'tipo' => 'rss',
                'url' => 'https://dcmais.com.br/feed/',
                'host' => 'dcmais.com.br',
            ],
            'arede' => [
                'nome' => 'aRede',
                'tipo' => 'rss',
                'url' => 'https://arede.info/rss',
                'host' => 'arede.info',
            ],
            'bnt' => [
                'nome' => 'BnT Online',
                'tipo' => 'rss',
                'url' => 'https://bntonline.com.br/feed/',
                'host' => 'bntonline.com.br',
            ],
            'utfpr' => [
                'nome' => 'UTFPR Ponta Grossa',
                'tipo' => 'html',
                'url' => 'https://www.utfpr.edu.br/noticias/ponta-grossa',
                'host' => 'www.utfpr.edu.br',
            ],
            'sympla' => [
                'nome' => 'Sympla — Ponta Grossa',
                'tipo' => 'html',
                'url' => 'https://www.sympla.com.br/eventos/ponta-grossa-pr/tecnologia',
                'host' => 'www.sympla.com.br',
            ],
        ];
    }

    public static function nome(?string $slug): string
    {
        return self::todas()[$slug]['nome'] ?? ($slug ?: 'fonte desconhecida');
    }
}
