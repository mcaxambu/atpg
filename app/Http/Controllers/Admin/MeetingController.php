<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateMinuteFromMeeting;
use App\Enums\AttendanceStatus;
use App\Enums\MeetingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MeetingRequest;
use App\Models\Company;
use App\Models\Meeting;
use App\Models\MeetingActionItem;
use App\Models\MeetingAgendaItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeetingController extends Controller
{
    public function index(Request $request): View
    {
        return view('portal.admin.reunioes.index', [
            'reunioes' => Meeting::query()
                ->withCount([
                    'attendances as confirmadas' => fn ($q) => $q->where('status', AttendanceStatus::Confirmado),
                    'actionItems as pendencias' => fn ($q) => $q->where('status', 'pendente'),
                ])
                ->search($request->query('q'))
                ->when(
                    $request->filled('status') && MeetingStatus::tryFrom($request->query('status')),
                    fn ($q) => $q->where('status', $request->query('status'))
                )
                ->orderByDesc('scheduled_at')
                ->paginate(20)
                ->withQueryString(),
            'total' => Meeting::count(),
            'proximas' => Meeting::upcoming()->count(),
            'vencidos' => MeetingActionItem::overdue()->count(),
        ]);
    }

    public function create(): View
    {
        return view('portal.admin.reunioes.form', [
            'reuniao' => new Meeting([
                'status' => MeetingStatus::Agendada,
                'scheduled_at' => now()->addWeek()->setTime(19, 0),
            ]),
        ]);
    }

    public function store(MeetingRequest $request): RedirectResponse
    {
        $data = $request->meetingData();
        $data['created_by'] = $request->user()->id;

        $reuniao = Meeting::create($data);
        $this->syncAgenda($reuniao, $request->agendaItems());
        $this->syncAttendanceList($reuniao);

        return redirect()->route('admin.reunioes.show', $reuniao)
            ->with('status', 'Reunião criada. Copie o link de convocação e envie no WhatsApp.');
    }

    public function show(Meeting $reuniao): View
    {
        $reuniao->load([
            'agendaItems.actionItems',
            'attendances.company:id,name,slug',
            'actionItems.agendaItem:id,title',
            'minute',
        ]);

        // Empresas aprovadas que ainda nao estao na lista: entraram depois da
        // reuniao ser criada.
        $ausentesDaLista = Company::visible()
            ->whereNotIn('id', $reuniao->attendances->pluck('company_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('portal.admin.reunioes.show', compact('reuniao', 'ausentesDaLista'));
    }

    public function edit(Meeting $reuniao): View
    {
        $reuniao->load('agendaItems');

        return view('portal.admin.reunioes.form', compact('reuniao'));
    }

    public function update(MeetingRequest $request, Meeting $reuniao): RedirectResponse
    {
        $reuniao->update($request->meetingData());
        $this->syncAgenda($reuniao, $request->agendaItems());

        return redirect()->route('admin.reunioes.show', $reuniao)
            ->with('status', 'Reunião atualizada.');
    }

    public function destroy(Meeting $reuniao): RedirectResponse
    {
        $reuniao->delete();

        return redirect()->route('admin.reunioes.index')
            ->with('status', "Reunião \"{$reuniao->title}\" removida.");
    }

    /**
     * Recria a lista de convocados a partir das empresas publicadas,
     * preservando quem ja respondeu.
     */
    public function refreshAttendanceList(Meeting $reuniao): RedirectResponse
    {
        $novas = $this->syncAttendanceList($reuniao);

        return back()->with('status', $novas === 0
            ? 'A lista já está completa.'
            : ($novas === 1 ? '1 empresa adicionada à lista.' : "{$novas} empresas adicionadas à lista."));
    }

    /**
     * Presenca real no dia, marcada pela associacao.
     */
    public function saveAttendance(Request $request, Meeting $reuniao): RedirectResponse
    {
        $presentes = collect($request->input('presentes', []))->map(fn ($id) => (int) $id);

        $reuniao->attendances()->each(function ($presenca) use ($presentes) {
            $presenca->update(['attended' => $presentes->contains($presenca->company_id)]);
        });

        $reuniao->update(['status' => MeetingStatus::Realizada]);

        return back()->with('status', 'Presença registrada e reunião marcada como realizada.');
    }

    /**
     * Desfecho de um item de pauta, com votos quando houver.
     */
    public function saveOutcome(Request $request, Meeting $reuniao, MeetingAgendaItem $item): RedirectResponse
    {
        abort_unless($item->meeting_id === $reuniao->id, 404);

        $dados = $request->validate([
            'outcome' => ['nullable', 'in:'.implode(',', array_keys(MeetingAgendaItem::OUTCOMES))],
            'outcome_notes' => ['nullable', 'string', 'max:3000'],
            'votes_for' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'votes_against' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'votes_abstain' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $item->update($dados);

        return back()->with('status', "Resultado registrado para \"{$item->title}\".");
    }

    public function storeActionItem(Request $request, Meeting $reuniao): RedirectResponse
    {
        $dados = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'responsible' => ['nullable', 'string', 'max:150'],
            'due_date' => ['nullable', 'date'],
            'meeting_agenda_item_id' => ['nullable', 'integer', 'exists:meeting_agenda_items,id'],
        ]);

        $reuniao->actionItems()->create($dados + ['status' => 'pendente']);

        return back()->with('status', 'Encaminhamento registrado.');
    }

    public function updateActionItem(Request $request, MeetingActionItem $encaminhamento): RedirectResponse
    {
        $dados = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(MeetingActionItem::STATUSES))],
        ]);

        $encaminhamento->update([
            'status' => $dados['status'],
            'completed_at' => $dados['status'] === 'concluido' ? now() : null,
        ]);

        return back()->with('status', 'Encaminhamento atualizado.');
    }

    public function destroyActionItem(MeetingActionItem $encaminhamento): RedirectResponse
    {
        $encaminhamento->delete();

        return back()->with('status', 'Encaminhamento removido.');
    }

    public function generateMinute(Meeting $reuniao, GenerateMinuteFromMeeting $gerar): RedirectResponse
    {
        if ($reuniao->minute) {
            return back()->withErrors(['ata' => 'Esta reunião já tem uma ata gerada.']);
        }

        $ata = $gerar($reuniao, request()->user());

        return redirect()->route('admin.atas.edit', $ata)
            ->with('status', 'Ata gerada a partir da reunião. Revise o texto antes de publicar.');
    }

    /**
     * Cria, atualiza e remove itens de pauta conforme o formulario, mantendo
     * os ids: recriar tudo apagaria os resultados ja registrados.
     *
     * @param  array<int, array<string, mixed>>  $itens
     */
    private function syncAgenda(Meeting $reuniao, array $itens): void
    {
        $mantidos = [];

        foreach ($itens as $dados) {
            $id = $dados['id'];
            unset($dados['id']);

            if ($id && $existente = $reuniao->agendaItems()->find($id)) {
                $existente->update($dados);
                $mantidos[] = $existente->id;

                continue;
            }

            $mantidos[] = $reuniao->agendaItems()->create($dados)->id;
        }

        $reuniao->agendaItems()->whereNotIn('id', $mantidos ?: [0])->delete();
    }

    /**
     * Convoca todas as empresas publicadas que ainda nao estao na lista.
     */
    private function syncAttendanceList(Meeting $reuniao): int
    {
        $jaNaLista = $reuniao->attendances()->pluck('company_id');

        $novas = Company::visible()->whereNotIn('id', $jaNaLista)->pluck('id');

        foreach ($novas as $companyId) {
            $reuniao->attendances()->create([
                'company_id' => $companyId,
                'status' => AttendanceStatus::Pendente,
            ]);
        }

        return $novas->count();
    }
}
