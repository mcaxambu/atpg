<?php

namespace App\Http\Controllers\Company;

use App\Enums\AttendanceStatus;
use App\Enums\MeetingStatus;
use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Reunioes vistas pela empresa associada, com confirmacao de presenca.
 */
class MeetingController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $comPresenca = fn ($query) => $query
            ->with(['agendaItems', 'attendances' => fn ($q) => $q->where('company_id', $companyId)])
            ->withCount(['attendances as confirmadas' => fn ($q) => $q->where('status', AttendanceStatus::Confirmado)]);

        return view('portal.company.reunioes.index', [
            'proximas' => $comPresenca(Meeting::upcoming())->get(),
            'anteriores' => $comPresenca(
                Meeting::query()
                    ->where(fn ($q) => $q->where('scheduled_at', '<', now()->startOfDay())
                        ->orWhere('status', '!=', MeetingStatus::Agendada))
            )->orderByDesc('scheduled_at')->limit(10)->get(),
        ]);
    }

    public function confirm(Request $request, Meeting $reuniao): RedirectResponse
    {
        if (! $reuniao->acceptsConfirmations()) {
            return back()->withErrors(['status' => 'O prazo de confirmação desta reunião já encerrou.']);
        }

        $dados = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(AttendanceStatus::replyOptions()))],
        ]);

        $reuniao->attendances()->updateOrCreate(
            ['company_id' => $request->user()->company_id],
            [
                'status' => $dados['status'],
                'responded_by' => $request->user()->name,
                'responded_at' => now(),
            ]
        );

        return back()->with('status', 'Resposta registrada. Obrigado!');
    }
}
