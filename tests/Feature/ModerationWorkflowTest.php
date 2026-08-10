<?php

namespace Tests\Feature;

use App\Enums\ModerationStatus;
use App\Mail\RegistrationStatusMail;
use App\Models\Company;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModerationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    #[Test]
    public function aprovar_empresa_publica_no_portal_e_avisa_por_email(): void
    {
        Mail::fake();
        $company = Company::factory()->pending()->create();

        $this->actingAs($this->admin())
            ->patch(route('admin.companies.approve', $company))
            ->assertRedirect();

        $company->refresh();

        $this->assertSame(ModerationStatus::Approved, $company->status);
        $this->assertTrue($company->is_active);
        $this->assertNotNull($company->reviewed_at);
        $this->assertNotNull($company->reviewed_by);

        Mail::assertSent(RegistrationStatusMail::class, fn ($mail) => $mail->hasTo($company->email)
            && $mail->event === RegistrationStatusMail::APPROVED);

        $this->get(route('companies.show', $company))->assertOk();
    }

    #[Test]
    public function rejeitar_empresa_exige_motivo(): void
    {
        $company = Company::factory()->pending()->create();

        $this->actingAs($this->admin())
            ->patch(route('admin.companies.reject', $company), ['rejection_reason' => 'curto'])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame(ModerationStatus::Pending, $company->refresh()->status);
    }

    #[Test]
    public function rejeitar_empresa_guarda_motivo_e_avisa_por_email(): void
    {
        Mail::fake();
        $company = Company::factory()->pending()->create();

        $this->actingAs($this->admin())
            ->patch(route('admin.companies.reject', $company), [
                'rejection_reason' => 'O CNPJ informado não corresponde à razão social.',
            ])
            ->assertRedirect();

        $company->refresh();

        $this->assertSame(ModerationStatus::Rejected, $company->status);
        $this->assertFalse($company->is_active);
        $this->assertStringContainsString('CNPJ', $company->rejection_reason);

        Mail::assertSent(RegistrationStatusMail::class, fn ($mail) => $mail->event === RegistrationStatusMail::REJECTED);

        $this->get(route('companies.show', $company))->assertNotFound();
    }

    #[Test]
    public function membro_pendente_tem_fila_propria_e_pode_ser_aprovado(): void
    {
        Mail::fake();
        $member = Member::factory()->pending()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.members.pending'))
            ->assertOk()
            ->assertSee($member->name);

        $this->actingAs($this->admin())
            ->patch(route('admin.members.approve', $member))
            ->assertRedirect();

        $member->refresh();

        $this->assertSame(ModerationStatus::Approved, $member->status);
        $this->assertTrue($member->is_active);

        Mail::assertSent(RegistrationStatusMail::class);
    }

    #[Test]
    public function membro_aprovado_de_empresa_nao_publicada_nao_aparece_no_portal(): void
    {
        $company = Company::factory()->pending()->create();
        $member = Member::factory()->for($company)->create();

        $this->get(route('members.show', $member))->assertNotFound();

        $company->approve();

        $this->get(route('members.show', $member))->assertOk();
    }

    #[Test]
    public function membro_independente_aprovado_aparece_no_portal(): void
    {
        $member = Member::factory()->independent()->create();

        $this->get(route('members.show', $member))->assertOk();
    }

    #[Test]
    public function empresa_aprovada_mas_despublicada_some_do_portal(): void
    {
        $company = Company::factory()->create();

        $this->get(route('companies.show', $company))->assertOk();

        $company->update(['is_active' => false]);

        $this->get(route('companies.show', $company))->assertNotFound();
    }
}
