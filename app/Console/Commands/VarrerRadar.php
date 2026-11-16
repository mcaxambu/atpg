<?php

namespace App\Console\Commands;

use App\Enums\RadarStatus;
use App\Enums\RadarTipo;
use App\Models\RadarItem;
use App\Support\LinkPreview;
use App\Support\Radar\Coletor;
use App\Support\Radar\Fontes;
use App\Support\Radar\LeitorDeItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Varre as fontes e enche a fila do Radar.
 *
 * Roda de madrugada pelo scheduler. NADA daqui vai ao portal sozinho: o
 * comando só enfileira; publicar é clique de gente na tela do painel.
 */
class VarrerRadar extends Command
{
    protected $signature = 'atpg:radar
        {--fonte= : varre só esta fonte}
        {--limite=12 : máximo de itens novos lidos por fonte}
        {--sem-ia : coleta e guarda sem classificar (não gasta a API)}';

    protected $description = 'Procura eventos, notícias e editais de tecnologia da região';

    public function handle(Coletor $coletor, LeitorDeItem $leitor, LinkPreview $preview): int
    {
        $fontes = $this->option('fonte')
            ? array_intersect_key(Fontes::todas(), [$this->option('fonte') => true])
            : Fontes::todas();

        if ($fontes === []) {
            $this->error('Fonte desconhecida. Conhecidas: '.implode(', ', array_keys(Fontes::todas())));

            return self::FAILURE;
        }

        // Sem chave configurada o radar COLETA e guarda os links, mas não
        // classifica: encher a fila de "não consegui ler" todas as noites
        // transformaria a tela num monte de lixo que ninguém abre de novo.
        $semIa = $this->option('sem-ia') || blank(config('services.anthropic.key'));

        if ($semIa && ! $this->option('sem-ia')) {
            $this->warn('ANTHROPIC_API_KEY não configurada: coletando sem classificar.');
        }

        $limite = max(1, (int) $this->option('limite'));
        $novos = 0;
        $lidos = 0;

        foreach ($fontes as $slug => $fonte) {
            $candidatos = $coletor->coletar($slug);

            $this->line("  {$fonte['nome']}: ".count($candidatos).' link(s)');

            $desteFonte = 0;

            foreach ($candidatos as $candidato) {
                if ($desteFonte >= $limite) {
                    break;
                }

                $hash = RadarItem::hashDa($candidato['url']);

                // Já visto é já visto, qualquer que tenha sido a decisão: o
                // que foi descartado não volta para a fila amanhã.
                if (RadarItem::where('url_hash', $hash)->exists()) {
                    continue;
                }

                $item = RadarItem::create([
                    'fonte' => $slug,
                    'url' => $candidato['url'],
                    'url_hash' => $hash,
                    'titulo_original' => $candidato['titulo'],
                    'resumo_original' => $candidato['resumo'],
                    'publicado_em' => $candidato['publicado_em'],
                    'status' => RadarStatus::Ilegivel,
                ]);

                $novos++;
                $desteFonte++;

                if ($semIa) {
                    continue;
                }

                $this->classificar($item, $candidato, $leitor, $preview);
                $lidos++;
            }
        }

        $naFila = RadarItem::esperando()->count();

        $this->info("Radar: {$novos} link(s) novo(s), {$lidos} lido(s). {$naFila} esperando decisão.");

        return self::SUCCESS;
    }

    /**
     * @param  array{fonte: string, url: string, titulo: string, resumo: ?string, publicado_em: ?\Illuminate\Support\Carbon}  $candidato
     */
    private function classificar(RadarItem $item, array $candidato, LeitorDeItem $leitor, LinkPreview $preview): void
    {
        try {
            // Listagem em HTML não traz texto: o resumo vem da própria página
            // do item, pelas metatags que o WhatsApp também usa.
            $texto = $candidato['resumo'];

            if (blank($texto)) {
                $cartao = $preview->fetch($candidato['url']);
                $texto = trim(($cartao['title'] ?? '').' '.($cartao['description'] ?? ''));
            }

            $campos = $leitor->ler($candidato, $texto ?: null);

            if ($campos === null) {
                $item->update(['erro' => 'A leitura automática não devolveu dados utilizáveis.']);

                return;
            }

            $item->update($campos + [
                // Fora do assunto já nasce descartado: a fila é para o que
                // merece decisão, não para tudo que o robô encostou.
                'status' => $campos['tipo'] === RadarTipo::Irrelevante->value
                    ? RadarStatus::Descartado
                    : RadarStatus::Novo,
                'erro' => null,
            ]);
        } catch (\Throwable $erro) {
            Log::warning('Radar: falha ao ler item.', ['url' => $item->url, 'erro' => $erro->getMessage()]);

            $item->update(['erro' => mb_substr($erro->getMessage(), 0, 500)]);
        }
    }
}
