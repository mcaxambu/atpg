<?php

namespace App\Http\Controllers;

use App\Actions\ModerateRegistration;
use App\Http\Requests\PublicCompanyRegistrationRequest;
use App\Models\Company;
use App\Models\SiteSetting;

class PublicCompanyRegistrationController extends Controller
{
    public function __construct(private readonly ModerateRegistration $moderation) {}

    public function create()
    {
        return view('portal.register-company', [
            'siteSettings' => SiteSetting::allSettings(),
        ]);
    }

    public function store(PublicCompanyRegistrationRequest $request)
    {
        $data = $request->companyData();
        $data['logo_path'] = $request->file('logo')->store('company-logos', 'public');

        $company = Company::create($data);

        $this->moderation->acknowledge($company);

        return redirect()
            ->route('companies.register.create')
            ->with('registration_success', true);
    }
}
