<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return view('portal.admin.users.index', [
            'users' => User::query()
                ->with('company:id,name')
                ->when($request->query('q'), fn ($query, $term) => $query->where(
                    fn ($inner) => $inner->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")
                ))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('portal.admin.users.form', ['user' => new User]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        User::create($data);

        return redirect()->route('admin.users.index')->with('status', 'Usuário criado com sucesso.');
    }

    public function edit(User $user)
    {
        return view('portal.admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('status', 'Usuário atualizado com sucesso.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Você não pode excluir o próprio usuário.']);
        }

        if (User::count() <= 1) {
            return back()->withErrors(['user' => 'O portal precisa de pelo menos um usuário administrador.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'Usuário removido.');
    }
}
