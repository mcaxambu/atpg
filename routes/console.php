<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Resumo do funil de novos associados para a diretoria.
 *
 * Segunda de manha, no comeco da semana de trabalho — e nao sexta, quando
 * ninguem vai atras de prospecto. O comando nao manda nada quando nao ha
 * pendencia, entao a caixa de entrada so recebe e-mail que pede acao.
 *
 * ATENCAO: isto so acontece se o `schedule:run` estiver no cron do servidor.
 */
/*
 * Radar: varre as fontes de madrugada, quando ninguém está no painel e os
 * sites da região já publicaram o dia anterior.
 *
 * Só enfileira — publicar continua sendo clique de gente.
 */
Schedule::command('atpg:radar --reler=20')
    ->dailyAt('04:30')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

Schedule::command('atpg:resumo-associados')
    ->weeklyOn(1, '08:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();
