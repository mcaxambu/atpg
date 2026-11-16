<?php

use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\AsaasWebhookController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\SpecialtyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API publica do portal (v1)
|--------------------------------------------------------------------------
|
| Somente leitura e somente conteudo ja aprovado e publicado, os mesmos
| registros que aparecem no site. Sem autenticacao, com limite de taxa por IP.
|
*/

Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function () {
    Route::get('empresas', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('empresas/{slug}', [CompanyController::class, 'show'])->name('companies.show');

    Route::get('membros', [MemberController::class, 'index'])->name('members.index');
    Route::get('membros/{slug}', [MemberController::class, 'show'])->name('members.show');

    Route::get('especialidades', [SpecialtyController::class, 'index'])->name('specialties.index');
});

/*
| Retorno do Asaas.
|
| Fica aqui, e nao em web.php, porque POST de fora nao tem token CSRF — e
| desligar a protecao caso a caso no grupo web e o tipo de excecao que um dia
| alguem copia para onde nao devia.
|
| O endereco e do proprio portal (atpg.net.br), que e o dominio da associacao
| e nao vai ser renomeado nem suspenso: webhook apontado para endereco de
| terceiro derruba o retorno de todas as cobrancas quando aquele some.
|
| Limite alto de taxa porque o Asaas reenvia em rajada depois de uma queda,
| e recusar reenvio conta como falha para ele.
*/
Route::post('asaas/webhook', AsaasWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('asaas.webhook');
