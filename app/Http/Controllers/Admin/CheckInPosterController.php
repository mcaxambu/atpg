<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Cartaz do check-in, para imprimir e plastificar.
 *
 * O QR aponta para um endereço fixo — e não para uma reunião — porque o cartaz
 * é permanente: precisa valer para toda reunião que vier, sem reimpressão.
 */
class CheckInPosterController extends Controller
{
    public function __invoke(): View
    {
        $url = route('empresa.checkin');

        return view('portal.admin.reunioes.cartaz', [
            'url' => $url,
            // Versão para quem vai digitar à mão, sem https:// nem barra final.
            'urlCurta' => preg_replace('#^https?://#', '', rtrim($url, '/')),
        ]);
    }
}
