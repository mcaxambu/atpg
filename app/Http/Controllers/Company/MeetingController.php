<?php

namespace App\Http\Controllers\Company;

use App\Actions\CheckInAtMeeting;
use App\Enums\AttendanceStatus;
use App\Enums\MeetingStatus;
use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\MeetingAttendance;
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

    /**
     * Check-in no dia da reunião, com o código mostrado na sala.
     *
     * É diferente de confirmar presença: confirmar é dizer que pretende ir;
     * o check-in é dizer que chegou. Até hoje só a diretoria podia registrar
     * isso, no fim da reunião — e, na prática, não registrava.
     */
    public function checkin(Request $request, Meeting $reuniao, CheckInAtMeeting $checkin): RedirectResponse
    {
        $dados = $request->validate(
            ['codigo' => ['required', 'string', 'max:10']],
            ['codigo.required' => 'Informe o código mostrado na reunião.']
        );

        $empresa = $request->user()->company;

        $resultado = $checkin($reuniao, $empresa, MeetingAttendance::CHECKIN_EMPRESA, $dados['codigo']);

        return match ($resultado) {
            CheckInAtMeeting::OK => back()->with('status', 'Check-in feito. Presença registrada, obrigado!'),
            CheckInAtMeeting::JA_FEITO => back()->with('status', 'Esta empresa já tinha feito o check-in nesta reunião.'),
            CheckInAtMeeting::CODIGO_ERRADO => back()->withErrors(['codigo' => 'Código inválido. Confira o que está sendo mostrado na reunião.']),
            CheckInAtMeeting::FORA_DA_JANELA => back()->withErrors(['codigo' => 'O check-in desta reunião não está aberto.']),
            CheckInAtMeeting::NAO_CONVOCADA => back()->withErrors(['codigo' => 'Sua empresa não está na lista desta reunião. Fale com a diretoria.']),
            default => back(),
        };
    }
}
