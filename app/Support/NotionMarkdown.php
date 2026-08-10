<?php

namespace App\Support;

/**
 * Limpeza do Markdown exportado do Notion.
 *
 * O export do Notion tem tres particularidades que atrapalham quando so o
 * texto e importado:
 *
 *  1. arquivo salvo com BOM e quebras CRLF;
 *  2. o titulo da pagina vem repetido como H1 na primeira linha — e a tela
 *     de leitura ja mostra o titulo da ata acima;
 *  3. imagens viram arquivos numa pasta ao lado, referenciadas por caminho
 *     relativo. Sem a pasta, seriam imagens quebradas na tela.
 */
class NotionMarkdown
{
    /**
     * @return array{body: ?string, imagens_removidas: int}
     */
    public static function clean(?string $conteudo): array
    {
        if (blank($conteudo)) {
            return ['body' => null, 'imagens_removidas' => 0];
        }

        $texto = preg_replace('/^\xEF\xBB\xBF/', '', $conteudo);
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);

        [$texto, $removidas] = self::removerImagensRelativas($texto);

        $texto = self::removerTituloDuplicado($texto);
        $texto = trim($texto);

        return [
            'body' => $texto === '' ? null : $texto,
            'imagens_removidas' => $removidas,
        ];
    }

    /**
     * Remove ![alt](caminho/relativo.png), preservando imagens hospedadas
     * (http/https), que continuam funcionando.
     *
     * @return array{0: string, 1: int}
     */
    private static function removerImagensRelativas(string $texto): array
    {
        $removidas = 0;

        $texto = preg_replace_callback(
            '/!\[[^\]]*\]\(([^)]+)\)/',
            function (array $m) use (&$removidas) {
                $destino = trim($m[1]);

                if (preg_match('#^(https?:)?//#i', $destino) || str_starts_with($destino, 'data:')) {
                    return $m[0];
                }

                $removidas++;

                return '';
            },
            $texto
        );

        // Linhas que ficaram vazias por causa da remocao
        $texto = preg_replace("/\n{3,}/", "\n\n", $texto);

        return [$texto, $removidas];
    }

    /**
     * Tira o H1 da primeira linha: no Notion ele e sempre o titulo da pagina,
     * que a tela de leitura ja exibe.
     */
    private static function removerTituloDuplicado(string $texto): string
    {
        return preg_replace('/\A\s*#\s+[^\n]+\n+/', '', $texto, 1);
    }
}
