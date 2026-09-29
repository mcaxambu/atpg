<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OnboardingReminderMail;
use App\Models\Company;
use App\Models\Member;
use App\Support\OnboardingProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Acompanhamento de quem acabou de entrar.
 *
 * Mostra o que falta para cada associado novo estar de fato dentro — e o que
 * falta e calculado do banco, nunca marcado a mao (ver OnboardingProgress).
 *
 * A tela lista primeiro quem esta ha mais tempo parado: o associado que entrou
 * ha dois meses e nunca abriu o painel e o que corre risco de desistir, nao o
 * que entrou ontem.
 */
class OnboardingController extends Controller
{
    /** Por quanto tempo um associado novo continua sendo "novo" nesta tela. */
    private const JANELA_EM_DIAS = 180;

    public function index(Request $request): View
    {
        $mostrarCompletos = $request->boolean('completos');

        $associados = collect()
            ->merge(Company::approved()->where('created_at', '>=', now()->subDays(self::JANELA_EM_DIAS))->get())
            ->merge(Member::approved()->where('created_at', '>=', now()->subDays(self::JANELA_EM_DIAS))->get())
            ->map(fn (Company|Member $a) => new OnboardingProgress($a))
            ->when(! $mostrarCompletos, fn ($lista) => $lista->reject(fn (OnboardingProgress $p) => $p->completo()))
            ->sortByDesc(fn (OnboardingProgress $p) => $p->diasDesdeAprovacao())
            ->values();

        return view('portal.admin.crm.onboarding', [
            'associados' => $associados,
            'mostrarCompletos' => $mostrarCompletos,
            'janela' => self::JANELA_EM_DIAS,
        ]);
    }

    /**
     * Lembrete para o associado, com o que falta.
     *
     * Fica no botao, e nao no automatico, de proposito: cobranca automatica
     * direto ao associado vira spam de quem acabou de entrar. O que sai sozinho
     * e o resumo semanal para a DIRETORIA (ver ResumoSemanalCommand).
     */
    public function remind(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'tipo' => ['required', 'in:company,member'],
            'id' => ['required', 'integer'],
        ]);

        $associado = $dados['tipo'] === 'company'
            ? Company::findOrFail($dados['id'])
            : Member::findOrFail($dados['id']);

        $progresso = new OnboardingProgress($associado);

        if ($progresso->completo()) {
            return back()->with('status', 'Nada pendente para lembrar.');
        }

        if (blank($associado->email)) {
            return back()->withErrors(['email' => "Sem e-mail no cadastro de \"{$associado->name}\"."]);
        }

        try {
            Mail::to($associado->email)->send(new OnboardingReminderMail($associado, $progresso));
        } catch (\Throwable $erro) {
            report($erro);

            return back()->withErrors(['lembrete' => 'O lembrete não pôde ser enviado. Confira a configuração de e-mail.']);
        }

        return back()->with('status', "Lembrete enviado para {$associado->email}.");
    }
}
