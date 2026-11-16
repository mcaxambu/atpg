<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Industry;
use App\Models\Specialty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cadastro das indústrias da região.
 *
 * O que justifica a tela: o cruzamento. Sem necessidade marcada na indústria
 * e especialidade marcada na associada, isto aqui seria mais uma agenda de
 * contatos — e para isso já existe caderno.
 */
class IndustryController extends Controller
{
    public function index(Request $request): View
    {
        $industrias = Industry::query()
            ->search($request->query('q'))
            ->precisandoDe($request->integer('especialidade') ?: null)
            ->when($request->query('setor'), fn ($q, $setor) => $q->where('sector', $setor))
            ->withCount('specialties')
            ->with('specialties')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('portal.admin.industrias.index', [
            'industrias' => $industrias,
            'especialidades' => Specialty::orderBy('name')->get(),
            'setores' => Industry::query()->whereNotNull('sector')->distinct()->orderBy('sector')->pluck('sector'),
            // Enquanto ninguém marcar o que as associadas fazem, o cruzamento
            // devolve vazio e a tela parece quebrada. Melhor dizer na cara.
            'semEspecialidade' => Company::visible()->doesntHave('specialties')->count(),
            'total' => Industry::count(),
        ]);
    }

    public function create(): View
    {
        return view('portal.admin.industrias.form', [
            'industria' => new Industry,
            'especialidades' => Specialty::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $this->validados($request);

        $industria = Industry::create($dados + ['created_by' => $request->user()->id]);
        $industria->specialties()->sync($request->input('especialidades', []));

        return redirect()->route('admin.industrias.show', $industria)
            ->with('status', 'Indústria cadastrada.');
    }

    public function show(Industry $industria): View
    {
        $industria->load('specialties');

        return view('portal.admin.industrias.show', [
            'industria' => $industria,
            'atendem' => $industria->quemAtende(),
        ]);
    }

    public function edit(Industry $industria): View
    {
        return view('portal.admin.industrias.form', [
            'industria' => $industria->load('specialties'),
            'especialidades' => Specialty::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Industry $industria): RedirectResponse
    {
        $industria->update($this->validados($request, $industria));
        $industria->specialties()->sync($request->input('especialidades', []));

        return redirect()->route('admin.industrias.show', $industria)
            ->with('status', 'Indústria atualizada.');
    }

    public function destroy(Industry $industria): RedirectResponse
    {
        $industria->delete();

        return redirect()->route('admin.industrias.index')
            ->with('status', "Indústria \"{$industria->name}\" removida.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validados(Request $request, ?Industry $industria = null): array
    {
        $dados = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:20'],
            'sector' => ['nullable', 'string', 'max:120'],
            'size' => ['nullable', 'in:'.implode(',', array_keys(Industry::PORTES))],
            'employees' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'size:2'],
            'neighborhood' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'site_url' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_role' => ['nullable', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'last_contact_at' => ['nullable', 'date'],
            'especialidades' => ['nullable', 'array'],
            'especialidades.*' => ['integer', 'exists:specialties,id'],
        ]);

        unset($dados['especialidades']);

        $dados['is_active'] = $request->boolean('is_active', true);
        $dados['slug'] = Industry::uniqueSlug($dados['name'], $industria?->id);

        return $dados;
    }
}
