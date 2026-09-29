<?php

namespace App\Actions;

use App\Enums\InteractionType;
use App\Enums\ProspectStage;
use App\Models\Company;
use App\Models\Member;
use App\Models\Prospect;

/**
 * Liga o cadastro recem-enviado ao prospecto que o convidou.
 *
 * O token vem no link do convite e volta escondido no formulario publico. Sem
 * essa costura, a conversa de dois meses da diretoria e o cadastro que chega
 * ficam sendo duas coisas separadas no sistema.
 *
 * Falhar aqui NUNCA pode derrubar o cadastro: quem esta se associando nao tem
 * nada a ver com o funil interno. Por isso todo caminho de erro apenas ignora
 * o convite e deixa o cadastro seguir.
 */
class LinkProspectToRegistration
{
    public function __invoke(?string $token, Company|Member $cadastro): ?Prospect
    {
        if (blank($token)) {
            return null;
        }

        $prospecto = Prospect::query()
            ->aguardandoCadastro()
            ->where('invite_token', $token)
            ->first();

        if (! $prospecto) {
            return null;
        }

        $prospecto->forceFill(
            $cadastro instanceof Company
                ? ['company_id' => $cadastro->id]
                : ['member_id' => $cadastro->id]
        )->save();

        $prospecto->interactions()->create([
            'type' => InteractionType::Sistema,
            'summary' => 'Cadastro recebido pelo convite: "'.$cadastro->name.'".',
            'happened_at' => now(),
        ]);

        // Volta atras nao: quem ja foi aprovado nao regride para "cadastro
        // recebido" se o formulario for enviado de novo com o mesmo link.
        if ($prospecto->stage->emAndamento()) {
            $prospecto->moverPara(ProspectStage::Cadastro);
        }

        return $prospecto;
    }
}
