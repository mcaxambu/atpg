<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SpecialtyController extends Controller
{
    public function index(Request $request)
    {
        return view('portal.admin.specialties.index', [
            'specialties' => Specialty::query()
                ->withCount('members')
                ->search($request->query('q'))
                ->orderBy('name')
                ->paginate(30)
                ->withQueryString(),
            'total' => Specialty::count(),
        ]);
    }

    public function create()
    {
        return view('portal.admin.specialties.form', ['specialty' => new Specialty]);
    }

    public function store(Request $request)
    {
        $specialty = Specialty::create($this->validatedData($request));

        return redirect()->route('admin.specialties.index')
            ->with('status', "Especialidade \"{$specialty->name}\" cadastrada com sucesso.");
    }

    public function edit(Specialty $specialty)
    {
        $specialty->loadCount('members');

        return view('portal.admin.specialties.form', compact('specialty'));
    }

    public function update(Request $request, Specialty $specialty)
    {
        $specialty->update($this->validatedData($request, $specialty));

        return redirect()->route('admin.specialties.index')
            ->with('status', "Especialidade \"{$specialty->name}\" atualizada com sucesso.");
    }

    public function destroy(Specialty $specialty)
    {
        // Excluir uma especialidade em uso apagaria o vínculo silenciosamente
        // dos perfis, sem deixar rastro para o operador.
        $inUse = $specialty->members()->count();

        if ($inUse > 0) {
            return back()->withErrors([
                'specialty' => "\"{$specialty->name}\" está vinculada a {$inUse} "
                    .($inUse === 1 ? 'membro' : 'membros').'. Remova o vínculo nos perfis antes de excluir.',
            ]);
        }

        $specialty->delete();

        return redirect()->route('admin.specialties.index')
            ->with('status', "Especialidade \"{$specialty->name}\" removida com sucesso.");
    }

    private function validatedData(Request $request, ?Specialty $specialty = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('specialties', 'name')->ignore($specialty),
            ],
        ], [
            'name.unique' => 'Já existe uma especialidade com esse nome.',
        ]);

        $data['slug'] = Specialty::uniqueSlug($data['name'], $specialty?->id);

        return $data;
    }
}
