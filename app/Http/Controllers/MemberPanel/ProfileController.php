<?php

namespace App\Http\Controllers\MemberPanel;

use App\Http\Controllers\Admin\MemberController as AdminMemberController;
use App\Http\Controllers\Controller;
use App\Http\Requests\MemberPanel\MemberProfileRequest;
use App\Models\Member;
use App\Models\Specialty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * O membro editando o proprio cadastro do diretorio.
 *
 * Nao existe id na URL: o registro sai sempre de `member()` do usuario logado,
 * entao nao ha como alcancar o cadastro de outra pessoa.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $member = $this->member($request);
        $member->load(['specialties', 'experiences', 'projects', 'certifications']);

        return view('portal.membro.profile', [
            'member' => $member,
            'specialties' => Specialty::orderBy('name')->get(),
        ]);
    }

    public function update(MemberProfileRequest $request): RedirectResponse
    {
        $member = $this->member($request);
        $data = $request->memberData($member);

        if ($request->hasFile('photo')) {
            $this->deletePhoto($member);
            $data['photo_path'] = $request->file('photo')->store('member-photos', 'public');
        }

        if ($request->boolean('remove_photo')) {
            $this->deletePhoto($member);
            $data['photo_path'] = null;
        }

        $member->update($data);
        $member->specialties()->sync($request->input('specialties', []));
        $this->syncDetails($member, $request);

        return back()->with('status', 'Cadastro atualizado. As alterações voltaram para análise da associação.');
    }

    private function member(Request $request): Member
    {
        return $request->user()->member ?? abort(403, 'Seu acesso não está vinculado a nenhum cadastro.');
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
