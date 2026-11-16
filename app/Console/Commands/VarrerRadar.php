<?php

namespace App\Console\Commands;

use App\Enums\RadarStatus;
use App\Enums\RadarTipo;
use App\Models\RadarItem;
use App\Support\LinkPreview;
use Anthropic\Core\Exceptions\AuthenticationException;
use App\Support\Radar\ChaveRecusada;
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
        {--sem-ia : coleta e guarda sem classificar (não gasta a API)}
        {--reler=0 : relê N itens que ficaram sem classificação, antes de varrer}';

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

        /*
         * Sem chave utilizável o radar COLETA e guarda os links, mas não
         * classifica: encher a fila de "não consegui ler" todas as noites
         * transformaria a tela num monte de lixo que ninguém abre de novo.
         *
         * "Utilizável" inclui ter cara de chave. O texto de exemplo copiado
         * de uma instrução para dentro do .env já aconteceu aqui (16/11), e
         * sem esta conferência a varredura gastava uma chamada recusada por
         * item, toda madrugada, sem nada na tela explicando por quê.
         */
        $chave = (string) config('services.anthropic.key');
        $chaveServe = $chave !== '' && str_starts_with($chave, 'sk-ant-');

        $semIa = $this->option('sem-ia') || ! $chaveServe;

        if (! $this->option('sem-ia') && ! $chaveServe) {
            $this->warn($chave === ''
                ? 'ANTHROPIC_API_KEY não configurada: coletando sem classificar.'
                : 'ANTHROPIC_API_KEY não parece uma chave (deve começar com "sk-ant-"): coletando sem classificar.');
        }

        $limite = max(1, (int) $this->option('limite'));
        $novos = 0;
        $lidos = 0;

        try {
            [$novos, $lidos] = $this->varrer($fontes, $limite, $semIa, $leitor, $preview, $coletor);
        } catch (ChaveRecusada $erro) {
            $this->error($erro->getMessage());
            $this->line('  Os links continuam guardados: corrija a chave e rode com --reler para classificá-los.');

            return self::FAILURE;
        }

        $naFila = RadarItem::esperando()->count();

        $this->info("Radar: {$novos} link(s) novo(s), {$lidos} lido(s). {$naFila} esperando decisão.");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, array{nome: string, tipo: string, url: string, host: string}>  $fontes
     * @return array{0: int, 1: int}
     */
    private function varrer(array $fontes, int $limite, bool $semIa, LeitorDeItem $leitor, LinkPreview $preview, Coletor $coletor): array
    {
        $novos = 0;
        $lidos = 0;

        // O que foi coletado antes de haver chave (ou numa noite em que a API
        // falhou) continua guardado; sem isto ele ficaria parado para sempre,
        // porque a varredura só lê o que acabou de chegar.
        if (! $semIa && ($quantos = (int) $this->option('reler')) > 0) {
            $lidos += $this->relerPendentes($quantos, $leitor, $preview);
        }

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

        return [$novos, $lidos];
    }

    /**
     * Relê o que ficou sem classificação, do mais recente para o mais antigo.
     */
    private function relerPendentes(int $quantos, LeitorDeItem $leitor, LinkPreview $preview): int
    {
        $pendentes = RadarItem::where('status', RadarStatus::Ilegivel)
            ->orderByDesc('created_at')
            ->limit($quantos)
            ->get();

        foreach ($pendentes as $item) {
            $this->classificar($item, [
                'fonte' => $item->fonte,
                'url' => $item->url,
                'titulo' => (string) $item->titulo_original,
                'resumo' => $item->resumo_original,
                'publicado_em' => $item->publicado_em,
            ], $leitor, $preview);
        }

        if ($pendentes->isNotEmpty()) {
            $this->line('  relidos: '.$pendentes->count());
        }

        return $pendentes->count();
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
        } catch (AuthenticationException $erro) {
            // Chave recusada não é problema DESTE item: é da configuração.
            // Seguir em frente gastaria uma chamada recusada por item e
            // marcaria a coleta inteira como ilegível por engano.
            $item->update(['erro' => 'Chave da API recusada.']);

            throw new ChaveRecusada('A API recusou a chave (ANTHROPIC_API_KEY). Nada foi classificado.', previous: $erro);
        } catch (\Throwable $erro) {
            Log::warning('Radar: falha ao ler item.', ['url' => $item->url, 'erro' => $erro->getMessage()]);

            $item->update(['erro' => mb_substr($erro->getMessage(), 0, 500)]);
        }
    }
}
