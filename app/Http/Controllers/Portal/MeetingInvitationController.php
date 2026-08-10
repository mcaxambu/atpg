<?php

namespace App\Http\Controllers\Portal;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Meeting;
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

        $reuniao->load(['agendaItems', 'attendances.company:id,name']);

        return view('portal.reuniao.convocacao', [
            'reuniao' => $reuniao,
            'empresas' => Company::visible()->orderBy('name')->get(['id', 'name']),
            'confirmadas' => $reuniao->confirmedCount(),
        ]);
    }

    public function confirm(Request $request, string $token): RedirectResponse
    {
        $reuniao = $this->findByToken($token);

        if (! $reuniao->acceptsConfirmations()) {
            return back()->withErrors([
                'company_id' => 'O prazo de confirmação desta reunião já encerrou.',
            ]);
        }

        $dados = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'status' => ['required', 'in:'.implode(',', array_keys(AttendanceStatus::replyOptions()))],
            'responded_by' => ['required', 'string', 'max:120'],
        ], [
            'company_id.required' => 'Selecione a empresa.',
            'responded_by.required' => 'Informe seu nome.',
        ]);

        $reuniao->attendances()->updateOrCreate(
            ['company_id' => $dados['company_id']],
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
