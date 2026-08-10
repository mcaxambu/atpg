<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Member;
use App\Support\Portal\PortalData;
use Illuminate\Http\Request;

class MemberDirectoryController extends Controller
{
    public function index(Request $request, PortalData $portalData)
    {
        $members = Member::query()
            ->with(['company:id,name,slug', 'specialties:id,name,slug'])
            ->publiclyVisible()
            ->search($request->query('search'))
            ->when($request->query('specialty'), fn ($query, $specialty) => $query->whereHas(
                'specialties',
                fn ($inner) => $inner->where('slug', $specialty)
            ))
            ->when($request->query('company'), fn ($query, $company) => $query->whereHas(
                'company',
                fn ($inner) => $inner->where('slug', $company)
            ))
            ->when($request->query('experience'), fn ($query, $years) => $query->where('experience_years', '>=', (int) $years))
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('portal.members', $portalData->merge([
            'members' => $members,
            'companies' => Company::visible()->orderBy('name')->get(['id', 'name', 'slug']),
            'filters' => $request->only(['search', 'specialty', 'company', 'experience']),
        ]));
    }

    public function show(Member $member, PortalData $portalData)
    {
        $member->load(['company', 'specialties', 'experiences', 'projects', 'certifications']);

        abort_unless($member->isPubliclyVisible(), 404);

        return view('portal.member-profile', $portalData->merge(['member' => $member]));
    }
}
