<?php

namespace App\Http\Controllers\Admin;

use App\Actions\NotifyCompanyAboutJob;
use App\Enums\ModerationStatus;
use App\Http\Controllers\Controller;
use App\Models\JobOpening;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Moderacao das vagas publicadas pelas empresas.
 *
 * A associacao decide se a vaga entra no portal, mas NAO enxerga as
 * candidaturas: os dados do candidato sao da empresa que abriu a vaga. Aqui
 * so aparece a contagem.
 */
class JobOpeningController extends Controller
{
    public function __construct(private readonly NotifyCompanyAboutJob $notify) {}

    public function index(Request $request): View
    {
        $jobs = JobOpening::query()
            ->with('company:id,name')
            ->withCount('applications')
            ->search($request->query('q'))
            ->when(
                $request->filled('status') && ModerationStatus::tryFrom($request->query('status')),
                fn ($query) => $query->where('status', $request->query('status'))
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('portal.admin.jobs.index', [
            'jobs' => $jobs,
            'statusCounts' => [
                'all' => JobOpening::count(),
                ModerationStatus::Pending->value => JobOpening::pending()->count(),
                ModerationStatus::Approved->value => JobOpening::approved()->count(),
                ModerationStatus::Rejected->value => JobOpening::rejected()->count(),
            ],
        ]);
    }

    public function pending(Request $request): View
    {
        return view('portal.admin.jobs.pending', [
            'jobs' => JobOpening::query()
                ->with('company:id,name')
                ->pending()
                ->search($request->query('q'))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function show(JobOpening $job): View
    {
        return view('portal.admin.jobs.show', [
            'job' => $job->load('company')->loadCount('applications'),
        ]);
    }

    public function approve(Request $request, JobOpening $job): RedirectResponse
    {
        $job->approve($request->user());
        $this->notify->approved($job);

        return back()->with('status', "\"{$job->title}\" aprovada e publicada no portal. A empresa foi avisada por e-mail.");
    }

    public function reject(Request $request, JobOpening $job): RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ], [], ['rejection_reason' => 'motivo']);

        $job->reject($data['rejection_reason'] ?? null, $request->user());
        $this->notify->rejected($job);

        return back()->with('status', "\"{$job->title}\" rejeitada. A empresa recebeu o motivo por e-mail.");
    }

    /**
     * Tira do ar sem desfazer a aprovacao — o mesmo que a empresa faz ao
     * encerrar, mas disponivel para a diretoria.
     */
    public function unpublish(JobOpening $job): RedirectResponse
    {
        $job->update(['is_active' => false]);

        return back()->with('status', "\"{$job->title}\" saiu do portal.");
    }

    public function publish(JobOpening $job): RedirectResponse
    {
        $job->update(['is_active' => true, 'status' => ModerationStatus::Approved]);

        return back()->with('status', "\"{$job->title}\" voltou ao portal.");
    }

    public function destroy(JobOpening $job): RedirectResponse
    {
        $job->delete();

        return redirect()->route('admin.jobs.index')
            ->with('status', "\"{$job->title}\" removida.");
    }
}
