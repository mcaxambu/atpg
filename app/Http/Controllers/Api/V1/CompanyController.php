<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanyController extends Controller
{
    /**
     * Empresas publicadas no diretorio.
     *
     * Filtros: busca, segmento, cidade. Paginacao: pagina, por_pagina (max 100).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'busca' => ['nullable', 'string', 'max:120'],
            'segmento' => ['nullable', 'string', 'max:120'],
            'cidade' => ['nullable', 'string', 'max:120'],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $companies = Company::query()
            ->withCount(['members' => fn ($query) => $query->visible()])
            ->visible()
            ->search($request->query('busca'))
            ->when($request->query('segmento'), fn ($q, $v) => $q->where('segment', $v))
            ->when($request->query('cidade'), fn ($q, $v) => $q->where('city', $v))
            ->orderBy('name')
            ->paginate((int) $request->query('por_pagina', 25))
            ->withQueryString();

        return CompanyResource::collection($companies);
    }

    public function show(string $slug): CompanyResource
    {
        $company = Company::query()
            ->visible()
            ->where('slug', $slug)
            ->withCount(['members' => fn ($query) => $query->visible()])
            ->with(['members' => fn ($query) => $query->visible()->with('specialties')->orderBy('name')])
            ->firstOrFail();

        return new CompanyResource($company);
    }
}
