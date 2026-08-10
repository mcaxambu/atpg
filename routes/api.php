<?php

use App\Http\Controllers\Api\V1\CompanyController;
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
