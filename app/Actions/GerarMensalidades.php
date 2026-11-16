<?php

namespace App\Actions;

use App\Enums\MensalidadeStatus;
use App\Models\Company;
use App\Models\Mensalidade;
use App\Models\SiteSetting;
use Illuminate\Support\Carbon;

/**
 * Gera as mensalidades de um mês.
 *
 * Um ponto só, usado pelo comando do cron e pelo botão do painel — para a
 * regra de quem paga, quanto e quando vencer não existir em duas versões que
 * um dia discordam.
 *
 * É IDEMPOTENTE: rodar de novo o mesmo mês não cobra duas vezes. O banco
 * também garante isso (índice único por empresa e competência), mas falhar
 * calado numa exceção de chave duplicada seria péssima forma de descobrir.
 */
class GerarMensalidades
{
    public const CHAVE_VALOR = 'mensalidade_valor';

    public const CHAVE_DIA_VENCIMENTO = 'mensalidade_dia_vencimento';

    /**
     * @return array{geradas: int, puladas: int, valor: string}
     */
    public function __invoke(?Carbon $competencia = null, ?int $registradoPor = null): array
    {
        $competencia = ($competencia ?? Carbon::today())->copy()->startOfMonth();

        $valor = self::valorAtual();
        $vencimento = $this->vencimentoDe($competencia);

        $geradas = 0;
        $puladas = 0;

        // Só associadas publicadas e não isentas. Empresa reprovada ou na
        // lixeira não recebe cobrança — e isso já aconteceu em sistema que
        // cobrava por lista velha.
        $empresas = Company::visible()->where('mensalidade_isenta', false)->orderBy('name')->get();

        foreach ($empresas as $empresa) {
            $jaExiste = Mensalidade::where('company_id', $empresa->id)
                ->whereDate('competencia', $competencia)
                ->exists();

            if ($jaExiste) {
                $puladas++;

                continue;
            }

            Mensalidade::create([
                'company_id' => $empresa->id,
                'competencia' => $competencia,
                'vencimento' => $vencimento,
                'valor' => $valor,
                'status' => MensalidadeStatus::Pendente,
                'registrado_por' => $registradoPor,
            ]);

            $geradas++;
        }

        return ['geradas' => $geradas, 'puladas' => $puladas, 'valor' => $valor];
    }

    public static function valorAtual(): string
    {
        return (string) (SiteSetting::getValue(self::CHAVE_VALOR) ?: '0.00');
    }

    public static function diaDeVencimento(): int
    {
        $dia = (int) SiteSetting::getValue(self::CHAVE_DIA_VENCIMENTO, '10');

        return max(1, min(28, $dia ?: 10));
    }

    /**
     * O dia 31 não existe em todo mês; o teto de 28 evita que a cobrança
     * escorregue de mês sozinha em fevereiro.
     */
    private function vencimentoDe(Carbon $competencia): Carbon
    {
        return $competencia->copy()->day(self::diaDeVencimento());
    }
}
