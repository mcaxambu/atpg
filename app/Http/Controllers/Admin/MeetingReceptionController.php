<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\View\View;

/**
 * Tela de recepção, para abrir num tablet na porta da reunião.
 *
 * Fica fora do layout do painel de propósito: na porta, quem opera precisa de
 * nomes grandes e alvos de toque grandes, não de menu lateral. É a mesma ação
 * de check-in da diretoria — muda só a moldura.
 */
class MeetingReceptionController extends Controller
{
    public function show(Meeting $reuniao): View
    {
        return view('portal.admin.reunioes.recepcao', [
            'reuniao' => $reuniao->load(['attendances.company']),
            'presencas' => $reuniao->attendances->sortBy(fn ($p) => $p->company?->name)->values(),
        ]);
    }
}
