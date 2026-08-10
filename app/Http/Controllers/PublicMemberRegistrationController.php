<?php

namespace App\Http\Controllers;

use App\Actions\ModerateRegistration;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Requests\PublicMemberRegistrationRequest;
use App\Models\Company;
use App\Models\Member;
use App\Models\Specialty;

class PublicMemberRegistrationController extends Controller
{
    public function __construct(private readonly ModerateRegistration $moderation) {}

    public function create()
    {
        return view('portal.register-member', [
            'companies' => Company::approved()->orderBy('name')->get(),
            'specialties' => Specialty::orderBy('name')->get(),
        ]);
    }

    public function store(PublicMemberRegistrationRequest $request)
    {
        $data = $request->memberData();

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('member-photos', 'public');
        }

        $member = Member::create($data);
        $member->specialties()->sync($request->input('specialties', []));

        foreach (['experiences', 'projects', 'certifications'] as $relation) {
            foreach (MemberController::lines($request->input($relation)) as $position => $title) {
                $member->{$relation}()->create(['title' => $title, 'position' => $position]);
            }
        }

        $this->moderation->acknowledge($member);

        return redirect()
            ->route('members.create')
            ->with('registration_success', true);
    }
}
