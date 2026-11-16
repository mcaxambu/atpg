<?php

namespace App\Http\Controllers\Company;

use App\Enums\MensalidadeStatus;
use App\Http\Controllers\Controller;
use App\Models\Mensalidade;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * As mensalidades vistas pela própria associada.
 *
 * Só leitura: ela confere o que deve e o que já pagou. Quem dá baixa é a
 * diretoria — deixar a empresa marcar o próprio pagamento seria entregar o
 * caixa para quem paga.
 */
class MensalidadeController extends Controller
{
    public function index(Request $request): View
    {
        $empresa = $request->user()->company;

        $mensalidades = Mensalidade::query()
            ->where('company_id', $empresa?->id)
            ->orderByDesc('competencia')
            ->get();

        return view('portal.company.mensalidades', [
            'empresa' => $empresa,
            'mensalidades' => $mensalidades,
            'emAberto' => $mensalidades->where('status', MensalidadeStatus::Pendente),
        ]);
    }

    /**
     * Comprovante de uma mensalidade paga, para imprimir.
     */
    public function recibo(Request $request, Mensalidade $mensalidade): View
    {
        abort_unless($mensalidade->company_id === $request->user()->company_id, 404);
        abort_unless($mensalidade->status === MensalidadeStatus::Paga, 404);

        return view('portal.company.recibo', [
            'mensalidade' => $mensalidade->load('company'),
        ]);
    }
}
