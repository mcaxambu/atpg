<?php

namespace App\Http\Controllers\Company;

use App\Enums\ModerationStatus;
use App\Http\Controllers\Admin\MemberController as AdminMemberController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CompanyMemberRequest;
use App\Models\Member;
use App\Models\Specialty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Colaboradores sob a responsabilidade da empresa.
 *
 * Todas as consultas partem de `scopedQuery()`, que filtra pela empresa do
 * usuario logado — assim nao existe caminho para alcancar o colaborador de
 * outra empresa, nem trocando o id na URL.
 */
class MemberController extends Controller
{
    public function index(Request $request): View
    {
        return view('portal.company.members.index', [
            'members' => $this->scopedQuery($request)
                ->with('specialties')
                ->search($request->query('q'))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Member(['status' => ModerationStatus::Pending]));
    }

    public function store(CompanyMemberRequest $request): RedirectResponse
    {
        $data = $request->memberData();
        $data['company_id'] = $request->user()->company_id;

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('member-photos', 'public');
        }

        $member = Member::create($data);
        $member->specialties()->sync($request->input('specialties', []));
        $this->syncDetails($member, $request);

        return redirect()->route('empresa.membros.index')
            ->with('status', "\"{$member->name}\" foi enviado para análise da associação.");
    }

    public function edit(Request $request, int $member): View
    {
        $model = $this->scopedQuery($request)->findOrFail($member);
        $model->load(['specialties', 'experiences', 'projects', 'certifications']);

        return $this->form($model);
    }

    public function update(CompanyMemberRequest $request, int $member): RedirectResponse
    {
        $model = $this->scopedQuery($request)->findOrFail($member);
        $data = $request->memberData($model);

        if ($request->hasFile('photo')) {
            $this->deletePhoto($model);
            $data['photo_path'] = $request->file('photo')->store('member-photos', 'public');
        }

        if ($request->boolean('remove_photo')) {
            $this->deletePhoto($model);
            $data['photo_path'] = null;
        }

        $model->update($data);
        $model->specialties()->sync($request->input('specialties', []));
        $this->syncDetails($model, $request);

        $aviso = $model->isPending()
            ? ' As alterações voltaram para análise da associação.'
            : '';

        return redirect()->route('empresa.membros.index')
            ->with('status', "\"{$model->name}\" atualizado.".$aviso);
    }

    public function destroy(Request $request, int $member): RedirectResponse
    {
        $model = $this->scopedQuery($request)->findOrFail($member);
        $model->delete();

        return redirect()->route('empresa.membros.index')
            ->with('status', "\"{$model->name}\" removido da sua equipe.");
    }

    private function scopedQuery(Request $request)
    {
        return Member::query()->where('company_id', $request->user()->company_id);
    }

    private function form(Member $member): View
    {
        return view('portal.company.members.form', [
            'member' => $member,
            'specialties' => Specialty::orderBy('name')->get(),
        ]);
    }

    private function deletePhoto(Member $member): void
    {
        if ($member->photo_path) {
            Storage::disk('public')->delete($member->photo_path);
        }
    }

    private function syncDetails(Member $member, Request $request): void
    {
        foreach (['experiences', 'projects', 'certifications'] as $relation) {
            $member->{$relation}()->delete();

            foreach (AdminMemberController::lines($request->input($relation)) as $position => $title) {
                $member->{$relation}()->create(['title' => $title, 'position' => $position]);
            }
        }
    }
}
