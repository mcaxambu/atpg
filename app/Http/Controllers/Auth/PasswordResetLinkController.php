<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('portal.auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        // Resposta identica para e-mail existente ou nao: revelar a diferenca
        // permitiria descobrir quem tem conta no painel.
        return back()->with('status', 'Se houver uma conta com esse e-mail, enviamos o link de redefinição de senha.');
    }
}
