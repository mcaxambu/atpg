<?php

namespace App\Http\Controllers\Company;

use App\Enums\JobType;
use App\Enums\JobWorkplace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CompanyJobRequest;
use App\Models\JobOpening;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vagas sob responsabilidade da empresa.
 *
 * Como no controller de colaboradores, tudo parte de `scopedQuery()`: nao ha
 * caminho para alcancar a vaga de outra empresa trocando o id na URL.
 */
class JobOpeningController extends Controller
{
    public function index(Request $request): View
    {
        return view('portal.company.jobs.index', [
            'jobs' => $this->scopedQuery($request)
                ->withCount('applications')
                ->search($request->query('q'))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new JobOpening);
    }

    public function store(CompanyJobRequest $request): RedirectResponse
    {
        $data = $request->jobData();
        $data['company_id'] = $request->user()->company_id;

        $job = JobOpening::create($data);

        return redirect()->route('empresa.vagas.index')
            ->with('status', "\"{$job->title}\" foi enviada para análise da associação.");
    }

    public function edit(Request $request, int $job): View
    {
        return $this->form($this->scopedQuery($request)->findOrFail($job));
    }

    public function update(CompanyJobRequest $request, int $job): RedirectResponse
    {
        $model = $this->scopedQuery($request)->findOrFail($job);
        $model->update($request->jobData($model));

        return redirect()->route('empresa.vagas.index')
            ->with('status', "\"{$model->title}\" atualizada e enviada para nova análise.");
    }

    /**
     * Encerrar tira do ar sem apagar: as candidaturas recebidas continuam
     * acessiveis para a empresa.
     */
    public function close(Request $request, int $job): RedirectResponse
    {
        $model = $this->scopedQuery($request)->findOrFail($job);
        $model->update(['is_active' => false]);

        return back()->with('status', "\"{$model->title}\" foi encerrada e saiu do portal.");
    }

    public function destroy(Request $request, int $job): RedirectResponse
    {
        $model = $this->scopedQuery($request)->findOrFail($job);
        $model->delete();

        return redirect()->route('empresa.vagas.index')
            ->with('status', "\"{$model->title}\" removida.");
    }

    private function scopedQuery(Request $request)
    {
        return JobOpening::query()->where('company_id', $request->user()->company_id);
    }

    private function form(JobOpening $job): View
    {
        return view('portal.company.jobs.form', [
            'job' => $job,
            'types' => JobType::cases(),
            'workplaces' => JobWorkplace::cases(),
        ]);
    }
}
