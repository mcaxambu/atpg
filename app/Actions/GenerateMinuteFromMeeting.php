<?php

namespace App\Actions;

use App\Models\Meeting;
use App\Models\MeetingMinute;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Monta a ata a partir do que foi registrado na reuniao.
 *
 * Entrega um rascunho: a ata nasce despublicada, para a associacao revisar o
 * texto antes de liberar para as empresas.
 *
 * A montagem continua em Markdown e a conversao para HTML acontece num unico
 * ponto, no fim. O painel grava HTML desde 17/09/2026, e montar as tags na mao
 * aqui exigiria reescrever (e retestar) toda a estrutura — inclusive a tabela
 * de encaminhamentos — sem ganho nenhum.
 */
class GenerateMinuteFromMeeting
{
    public function __invoke(Meeting $reuniao, ?User $autor = null): MeetingMinute
    {
        $reuniao->loadMissing(['agendaItems.actionItems', 'attendances.company', 'actionItems']);

        $ata = MeetingMinute::create([
            'title' => $reuniao->title,
            'meeting_date' => $reuniao->scheduled_at->toDateString(),
            'summary' => $reuniao->type->label().' — '.$reuniao->scheduled_at->translatedFormat('d/m/Y'),
            'body' => $this->html($reuniao),
            'is_published' => false,
            'uploaded_by' => $autor?->id,
        ]);

        $reuniao->update(['meeting_minute_id' => $ata->id]);

        return $ata;
    }

    /**
     * O mesmo conversor que a ata antiga usa na exibicao, para a ata gerada
     * hoje sair com a mesma diagramacao das que ja estao publicadas.
     */
    private function html(Meeting $reuniao): string
    {
        return Str::markdown($this->markdown($reuniao), [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);
    }

    private function markdown(Meeting $reuniao): string
    {
        $linhas = [];

        $linhas[] = '**Tipo:** '.$reuniao->type->label();
        $linhas[] = '**Data:** '.$reuniao->scheduled_at->translatedFormat('d \d\e F \d\e Y').', '.$reuniao->periodLabel();

        if ($reuniao->location) {
            $linhas[] = '**Local:** '.$reuniao->location;
        }

        $linhas[] = '';
        $linhas[] = $this->presenca($reuniao);
        $linhas[] = '';
        $linhas[] = $this->pauta($reuniao);

        $encaminhamentos = $this->encaminhamentos($reuniao);

        if ($encaminhamentos !== '') {
            $linhas[] = '';
            $linhas[] = $encaminhamentos;
        }

        return implode("\n", array_filter($linhas, fn ($l) => $l !== null));
    }

    private function presenca(Meeting $reuniao): string
    {
        $presentes = $reuniao->attendances->where('attended', true)
            ->map(fn ($p) => $p->company?->name)
            ->filter()
            ->sort()
            ->values();

        $texto = "## Presença\n\n";

        if ($presentes->isEmpty()) {
            return $texto.'_Nenhuma presença registrada._';
        }

        $texto .= "Empresas presentes ({$presentes->count()}):\n\n";
        $texto .= $presentes->map(fn ($nome) => "- {$nome}")->implode("\n");

        if ($reuniao->quorum_minimum) {
            $atingido = $presentes->count() >= $reuniao->quorum_minimum;
            $texto .= "\n\n**Quórum mínimo:** {$reuniao->quorum_minimum} — "
                .($atingido ? 'atingido.' : 'não atingido.');
        }

        return $texto;
    }

    private function pauta(Meeting $reuniao): string
    {
        if ($reuniao->agendaItems->isEmpty()) {
            return "## Pauta\n\n_Sem itens de pauta registrados._";
        }

        $texto = "## Pauta e deliberações\n";

        foreach ($reuniao->agendaItems as $indice => $item) {
            $numero = $indice + 1;
            $texto .= "\n### {$numero}. {$item->title}\n";

            if ($item->presenter) {
                $texto .= "\n_Apresentado por {$item->presenter}._\n";
            }

            if ($item->description) {
                $texto .= "\n{$item->description}\n";
            }

            if ($item->outcome) {
                $texto .= "\n**Deliberação:** {$item->outcomeLabel()}";

                if ($item->hasVotes()) {
                    $texto .= ' ('.$item->votesSummary().')';
                }

                $texto .= "\n";
            }

            if ($item->outcome_notes) {
                $texto .= "\n{$item->outcome_notes}\n";
            }
        }

        return $texto;
    }

    private function encaminhamentos(Meeting $reuniao): string
    {
        if ($reuniao->actionItems->isEmpty()) {
            return '';
        }

        $texto = "## Encaminhamentos\n\n";
        $texto .= "| Ação | Responsável | Prazo |\n| --- | --- | --- |\n";

        foreach ($reuniao->actionItems as $item) {
            $texto .= sprintf(
                "| %s | %s | %s |\n",
                str_replace('|', '\|', $item->title),
                $item->responsible ?: '—',
                $item->due_date?->format('d/m/Y') ?: '—'
            );
        }

        return $texto;
    }
}
