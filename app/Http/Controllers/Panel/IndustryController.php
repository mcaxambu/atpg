<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Indústrias vistas por quem é associado.
 *
 * MESMA lista da diretoria, moldura diferente — e sem contato nem anotação.
 * A diretoria vê o telefone de quem decide; a associada vê que a indústria
 * existe e do que ela precisa. Algumas das associadas concorrem entre si, e
 * entregar a lista de contatos para as 27 transformaria a ferramenta da
 * associação na lista de prospecção particular de quem chegasse primeiro.
 */
class IndustryController extends Controller
{
    public function index(Request $request): View
    {
        $empresa = $request->user()->company;

        $industrias = Industry::query()
            ->ativas()
            ->search($request->query('q'))
            ->precisandoDe($request->integer('especialidade') ?: null)
            ->when($request->query('setor'), fn ($q, $setor) => $q->where('sector', $setor))
            // "Só as que eu atendo" é o filtro que responde a pergunta real de
            // quem entra aqui: onde eu me encaixo?
            ->when($request->boolean('minhas') && $empresa, function ($q) use ($empresa) {
                $faz = $empresa->specialties->pluck('id');

                return $q->whereHas('specialties', fn ($inner) => $inner->whereIn('specialties.id', $faz));
            })
            ->with('specialties')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('portal.panel.industrias.index', [
            'industrias' => $industrias,
            'especialidades' => Specialty::orderBy('name')->get(),
            'setores' => Industry::ativas()->whereNotNull('sector')->distinct()->orderBy('sector')->pluck('sector'),
            'empresa' => $empresa,
            'minhasEspecialidades' => $empresa?->specialties->pluck('id') ?? collect(),
            'layout' => $this->layout($request),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $industria = Industry::ativas()->where('slug', $slug)->firstOrFail();
        $empresa = $request->user()->company;

        return view('portal.panel.industrias.show', [
            'industria' => $industria->load('specialties'),
            'empresa' => $empresa,
            'minhasEspecialidades' => $empresa?->specialties->pluck('id') ?? collect(),
            // Quem mais atende aparece para a diretoria; para a associada, não.
            // Saber que outras três associadas também atendem não ajuda ela a
            // trabalhar, e azeda a convivência.
            'atendem' => $request->user()->isAdmin() ? $industria->quemAtende() : collect(),
            'layout' => $this->layout($request),
        ]);
    }

    private function layout(Request $request): string
    {
        $user = $request->user();

        return match (true) {
            $user->isAdmin() => 'layouts.admin',
            $user->isCompany() => 'layouts.company',
            default => 'layouts.membro',
        };
    }
}
