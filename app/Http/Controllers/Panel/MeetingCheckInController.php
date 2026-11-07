<?php

namespace App\Http\Controllers\Panel;

use App\Actions\CheckInAtMeeting;
use App\Enums\MeetingStatus;
use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\MeetingAttendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Destino do QR code de check-in.
 *
 * O endereço é FIXO (/checkin) porque o QR vai ser impresso e plastificado:
 * ele precisa valer para toda reunião, para sempre, e para qualquer associado
 * — empresa ou pessoa física. Quem descobre qual é a reunião é o portal, pela
 * janela de check-in aberta, não o papel na parede.
 *
 * Por isso o papel sozinho NÃO registra presença: ele leva à tela certa, e o
 * código mostrado na sala é que confirma. Um QR permanente fotografado uma vez
 * serviria para sempre, de qualquer lugar; o código muda a cada reunião e só
 * existe dentro da sala.
 *
 * O QR que a recepção mostra na tela é outro: aquele já carrega o código do
 * dia, então escanear basta.
 */
class MeetingCheckInController extends Controller
{
    public function show(Request $request, CheckInAtMeeting $checkin): View|RedirectResponse
    {
        $reunioes = Meeting::query()
            ->where('status', '!=', MeetingStatus::Cancelada)
            ->whereDate('scheduled_at', '>=', now()->subDay())
            ->whereDate('scheduled_at', '<=', now()->addDay())
            ->orderBy('scheduled_at')
            ->get()
            ->filter(fn (Meeting $m) => $m->checkinAberto())
            ->values();

        $reuniao = $reunioes->first();
        $participante = $request->user()->participanteDeReuniao();

        $presenca = $reuniao?->attendances()->doParticipante($participante)->first();

        /*
         * Código vindo no próprio QR (o da tela da recepção): registra na hora.
         * É o caminho de um toque — escaneou, está registrado.
         */
        if ($reuniao && $request->filled('c')) {
            $resultado = $checkin($reuniao, $participante, MeetingAttendance::CHECKIN_EMPRESA, $request->query('c'));

            if (in_array($resultado, [CheckInAtMeeting::OK, CheckInAtMeeting::JA_FEITO], true)) {
                return redirect()->route('painel.checkin')->with(
                    'status',
                    $resultado === CheckInAtMeeting::OK
                        ? 'Presença registrada. Boa reunião!'
                        : 'Sua presença já estava registrada.'
                );
            }

            return redirect()->route('painel.checkin')
                ->withErrors(['codigo' => 'O código deste QR não vale para a reunião de agora. Digite o código mostrado na sala.']);
        }

        return view('portal.panel.checkin', [
            'reuniao' => $reuniao,
            'presenca' => $presenca,
            'participante' => $participante,
            'outras' => $reunioes->skip(1),
            'layout' => $this->layout($request),
        ]);
    }

    public function store(Request $request, CheckInAtMeeting $checkin): RedirectResponse
    {
        $dados = $request->validate(
            [
                'meeting_id' => ['required', 'exists:meetings,id'],
                'codigo' => ['required', 'string', 'max:10'],
            ],
            ['codigo.required' => 'Digite o código mostrado na reunião.']
        );

        $reuniao = Meeting::findOrFail($dados['meeting_id']);

        $resultado = $checkin(
            $reuniao,
            $request->user()->participanteDeReuniao(),
            MeetingAttendance::CHECKIN_EMPRESA,
            $dados['codigo']
        );

        return match ($resultado) {
            CheckInAtMeeting::OK => redirect()->route('painel.checkin')->with('status', 'Presença registrada. Boa reunião!'),
            CheckInAtMeeting::JA_FEITO => redirect()->route('painel.checkin')->with('status', 'Sua presença já estava registrada.'),
            CheckInAtMeeting::CODIGO_ERRADO => back()->withErrors(['codigo' => 'Código inválido. Confira o que está sendo mostrado na sala.']),
            CheckInAtMeeting::FORA_DA_JANELA => back()->withErrors(['codigo' => 'O check-in desta reunião não está aberto.']),
            CheckInAtMeeting::NAO_CONVOCADA => back()->withErrors(['codigo' => 'Você não está na lista desta reunião. Fale com a diretoria.']),
            default => back(),
        };
    }

    private function layout(Request $request): string
    {
        $user = $request->user();

        return match (true) {
            $user->isAdmin() => 'layouts.admin',
            $user->isCompany() => 'layouts.company',
            default => 'layouts.membro',
        };
    }
}
