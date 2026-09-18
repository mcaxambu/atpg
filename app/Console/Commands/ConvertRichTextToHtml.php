<?php

namespace App\Console\Commands;

use App\Models\Columnist;
use App\Models\CmsPage;
use App\Models\Company;
use App\Models\Event;
use App\Models\JobOpening;
use App\Models\Member;
use App\Models\MeetingMinute;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Converte para HTML o conteudo que ficou em Markdown no banco.
 *
 * Os campos longos do painel passaram a guardar HTML em 17/09/2026. Sem esta
 * conversao o conteudo antigo abriria no editor com a marcacao a mostra
 * (`## titulo`, `| celula |`), e salvar por cima gravaria esse lixo como texto.
 *
 * Roda quantas vezes for preciso: registro que ja esta em HTML e deixado como
 * esta, entao repetir o comando nao acumula conversao.
 */
class ConvertRichTextToHtml extends Command
{
    protected $signature = 'atpg:texto-para-html {--dry-run : Apenas mostra o que mudaria}';

    protected $description = 'Converte para HTML os campos de texto que ainda estao em Markdown';

    /**
     * Campos do editor, por model.
     *
     * A ata fica de fora desta lista: ela tem opcoes proprias de conversao e e
     * tratada em seguida, para a diagramacao das atas publicadas nao mudar.
     */
    private const CAMPOS = [
        Post::class => 'body',
        CmsPage::class => 'body',
        JobOpening::class => 'description',
        Company::class => 'description',
        Member::class => 'summary',
        Event::class => 'description',
        Columnist::class => 'presentation',
    ];

    public function handle(): int
    {
        $ensaio = (bool) $this->option('dry-run');

        if ($ensaio) {
            $this->warn('Ensaio: nada sera gravado.');
        }

        $linhas = [];

        foreach (self::CAMPOS as $classe => $campo) {
            $linhas[] = $this->converter($classe, $campo, $ensaio, comQuebraDeLinha: true);
        }

        // A ata NAO usa soft_break como <br>: trocar isso mudaria a
        // diagramacao das atas que ja estao publicadas.
        $linhas[] = $this->converter(MeetingMinute::class, 'body', $ensaio, comQuebraDeLinha: false);

        $this->table(['Registro', 'Total', 'Já em HTML', 'Convertidos'], $linhas);

        return self::SUCCESS;
    }

    /** @return array{0: string, 1: int, 2: int, 3: int} */
    private function converter(string $classe, string $campo, bool $ensaio, bool $comQuebraDeLinha): array
    {
        $consulta = $classe::query();

        // Registro na lixeira tambem e convertido: se for restaurado depois,
        // voltaria com o formato antigo e abriria torto no editor.
        if (in_array(SoftDeletes::class, class_uses_recursive($classe), true)) {
            $consulta->withTrashed();
        }

        $total = 0;
        $jaEmHtml = 0;
        $convertidos = 0;

        $consulta->orderBy('id')->chunkById(100, function ($registros) use (
            $campo, $ensaio, $comQuebraDeLinha, &$total, &$jaEmHtml, &$convertidos
        ) {
            foreach ($registros as $registro) {
                $texto = $registro->getAttributes()[$campo] ?? null;

                if (blank($texto)) {
                    continue;
                }

                $total++;

                if ($this->pareceHtml($texto)) {
                    $jaEmHtml++;

                    continue;
                }

                $convertidos++;

                if ($ensaio) {
                    $this->line("  {$registro->getTable()} #{$registro->id}: converteria ".mb_strlen($texto).' caracteres');

                    continue;
                }

                $this->gravar($registro, $campo, $texto, $comQuebraDeLinha);
            }
        });

        return [class_basename($classe), $total, $jaEmHtml, $convertidos];
    }

    private function gravar(Model $registro, string $campo, string $texto, bool $comQuebraDeLinha): void
    {
        $opcoes = [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ];

        if ($comQuebraDeLinha) {
            $opcoes['renderer'] = ['soft_break' => "<br>\n"];
        }

        // O mutator do model limpa o HTML; aqui so a conversao.
        $registro->{$campo} = Str::markdown($texto, $opcoes);

        /*
         * Sem mexer em `updated_at`: o portal mostra a data de atualizacao da
         * noticia, e uma conversao tecnica nao deve fazer todo o conteudo
         * parecer editado hoje.
         */
        $registro->timestamps = false;
        $registro->save();
    }

    /**
     * Mesma regra do RendersRichText: marca de bloco de HTML no inicio de uma
     * tag. Markdown escrito por gente nao traz `<p>` nem `<h2>`.
     */
    private function pareceHtml(string $texto): bool
    {
        return preg_match(
            '#<(?:p|div|h[1-6]|ul|ol|li|table|blockquote|img|iframe|figure|br|strong|em|u|s|a)[\s/>]#i',
            $texto
        ) === 1;
    }
}
