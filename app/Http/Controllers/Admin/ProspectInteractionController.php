<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InteractionType;
use App\Http\Controllers\Controller;
use App\Models\Prospect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Registro de contato com o prospecto.
 *
 * O formulario grava o contato E o proximo passo de uma vez. Sao duas coisas
 * que sempre acontecem juntas — "liguei, retorno na terca" —, e separa-las em
 * duas telas faz a segunda metade nunca ser preenchida.
 */
class ProspectInteractionController extends Controller
{
    public function store(Request $request, Prospect $prospect): RedirectResponse
    {
        $dados = $request->validate([
            'type' => ['required', Rule::in(array_column(InteractionType::manuais(), 'value'))],
            'summary' => ['required', 'string', 'max:2000'],
            'happened_at' => ['nullable', 'date', 'before_or_equal:now'],
            'next_action' => ['nullable', 'string', 'max:255'],
            'next_action_at' => ['nullable', 'date'],
        ], [
            'summary.required' => 'Escreva o que foi conversado.',
            'happened_at.before_or_equal' => 'O contato não pode estar no futuro.',
        ]);

        $prospect->interactions()->create([
            'user_id' => $request->user()->id,
            'type' => $dados['type'],
            'summary' => $dados['summary'],
            'happened_at' => $dados['happened_at'] ?? now(),
        ]);

        // O proximo passo so e sobrescrito quando a pessoa escreve um novo:
        // registrar um contato antigo nao pode apagar o combinado que vale.
        if (filled($dados['next_action'] ?? null) || filled($dados['next_action_at'] ?? null)) {
            $prospect->update([
                'next_action' => $dados['next_action'] ?? $prospect->next_action,
                'next_action_at' => $dados['next_action_at'] ?? $prospect->next_action_at,
            ]);
        }

        return back()->with('status', 'Contato registrado.');
    }
}
