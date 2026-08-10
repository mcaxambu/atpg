<?php

namespace Tests\Feature;

use App\Enums\ModerationStatus;
use App\Http\Middleware\ProtectPublicForm;
use App\Mail\RegistrationStatusMail;
use App\Models\Company;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const CNPJ = '11.222.333/0001-81';

    private function companyPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nova Tech',
            'legal_name' => 'Nova Tech LTDA',
            'cnpj' => self::CNPJ,
            'segment' => 'Software',
            'city' => 'Ponta Grossa',
            'state' => 'PR',
            'zip_code' => '84000-000',
            'address' => 'Rua das Flores',
            'address_number' => '100',
            'neighborhood' => 'Centro',
            'description' => 'Empresa de desenvolvimento de software sob medida para o varejo.',
            'contact_name' => 'Maria Lima',
            'contact_role' => 'Sócia',
            'email' => 'contato@novatech.test',
            'whatsapp' => '(42) 99999-9999',
            'logo' => UploadedFile::fake()->image('logo.png'),
            'privacy_consent' => '1',
        ], $overrides);
    }

    #[Test]
    public function cadastro_publico_de_empresa_entra_como_pendente(): void
    {
        Storage::fake('public');
        Mail::fake();

        $this->post(route('companies.register.store'), $this->companyPayload())
            ->assertRedirect(route('companies.register.create'))
            ->assertSessionHas('registration_success');

        $company = Company::firstWhere('name', 'Nova Tech');

        $this->assertSame(ModerationStatus::Pending, $company->status);
        $this->assertFalse($company->is_active);
        $this->assertSame('public', $company->registration_source);

        // Quem se cadastrou recebe a confirmação de recebimento.
        Mail::assertSent(RegistrationStatusMail::class, fn ($mail) => $mail->event === RegistrationStatusMail::RECEIVED);

        // E não aparece no portal antes da aprovação.
        $this->get(route('companies.index'))->assertDontSee('Nova Tech');
    }

    #[Test]
    public function cadastro_publico_recusa_cnpj_invalido(): void
    {
        Storage::fake('public');

        $this->post(route('companies.register.store'), $this->companyPayload(['cnpj' => '00.000.000/0000-00']))
            ->assertSessionHasErrors('cnpj');

        $this->assertDatabaseCount('companies', 0);
    }

    #[Test]
    public function honeypot_bloqueia_envio_automatizado(): void
    {
        Storage::fake('public');

        $this->post(route('companies.register.store'), $this->companyPayload([
            ProtectPublicForm::HONEYPOT_FIELD => 'http://spam.test',
        ]))->assertSessionHasErrors('name');

        $this->assertDatabaseCount('companies', 0);
    }

    #[Test]
    public function envio_instantaneo_demais_e_bloqueado(): void
    {
        Storage::fake('public');

        $this->post(route('companies.register.store'), $this->companyPayload([
            ProtectPublicForm::TIMESTAMP_FIELD => (string) time(),
        ]))->assertSessionHasErrors('name');

        $this->assertDatabaseCount('companies', 0);
    }

    #[Test]
    public function cadastro_publico_de_membro_entra_como_pendente(): void
    {
        Mail::fake();

        $this->post(route('members.store'), [
            'name' => 'João Pereira',
            'role' => 'Desenvolvedor',
            'email' => 'joao@exemplo.test',
            'experiences' => "Empresa A\nEmpresa B",
            'privacy_consent' => '1',
        ])->assertRedirect(route('members.create'));

        $member = Member::firstWhere('name', 'João Pereira');

        $this->assertSame(ModerationStatus::Pending, $member->status);
        $this->assertFalse($member->is_active);
        $this->assertCount(2, $member->experiences);

        Mail::assertSent(RegistrationStatusMail::class);
    }

    #[Test]
    public function membro_publico_nao_pode_se_vincular_a_empresa_pendente(): void
    {
        $company = Company::factory()->pending()->create();

        $this->post(route('members.store'), [
            'name' => 'João Pereira',
            'email' => 'joao@exemplo.test',
            'company_id' => $company->id,
            'privacy_consent' => '1',
        ])->assertSessionHasErrors('company_id');
    }
}
