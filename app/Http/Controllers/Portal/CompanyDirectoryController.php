<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Support\Portal\PortalData;
use Illuminate\Http\Request;

class CompanyDirectoryController extends Controller
{
    public function index(Request $request, PortalData $portalData)
    {
        $companies = Company::query()
            ->withCount(['members' => fn ($query) => $query->visible()])
            ->visible()
            ->search($request->query('search'))
            ->when($request->query('segment'), fn ($query, $segment) => $query->where('segment', $segment))
            ->when($request->query('city'), fn ($query, $city) => $query->where('city', $city))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('portal.companies', $portalData->merge([
            'companies' => $companies,
            'segments' => $this->distinctValues('segment'),
            'cities' => $this->distinctValues('city'),
            'filters' => $request->only(['search', 'segment', 'city']),
            'totalCompanies' => $companies->total(),
            'totalMembers' => Company::visible()->withCount(['members' => fn ($query) => $query->visible()])->get()->sum('members_count'),
        ]));
    }

    public function show(Company $company, PortalData $portalData)
    {
        abort_unless($company->isVisible(), 404);

        $company->load(['members' => fn ($query) => $query->visible()->with('specialties')->orderBy('name')]);

        return view('portal.company-profile', $portalData->merge(['company' => $company]));
    }

    private function distinctValues(string $column)
    {
        return Company::query()
            ->visible()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column);
    }
}
