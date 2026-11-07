<?php

namespace App\Http\Controllers\Panel;

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
 * Reunioes vistas por quem participa delas.
 *
 * UM endereco para empresa associada e para membro pessoa fisica, como o
 * forum: a reuniao e a mesma, muda so a moldura em volta. O associado sem
 * empresa vale o mesmo que uma empresa na convocacao, no check-in e no quorum
 * — decisao do usuario em 06/11/2026.
 *
 * Quem e o participante sai de `User::participanteDeReuniao()`, nunca do
 * papel: ha gente da diretoria que tambem e associada, e ha membro que
 * participa pela empresa onde trabalha.
 */
class MeetingController extends Controller
{
    public function index(Request $request): View
    {
        $participante = $request->user()->participanteDeReuniao();

        $comPresenca = fn ($query) => $query
            ->with([
                'agendaItems',
                'attendances' => fn ($q) => $q->doParticipante($participante),
            ])
            ->withCount(['attendances as confirmadas' => fn ($q) => $q->where('status', AttendanceStatus::Confirmado)]);

        return view('portal.panel.reunioes.index', $this->comum($request) + [
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
        $participante = $request->user()->participanteDeReuniao();

        if (! $participante) {
            return back()->withErrors(['status' => 'Sua conta não está ligada a uma empresa nem a um cadastro de associado.']);
        }

        if (! $reuniao->acceptsConfirmations()) {
            return back()->withErrors(['status' => 'O prazo de confirmação desta reunião já encerrou.']);
        }

        $dados = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(AttendanceStatus::replyOptions()))],
        ]);

        $reuniao->attendances()->updateOrCreate(
            $participante instanceof \App\Models\Company
                ? ['company_id' => $participante->id]
                : ['member_id' => $participante->id],
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
     * o check-in é dizer que chegou.
     */
    public function checkin(Request $request, Meeting $reuniao, CheckInAtMeeting $checkin): RedirectResponse
    {
        $dados = $request->validate(
            ['codigo' => ['required', 'string', 'max:10']],
            ['codigo.required' => 'Informe o código mostrado na reunião.']
        );

        $resultado = $checkin(
            $reuniao,
            $request->user()->participanteDeReuniao(),
            MeetingAttendance::CHECKIN_EMPRESA,
            $dados['codigo']
        );

        return match ($resultado) {
            CheckInAtMeeting::OK => back()->with('status', 'Check-in feito. Presença registrada, obrigado!'),
            CheckInAtMeeting::JA_FEITO => back()->with('status', 'Seu check-in nesta reunião já estava feito.'),
            CheckInAtMeeting::CODIGO_ERRADO => back()->withErrors(['codigo' => 'Código inválido. Confira o que está sendo mostrado na reunião.']),
            CheckInAtMeeting::FORA_DA_JANELA => back()->withErrors(['codigo' => 'O check-in desta reunião não está aberto.']),
            CheckInAtMeeting::NAO_CONVOCADA => back()->withErrors(['codigo' => 'Você não está na lista desta reunião. Fale com a diretoria.']),
            default => back(),
        };
    }

    /**
     * Entra na videochamada PELO PORTAL, registrando a presença no caminho.
     *
     * É o que dá para fazer sem Google Workspace: a API que lista quem esteve
     * numa chamada do Meet existe só na conta paga, com admin e conta de
     * serviço. Aqui o portal vira a porta de entrada — registra quem clicou
     * para entrar e encaminha.
     *
     * Falha no registro NUNCA segura a pessoa: ela vai para a chamada de
     * qualquer jeito. Perder a reunião por causa do nosso controle interno
     * seria trocar um problema pequeno por um grande.
     */
    public function entrar(Request $request, Meeting $reuniao, CheckInAtMeeting $checkin): RedirectResponse
    {
        abort_unless(filled($reuniao->online_url), 404);

        try {
            $checkin($reuniao, $request->user()->participanteDeReuniao(), MeetingAttendance::CHECKIN_ONLINE);
        } catch (\Throwable $erro) {
            report($erro);
        }

        return redirect()->away($reuniao->online_url);
    }

    /**
     * A moldura em volta: cada painel mantém o próprio menu.
     *
     * @return array<string, mixed>
     */
    private function comum(Request $request): array
    {
        $user = $request->user();

        return [
            'layout' => match (true) {
                $user->isAdmin() => 'layouts.admin',
                $user->isCompany() => 'layouts.company',
                default => 'layouts.membro',
            },
            'participante' => $user->participanteDeReuniao(),
        ];
    }
}
