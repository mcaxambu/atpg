<?php

namespace App\Http\Controllers\Portal;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Convocacao aberta por link, para enviar no WhatsApp.
 *
 * O token no endereco e o unico segredo: nao ha login. Isso e adequado para
 * confirmar presenca — nao e ato juridico —, mas por isso a pagina nao mostra
 * nada sensivel alem da pauta, e a resposta registra quem respondeu.
 */
class MeetingInvitationController extends Controller
{
    public function show(string $token): View
    {
        $reuniao = $this->findByToken($token);

        $reuniao->load(['agendaItems', 'attendances.company:id,name', 'attendances.member:id,name']);

        return view('portal.reuniao.convocacao', [
            'reuniao' => $reuniao,
            'empresas' => Company::visible()->orderBy('name')->get(['id', 'name']),
            // Associado pessoa fisica: participa por si, entao precisa se
            // achar na lista. Quem tem empresa responde pela empresa.
            'membros' => Member::visible()->whereNull('company_id')->orderBy('name')->get(['id', 'name']),
            'confirmadas' => $reuniao->confirmedCount(),
        ]);
    }

    public function confirm(Request $request, string $token): RedirectResponse
    {
        $reuniao = $this->findByToken($token);

        if (! $reuniao->acceptsConfirmations()) {
            return back()->withErrors([
                'participante' => 'O prazo de confirmação desta reunião já encerrou.',
            ]);
        }

        $dados = $request->validate([
            'participante' => ['required', 'string'],
            'status' => ['required', 'in:'.implode(',', array_keys(AttendanceStatus::replyOptions()))],
            'responded_by' => ['required', 'string', 'max:120'],
        ], [
            'participante.required' => 'Selecione quem você representa.',
            'responded_by.required' => 'Informe seu nome.',
        ]);

        // A chave diz empresa OU membro: ids de um e de outro se repetem, e
        // sem o tipo a confirmacao cairia na linha de outro associado.
        $participante = MeetingAttendance::participantePorChave($dados['participante']);

        if (! $participante) {
            return back()->withErrors(['participante' => 'Selecione quem você representa.']);
        }

        $reuniao->attendances()->updateOrCreate(
            $participante instanceof Company
                ? ['company_id' => $participante->id]
                : ['member_id' => $participante->id],
            [
                'status' => $dados['status'],
                'responded_by' => $dados['responded_by'],
                'responded_at' => now(),
            ]
        );

        return back()->with('confirmacao_registrada', true);
    }

    /**
     * Arquivo de calendario: quem abre o link ja guarda o compromisso.
     */
    public function calendar(string $token): Response
    {
        $reuniao = $this->findByToken($token);

        $formato = fn ($data) => $data->clone()->utc()->format('Ymd\THis\Z');
        $escapar = fn ($texto) => str_replace(['\\', "\n", ',', ';'], ['\\\\', '\n', '\,', '\;'], (string) $texto);

        $local = $reuniao->online_url ?: $reuniao->location;

        $ics = implode("\r\n", array_filter([
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Associacao Tech PG//Reunioes//PT-BR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:reuniao-'.$reuniao->id.'@atpg.net.br',
            'DTSTAMP:'.$formato(now()),
            'DTSTART:'.$formato($reuniao->scheduled_at),
            $reuniao->ends_at ? 'DTEND:'.$formato($reuniao->ends_at) : null,
            'SUMMARY:'.$escapar($reuniao->title),
            $local ? 'LOCATION:'.$escapar($local) : null,
            'DESCRIPTION:'.$escapar($reuniao->description ?: $reuniao->type->label()),
            'URL:'.$reuniao->publicUrl(),
            'END:VEVENT',
            'END:VCALENDAR',
        ]));

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="reuniao-'.$reuniao->id.'.ics"',
        ]);
    }

    private function findByToken(string $token): Meeting
    {
        return Meeting::where('public_token', $token)->firstOrFail();
    }
}
