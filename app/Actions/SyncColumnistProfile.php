<?php

namespace App\Actions;

use App\Models\Columnist;
use App\Models\Company;
use App\Models\Member;

/**
 * Liga e desliga o perfil de colunista a partir da caixa marcada no cadastro
 * do membro ou da empresa.
 *
 * Desmarcar NAO apaga o perfil, so o desativa. As colunas ja publicadas
 * continuam existindo e apontando para ele — apagar o colunista deixaria textos
 * sem assinatura, e remarcar a caixa depois criaria um perfil novo, com outro
 * endereco, quebrando os links que ja circularam.
 */
class SyncColumnistProfile
{
    public function __invoke(Member|Company $cadastro, bool $eColunista, ?string $nomeDeAssinatura = null): ?Columnist
    {
        $chave = $cadastro instanceof Member
            ? ['member_id' => $cadastro->id]
            : ['company_id' => $cadastro->id];

        $perfil = Columnist::where($chave)->first();

        if (! $eColunista) {
            $perfil?->update(['is_active' => false]);

            return $perfil;
        }

        if ($perfil) {
            $perfil->update([
                'name' => $nomeDeAssinatura,
                'is_active' => true,
            ]);

            return $perfil;
        }

        // O slug sai do nome de assinatura quando ha um; senao, do cadastro.
        $base = filled($nomeDeAssinatura) ? $nomeDeAssinatura : $cadastro->name;

        return Columnist::create($chave + [
            'name' => $nomeDeAssinatura,
            'slug' => Columnist::uniqueSlug($base),
            'is_active' => true,
        ]);
    }
}
