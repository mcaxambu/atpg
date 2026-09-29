<?php

namespace App\Http\Controllers;

use App\Actions\LinkProspectToRegistration;
use App\Actions\ModerateRegistration;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Requests\PublicMemberRegistrationRequest;
use App\Models\Company;
use App\Models\Member;
use App\Models\Specialty;
use Illuminate\Http\Request;

class PublicMemberRegistrationController extends Controller
{
    public function __construct(
        private readonly ModerateRegistration $moderation,
        private readonly LinkProspectToRegistration $linkProspect,
    ) {}

    public function create(Request $request)
    {
        return view('portal.register-member', [
            'companies' => Company::approved()->orderBy('name')->get(),
            'specialties' => Specialty::orderBy('name')->get(),
            // Token do convite: volta escondido no formulario para o cadastro
            // cair na ficha de quem vinha conversando.
            'convite' => $request->query('convite'),
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

        ($this->linkProspect)($request->input('convite'), $member);

        $this->moderation->acknowledge($member);

        return redirect()
            ->route('members.create')
            ->with('registration_success', true);
    }
}
