<?php

namespace App\Console\Commands;

use App\Actions\GerarMensalidades;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Gera as mensalidades do mês corrente. Roda no dia 1, pelo scheduler.
 */
class GerarMensalidadesDoMes extends Command
{
    protected $signature = 'atpg:mensalidades {--competencia= : AAAA-MM; o padrão é o mês de hoje}';

    protected $description = 'Gera as mensalidades do mês para as associadas ativas';

    public function handle(GerarMensalidades $gerar): int
    {
        $competencia = $this->option('competencia')
            ? Carbon::parse($this->option('competencia').'-01')
            : Carbon::today();

        // Valor zero é configuração faltando, não mensalidade de graça: gerar
        // 27 cobranças de R$ 0,00 sujaria o histórico e teria de ser desfeito
        // uma a uma.
        if ((float) GerarMensalidades::valorAtual() <= 0) {
            $this->error('O valor da mensalidade não está configurado. Defina em Financeiro → Configuração.');

            return self::FAILURE;
        }

        $resultado = $gerar($competencia);

        $this->info(sprintf(
            'Competência %s: %d gerada(s), %d já existia(m), R$ %s cada.',
            $competencia->format('m/Y'),
            $resultado['geradas'],
            $resultado['puladas'],
            number_format((float) $resultado['valor'], 2, ',', '.')
        ));

        return self::SUCCESS;
    }
}
