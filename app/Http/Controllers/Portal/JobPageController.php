<?php

namespace App\Http\Controllers\Portal;

use App\Actions\NotifyCompanyAboutJob;
use App\Enums\JobType;
use App\Enums\JobWorkplace;
use App\Http\Controllers\Controller;
use App\Http\Requests\JobApplicationRequest;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Support\Portal\PortalData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobPageController extends Controller
{
    public function __construct(private readonly NotifyCompanyAboutJob $notify) {}

    public function index(Request $request, PortalData $portalData): View
    {
        $jobs = JobOpening::query()
            ->with('company:id,name,slug,logo_path,initials')
            ->publiclyVisible()
            ->search($request->query('q'))
            ->when(
                $request->filled('tipo') && JobType::tryFrom($request->query('tipo')),
                fn ($query) => $query->where('type', $request->query('tipo'))
            )
            ->when(
                $request->filled('modelo') && JobWorkplace::tryFrom($request->query('modelo')),
                fn ($query) => $query->where('workplace', $request->query('modelo'))
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('portal.jobs', $portalData->merge([
            'jobs' => $jobs,
            'types' => JobType::cases(),
            'workplaces' => JobWorkplace::cases(),
        ]));
    }

    public function show(JobOpening $job, PortalData $portalData): View
    {
        abort_unless($job->isPubliclyVisible(), 404);

        return view('portal.jobs-show', $portalData->merge([
            'job' => $job->load('company'),
        ]));
    }

    /**
     * Recebe a candidatura. O curriculo vai para o disco privado; o retorno e
     * sempre a mesma tela de sucesso, sem expor nada sobre a vaga.
     */
    public function apply(JobApplicationRequest $request, JobOpening $job): RedirectResponse
    {
        abort_unless($job->isPubliclyVisible(), 404);

        $data = $request->applicationData();
        $data['job_opening_id'] = $job->id;

        if ($request->hasFile('resume')) {
            $data['resume_path'] = $request->file('resume')
                ->store("job-applications/{$job->id}", JobApplication::DISK);
        }

        $application = JobApplication::create($data);

        $this->notify->applicationReceived($application);

        return redirect()
            ->route('vagas.show', $job)
            ->with('application_sent', true);
    }
}
