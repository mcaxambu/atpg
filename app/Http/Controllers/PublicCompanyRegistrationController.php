<?php

namespace App\Http\Controllers;

use App\Actions\LinkProspectToRegistration;
use App\Actions\ModerateRegistration;
use App\Http\Requests\PublicCompanyRegistrationRequest;
use App\Models\Company;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class PublicCompanyRegistrationController extends Controller
{
    public function __construct(
        private readonly ModerateRegistration $moderation,
        private readonly LinkProspectToRegistration $linkProspect,
    ) {}

    public function create(Request $request)
    {
        return view('portal.register-company', [
            'siteSettings' => SiteSetting::allSettings(),
            // Token do convite: volta escondido no formulario para o cadastro
            // cair na ficha de quem vinha conversando (ver ProspectController).
            'convite' => $request->query('convite'),
        ]);
    }

    public function store(PublicCompanyRegistrationRequest $request)
    {
        $data = $request->companyData();
        $data['logo_path'] = $request->file('logo')->store('company-logos', 'public');

        $company = Company::create($data);

        ($this->linkProspect)($request->input('convite'), $company);

        $this->moderation->acknowledge($company);

        return redirect()
            ->route('companies.register.create')
            ->with('registration_success', true);
    }
}
