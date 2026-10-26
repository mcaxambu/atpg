<?php

namespace App\Actions;

use App\Models\Company;
use App\Models\Meeting;
use App\Models\MeetingAttendance;

/**
 * Registra a chegada de uma empresa à reunião.
 *
 * Um ponto só para os dois caminhos — a empresa pelo celular e a diretoria na
 * recepção —, porque a regra do que significa "presente" tem de ser a mesma
 * nos dois. O check-in liga `attended`: é a mesma coluna que a lista de
 * presentes e os relatórios de participação já usam, então nada precisou ser
 * recontado em outro lugar.
 */
class CheckInAtMeeting
{
    public const OK = 'ok';

    public const JA_FEITO = 'ja_feito';

    public const FORA_DA_JANELA = 'fora_da_janela';

    public const CODIGO_ERRADO = 'codigo_errado';

    public const NAO_CONVOCADA = 'nao_convocada';

    /**
     * @param  string|null  $codigo  exigido quando quem registra é a própria empresa
     */
    public function __invoke(
        Meeting $reuniao,
        Company $empresa,
        string $origem = MeetingAttendance::CHECKIN_EMPRESA,
        ?string $codigo = null,
    ): string {
        if (! $reuniao->checkinAberto()) {
            return self::FORA_DA_JANELA;
        }

        /*
         * O código só é exigido de quem se registra sozinho. A diretoria, na
         * recepção, está vendo a pessoa: pedir o código ali seria burocracia
         * sem ganho nenhum.
         */
        if ($origem === MeetingAttendance::CHECKIN_EMPRESA) {
            $informado = strtoupper(trim((string) $codigo));

            if ($informado === '' || $informado !== strtoupper($reuniao->codigoDeCheckin())) {
                return self::CODIGO_ERRADO;
            }
        }

        $presenca = $reuniao->attendances()->firstWhere('company_id', $empresa->id);

        // Empresa fora da lista de convocação não entra pelo check-in: a lista
        // é quem define o universo da reunião, e o quórum sai dela.
        if (! $presenca) {
            return self::NAO_CONVOCADA;
        }

        if ($presenca->fezCheckin()) {
            return self::JA_FEITO;
        }

        $presenca->forceFill([
            'attended' => true,
            'checked_in_at' => now(),
            'checked_in_source' => $origem,
        ])->save();

        return self::OK;
    }
}
