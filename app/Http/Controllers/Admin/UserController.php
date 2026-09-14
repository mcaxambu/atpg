<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminModule;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return view('portal.admin.users.index', [
            'users' => User::query()
                ->with(['company:id,name', 'member:id,name'])
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
        return $this->form(new User(['role' => UserRole::Admin]));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        User::create($data);

        return redirect()->route('admin.users.index')->with('status', 'Usuário criado com sucesso.');
    }

    public function edit(User $user)
    {
        return $this->form($user);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        // Sem esta trava um admin restrito abriria o proprio cadastro e se
        // promoveria a acesso total — o modulo "Usuários" ja e a chave do painel.
        if ($user->is($request->user()) && ! $request->user()->hasFullAccess()) {
            unset($data['role'], $data['abilities'], $data['company_id'], $data['member_id']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('status', 'Usuário atualizado com sucesso.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Você não pode excluir o próprio usuário.']);
        }

        if (User::admins()->count() <= 1 && $user->isAdmin()) {
            return back()->withErrors(['user' => 'O portal precisa de pelo menos um usuário administrador.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'Usuário removido.');
    }

    private function form(User $user)
    {
        return view('portal.admin.users.form', [
            'user' => $user,
            'roles' => UserRole::cases(),
            'modules' => AdminModule::all(),
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'members' => Member::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)->letters()->numbers()],
            'role' => ['required', Rule::enum(UserRole::class)],
            'full_access' => ['nullable', 'boolean'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => [Rule::enum(AdminModule::class)],
            'company_id' => ['nullable', 'exists:companies,id'],
            'member_id' => ['nullable', 'exists:members,id'],
        ], [], [
            'role' => 'perfil de acesso',
            'company_id' => 'empresa',
            'member_id' => 'cadastro do membro',
        ]);

        $role = UserRole::from($data['role']);

        // Cada papel guarda so o vinculo que faz sentido para ele; assim trocar
        // o papel de um usuario nao deixa um vinculo orfao para tras.
        $data['company_id'] = $role === UserRole::Company ? ($data['company_id'] ?? null) : null;
        $data['member_id'] = $role === UserRole::Member ? ($data['member_id'] ?? null) : null;

        if ($role === UserRole::Company && blank($data['company_id'])) {
            throw ValidationException::withMessages([
                'company_id' => 'Escolha a empresa que este usuário vai administrar.',
            ]);
        }

        if ($role === UserRole::Member && blank($data['member_id'])) {
            throw ValidationException::withMessages([
                'member_id' => 'Escolha o cadastro do diretório que este usuário vai editar.',
            ]);
        }

        // Nulo = acesso total. Só a diretoria tem módulos; os outros papéis já
        // são limitados pelo painel deles.
        if ($role !== UserRole::Admin) {
            $data['abilities'] = null;
        } elseif ($request->boolean('full_access')) {
            $data['abilities'] = null;
        } else {
            $modules = AdminModule::sanitize($data['abilities'] ?? []);

            if ($modules === []) {
                throw ValidationException::withMessages([
                    'abilities' => 'Marque pelo menos um módulo, ou escolha acesso total.',
                ]);
            }

            $data['abilities'] = $modules;
        }

        unset($data['full_access']);

        return $data;
    }
}
