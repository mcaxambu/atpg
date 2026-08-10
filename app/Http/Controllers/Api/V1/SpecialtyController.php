<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use Illuminate\Http\JsonResponse;

class SpecialtyController extends Controller
{
    /**
     * Especialidades com pelo menos um membro publicado. Serve como vocabulario
     * para o filtro `especialidade` do endpoint de membros.
     */
    public function index(): JsonResponse
    {
        $specialties = Specialty::query()
            ->withCount(['members' => fn ($query) => $query->publiclyVisible()])
            ->orderBy('name')
            ->get()
            ->map(fn (Specialty $s) => [
                'slug' => $s->slug,
                'nome' => $s->name,
                'total_membros' => $s->members_count,
            ])
            ->values();

        return response()->json(['data' => $specialties]);
    }
}
