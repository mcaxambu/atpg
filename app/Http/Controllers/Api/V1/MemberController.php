<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MemberController extends Controller
{
    /**
     * Membros publicados no diretorio.
     *
     * Filtros: busca, especialidade (slug), empresa (slug), experiencia_minima.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'busca' => ['nullable', 'string', 'max:120'],
            'especialidade' => ['nullable', 'string', 'max:120'],
            'empresa' => ['nullable', 'string', 'max:120'],
            'experiencia_minima' => ['nullable', 'integer', 'min:0', 'max:80'],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $members = Member::query()
            ->with(['company:id,name,slug', 'specialties:id,name,slug'])
            ->publiclyVisible()
            ->search($request->query('busca'))
            ->when($request->query('especialidade'), fn ($q, $v) => $q->whereHas(
                'specialties', fn ($inner) => $inner->where('slug', $v)
            ))
            ->when($request->query('empresa'), fn ($q, $v) => $q->whereHas(
                'company', fn ($inner) => $inner->where('slug', $v)
            ))
            ->when($request->query('experiencia_minima'), fn ($q, $v) => $q->where('experience_years', '>=', (int) $v))
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate((int) $request->query('por_pagina', 25))
            ->withQueryString();

        return MemberResource::collection($members);
    }

    public function show(string $slug): MemberResource
    {
        $member = Member::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->with(['company:id,name,slug', 'specialties:id,name,slug', 'experiences', 'projects', 'certifications'])
            ->firstOrFail();

        return new MemberResource($member);
    }
}
