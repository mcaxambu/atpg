<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Um BOM (EF BB BF) no inicio de um arquivo Blade nao e invisivel no
 * resultado: o Blade o imprime, e dentro de um CSS Grid um no de texto
 * nao-vazio vira um item anonimo. Foi assim que a grade de noticias passou a
 * pular a primeira coluna — um bug que nao aparece em revisao de codigo,
 * porque o caractere nao e visivel no editor.
 */
class SemBomNosArquivosTest extends TestCase
{
    #[Test]
    public function nenhum_arquivo_php_comeca_com_bom(): void
    {
        $raiz = dirname(__DIR__, 2);
        $comBom = [];

        foreach (['app', 'config', 'database', 'resources/views', 'routes', 'tests'] as $pasta) {
            $iterador = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator("{$raiz}/{$pasta}", RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterador as $arquivo) {
                if ($arquivo->getExtension() !== 'php') {
                    continue;
                }

                if (file_get_contents($arquivo->getPathname(), false, null, 0, 3) === "\xEF\xBB\xBF") {
                    $comBom[] = str_replace($raiz.DIRECTORY_SEPARATOR, '', $arquivo->getPathname());
                }
            }
        }

        $this->assertSame([], $comBom, "Arquivos com BOM:\n  ".implode("\n  ", $comBom));
    }
}
