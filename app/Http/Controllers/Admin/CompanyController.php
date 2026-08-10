<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ModerateRegistration;
use App\Actions\ProvisionCompanyAccess;
use App\Enums\ModerationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompanyRequest;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function __construct(private readonly ModerateRegistration $moderation) {}

    public function index(Request $request)
    {
        $companies = Company::query()
            ->withCount('members')
            ->search($request->query('q'))
            ->when(
                $request->filled('status') && ModerationStatus::tryFrom($request->query('status')),
                fn ($query) => $query->where('status', $request->query('status'))
            )
            ->when($request->query('segment'), fn ($query, $segment) => $query->where('segment', $segment))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('portal.admin.companies.index', [
            'companies' => $companies,
            'segments' => $this->segments(),
            'statusCounts' => $this->statusCounts(),
        ]);
    }

    public function pending(Request $request)
    {
        return view('portal.admin.companies.pending', [
            'companies' => Company::query()
                ->withCount('members')
                ->pending()
                ->search($request->query('q'))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function trash()
    {
        return view('portal.admin.companies.trash', [
            'companies' => Company::onlyTrashed()->latest('deleted_at')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('portal.admin.companies.form', [
            'company' => new Company(['status' => ModerationStatus::Approved, 'is_active' => true]),
        ]);
    }

    public function store(CompanyRequest $request)
    {
        $data = $request->companyData();
        $data['registration_source'] = 'admin';

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        $company = Company::create($data);

        return redirect()
            ->route('admin.companies.index')
            ->with('status', "Empresa \"{$company->name}\" cadastrada com sucesso.");
    }

    public function show(Company $company)
    {
        $company->loadCount('members')->load(['reviewer', 'accessUser']);

        return view('portal.admin.companies.show', compact('company'));
    }

    /**
     * Envia (ou reenvia) o convite de acesso ao painel da empresa.
     */
    public function invite(Request $request, Company $company, ProvisionCompanyAccess $provision)
    {
        if (! $company->isVisible()) {
            return back()->withErrors([
                'invite' => 'Aprove e publique a empresa antes de conceder acesso ao painel.',
            ]);
        }

        $result = $provision($company, force: $request->boolean('force'));

        return match ($result['status']) {
            ProvisionCompanyAccess::NO_EMAIL => back()->withErrors([
                'invite' => 'A empresa não tem e-mail cadastrado. Preencha o contato antes de enviar o convite.',
            ]),
            ProvisionCompanyAccess::EMAIL_TAKEN => back()->withErrors([
                'invite' => "O e-mail {$company->email} já pertence a outro usuário do sistema. Use um e-mail diferente para o responsável desta empresa.",
            ]),
            ProvisionCompanyAccess::ALREADY_ACTIVE => back()->with(
                'status',
                "{$company->email} já tem acesso ativo ao painel. Se precisar, use \"Reenviar mesmo assim\"."
            ),
            ProvisionCompanyAccess::RESENT => back()->with(
                'status',
                "Convite reenviado para {$company->email}."
            ),
            default => back()->with(
                'status',
                "Convite enviado para {$company->email}. O responsável define a senha pelo link do e-mail."
            ),
        };
    }

    public function edit(Company $company)
    {
        return view('portal.admin.companies.form', compact('company'));
    }

    public function update(CompanyRequest $request, Company $company)
    {
        $data = $request->companyData($company);

        if ($request->hasFile('logo')) {
            $this->deleteLogo($company);
            $data['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        $company->update($data);

        return redirect()
            ->route('admin.companies.index')
            ->with('status', "Empresa \"{$company->name}\" atualizada com sucesso.");
    }

    public function destroy(Company $company)
    {
        // Exclusao logica: a logo so e apagada quando o registro sai da lixeira.
        $company->delete();

        return redirect()
            ->route('admin.companies.index')
            ->with('status', "Empresa \"{$company->name}\" movida para a lixeira.");
    }

    public function restore(int $company)
    {
        $model = Company::onlyTrashed()->findOrFail($company);
        $model->restore();

        return redirect()
            ->route('admin.companies.trash')
            ->with('status', "Empresa \"{$model->name}\" restaurada.");
    }

    public function forceDelete(int $company)
    {
        $model = Company::onlyTrashed()->findOrFail($company);
        $this->deleteLogo($model);
        $model->forceDelete();

        return redirect()
            ->route('admin.companies.trash')
            ->with('status', "Empresa \"{$model->name}\" excluída definitivamente.");
    }

    public function approve(Company $company)
    {
        $this->moderation->approve($company, request()->user());

        return back()->with('status', "Empresa \"{$company->name}\" aprovada e publicada no portal.");
    }

    public function reject(Request $request, Company $company)
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $this->moderation->reject($company, $validated['rejection_reason'], $request->user());

        return back()->with('status', "Empresa \"{$company->name}\" rejeitada. O responsável foi avisado por e-mail.");
    }

    private function deleteLogo(Company $company): void
    {
        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
        }
    }

    /**
     * @return array<int, string>
     */
    private function segments(): array
    {
        return Company::query()
            ->whereNotNull('segment')
            ->where('segment', '!=', '')
            ->distinct()
            ->orderBy('segment')
            ->pluck('segment')
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function statusCounts(): array
    {
        return Company::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }
}
