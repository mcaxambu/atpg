<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CompanyProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('portal.company.profile', ['company' => $request->user()->company]);
    }

    public function update(CompanyProfileRequest $request): RedirectResponse
    {
        $company = $request->user()->company;
        $data = $request->profileData($company);

        if ($request->hasFile('logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        $company->update($data);

        return back()->with('status', 'Dados da empresa atualizados. As mudanças já estão no portal.');
    }
}
