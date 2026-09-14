<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobOpening;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Candidaturas recebidas por uma vaga da empresa.
 *
 * Toda consulta parte da vaga ja escopada pela empresa do usuario. O curriculo
 * fica no disco privado e sai apenas por `download()` — nao ha URL publica
 * para o arquivo, nem para quem descobrir o caminho.
 */
class JobApplicationController extends Controller
{
    public function index(Request $request, int $job): View
    {
        $opening = $this->job($request, $job);

        return view('portal.company.jobs.applications', [
            'job' => $opening,
            'applications' => $opening->applications()->latest()->paginate(30),
        ]);
    }

    public function show(Request $request, int $job, int $application): View
    {
        $opening = $this->job($request, $job);
        $model = $opening->applications()->findOrFail($application);

        $model->markAsViewed();

        return view('portal.company.jobs.application', [
            'job' => $opening,
            'application' => $model,
        ]);
    }

    public function download(Request $request, int $job, int $application): StreamedResponse
    {
        $opening = $this->job($request, $job);
        $model = $opening->applications()->findOrFail($application);

        abort_unless($model->hasResume(), 404, 'Esta candidatura não tem currículo anexado.');

        $model->markAsViewed();

        // Nome amigavel na hora de baixar, preservando a extensao original.
        $extensao = pathinfo($model->resume_path, PATHINFO_EXTENSION);
        $nome = str($model->name)->slug()->value()."-curriculo.{$extensao}";

        return Storage::disk(JobApplication::DISK)->download($model->resume_path, $nome);
    }

    public function destroy(Request $request, int $job, int $application): RedirectResponse
    {
        $opening = $this->job($request, $job);
        $model = $opening->applications()->findOrFail($application);

        // O evento deleting do model apaga o curriculo do disco junto.
        $model->delete();

        return redirect()->route('empresa.vagas.candidaturas', $opening->id)
            ->with('status', 'Candidatura removida e currículo apagado.');
    }

    private function job(Request $request, int $job): JobOpening
    {
        return JobOpening::query()
            ->where('company_id', $request->user()->company_id)
            ->findOrFail($job);
    }
}
