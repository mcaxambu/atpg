<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ModerateRegistration;
use App\Actions\SyncColumnistProfile;
use App\Enums\ModerationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MemberRequest;
use App\Models\Company;
use App\Models\Member;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MemberController extends Controller
{
    public function __construct(private readonly ModerateRegistration $moderation) {}

    public function index(Request $request)
    {
        $members = Member::query()
            ->with(['company', 'specialties'])
            ->search($request->query('q'))
            ->when(
                $request->filled('status') && ModerationStatus::tryFrom($request->query('status')),
                fn ($query) => $query->where('status', $request->query('status'))
            )
            ->when($request->query('company'), fn ($query, $company) => $query->where('company_id', $company))
            ->when($request->query('specialty'), fn ($query, $specialty) => $query->whereHas(
                'specialties',
                fn ($inner) => $inner->where('specialties.id', $specialty)
            ))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('portal.admin.members.index', [
            'members' => $members,
            'companies' => Company::approved()->orderBy('name')->get(),
            'specialties' => Specialty::orderBy('name')->get(),
            'statusCounts' => $this->statusCounts(),
        ]);
    }

    public function pending(Request $request)
    {
        return view('portal.admin.members.pending', [
            'members' => Member::query()
                ->with(['company', 'specialties'])
                ->pending()
                ->search($request->query('q'))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function trash()
    {
        return view('portal.admin.members.trash', [
            'members' => Member::onlyTrashed()->with('company')->latest('deleted_at')->paginate(20),
        ]);
    }

    public function create()
    {
        return $this->form(new Member(['status' => ModerationStatus::Approved, 'is_active' => true]));
    }

    public function store(MemberRequest $request)
    {
        $data = $request->memberData();
        $data['registration_source'] = 'admin';

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('member-photos', 'public');
        }

        $member = Member::create($data);
        $member->specialties()->sync($request->input('specialties', []));
        $this->syncDetails($member, $request);
        $this->sincronizarColunista($member, $request);

        return redirect()
            ->route('admin.members.index')
            ->with('status', "Membro \"{$member->name}\" cadastrado com sucesso.");
    }

    /**
     * A caixa "e colunista" do formulario liga ou desliga o perfil publico de
     * coluna deste membro.
     */
    private function sincronizarColunista(Member $member, Request $request): void
    {
        app(SyncColumnistProfile::class)(
            $member,
            $request->boolean('is_columnist'),
            $request->input('columnist_name')
        );
    }

    public function show(Member $member)
    {
        $member->load(['company', 'specialties', 'experiences', 'projects', 'certifications', 'reviewer']);

        return view('portal.admin.members.show', compact('member'));
    }

    public function edit(Member $member)
    {
        $member->load(['specialties', 'experiences', 'projects', 'certifications']);

        return $this->form($member);
    }

    public function update(MemberRequest $request, Member $member)
    {
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
        $this->sincronizarColunista($member, $request);

        return redirect()
            ->route('admin.members.index')
            ->with('status', "Membro \"{$member->name}\" atualizado com sucesso.");
    }

    public function destroy(Member $member)
    {
        $member->delete();

        return redirect()
            ->route('admin.members.index')
            ->with('status', "Membro \"{$member->name}\" movido para a lixeira.");
    }

    public function restore(int $member)
    {
        $model = Member::onlyTrashed()->findOrFail($member);
        $model->restore();

        return redirect()
            ->route('admin.members.trash')
            ->with('status', "Membro \"{$model->name}\" restaurado.");
    }

    public function forceDelete(int $member)
    {
        $model = Member::onlyTrashed()->findOrFail($member);
        $this->deletePhoto($model);
        $model->forceDelete();

        return redirect()
            ->route('admin.members.trash')
            ->with('status', "Membro \"{$model->name}\" excluído definitivamente.");
    }

    public function approve(Member $member)
    {
        $this->moderation->approve($member, request()->user());

        return back()->with('status', "Membro \"{$member->name}\" aprovado e publicado no portal.");
    }

    public function reject(Request $request, Member $member)
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $this->moderation->reject($member, $validated['rejection_reason'], $request->user());

        return back()->with('status', "Membro \"{$member->name}\" rejeitado. Ele foi avisado por e-mail.");
    }

    private function form(Member $member)
    {
        return view('portal.admin.members.form', [
            'member' => $member,
            'companies' => Company::orderBy('name')->get(),
            'specialties' => Specialty::orderBy('name')->get(),
        ]);
    }

    private function deletePhoto(Member $member): void
    {
        if ($member->photo_path) {
            Storage::disk('public')->delete($member->photo_path);
        }
    }

    /**
     * Experiencias, projetos e certificacoes chegam como texto livre,
     * uma entrada por linha.
     */
    private function syncDetails(Member $member, Request $request): void
    {
        $relations = [
            'experiences' => $request->input('experiences'),
            'projects' => $request->input('projects'),
            'certifications' => $request->input('certifications'),
        ];

        foreach ($relations as $relation => $value) {
            $member->{$relation}()->delete();

            foreach (self::lines($value) as $position => $title) {
                $member->{$relation}()->create(['title' => $title, 'position' => $position]);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    public static function lines(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function statusCounts(): array
    {
        return Member::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }
}
