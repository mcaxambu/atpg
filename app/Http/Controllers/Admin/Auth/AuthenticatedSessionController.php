<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Support\PanelRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /** Tentativas erradas aceitas antes da trava, por conta e IP. */
    private const TENTATIVAS = 5;

    private const BLOQUEIO_SEGUNDOS = 900;

    public function create(): View
    {
        return view('portal.admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /*
         * Trava por CONTA, e nao so por IP.
         *
         * O limite da rota é por IP: segura a varredura de um endereço só, mas
         * não a tentativa espalhada por muitos contra a mesma conta — que é
         * justamente como se ataca um e-mail conhecido, e os e-mails daqui
         * estão publicados no diretório de empresas.
         */
        $chave = $this->chaveDeTentativas($request);

        if (RateLimiter::tooManyAttempts($chave, self::TENTATIVAS)) {
            $segundos = RateLimiter::availableIn($chave);

            Log::warning('Login bloqueado por excesso de tentativas.', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
            ]);

            return back()
                ->withErrors(['email' => 'Muitas tentativas. Tente de novo em '.ceil($segundos / 60).' minuto(s).'])
                ->onlyInput('email');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($chave, self::BLOQUEIO_SEGUNDOS);

            // Registrado para que a diretoria consiga ver um ataque
            // acontecendo; sem isto, tentativa errada não deixa rastro nenhum.
            Log::notice('Tentativa de acesso recusada.', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
            ]);

            return back()
                ->withErrors(['email' => 'E-mail ou senha informados não conferem.'])
                ->onlyInput('email');
        }

        RateLimiter::clear($chave);

        $request->session()->regenerate();

        // Cada papel entra no painel dele; "intended" so vale se o destino
        // pretendido pertencer a esse painel.
        return redirect()->intended(PanelRedirect::homeFor($request->user()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('entrar');
    }

    /**
     * A conta e o IP juntos: travar só pela conta deixaria qualquer um
     * trancar o acesso de um associado de fora, só errando a senha dele.
     */
    private function chaveDeTentativas(Request $request): string
    {
        return 'login:'.Str::lower((string) $request->input('email')).'|'.$request->ip();
    }
}
