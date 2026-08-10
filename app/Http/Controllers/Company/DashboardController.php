<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $company = $request->user()->company;

        return view('portal.company.dashboard', [
            'company' => $company,
            'stats' => [
                'total' => Member::where('company_id', $company->id)->count(),
                'publicados' => Member::where('company_id', $company->id)->visible()->count(),
                'pendentes' => Member::where('company_id', $company->id)->pending()->count(),
                'rejeitados' => Member::where('company_id', $company->id)->rejected()->count(),
            ],
            'recentes' => Member::where('company_id', $company->id)
                ->with('specialties')
                ->latest()
                ->limit(5)
                ->get(),
            // O perfil so converte se estiver completo; mostramos o que falta.
            'pendencias' => array_keys(array_filter([
                'Logo da empresa' => blank($company->logo_path),
                'Descrição' => blank($company->description),
                'Segmento' => blank($company->segment),
                'Site' => blank($company->site_url),
                'WhatsApp' => blank($company->whatsapp),
            ])),
        ]);
    }
}
