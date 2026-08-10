<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurgeDemoDataTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function remove_apenas_os_cadastros_de_demonstracao(): void
    {
        $demo = Company::factory()->create(['name' => 'Demo Corp', 'registration_source' => 'demo']);
        Member::factory()->for($demo)->create(['name' => 'Membro Demo', 'registration_source' => 'demo']);

        $real = Company::factory()->create(['name' => 'Empresa Real', 'registration_source' => 'public_form']);
        Member::factory()->for($real)->create(['name' => 'Membro Real', 'registration_source' => 'public_form']);

        $this->artisan('portal:limpar-demo')->assertSuccessful();

        $this->assertDatabaseMissing('companies', ['name' => 'Demo Corp']);
        $this->assertDatabaseMissing('members', ['name' => 'Membro Demo']);
        $this->assertDatabaseHas('companies', ['name' => 'Empresa Real']);
        $this->assertDatabaseHas('members', ['name' => 'Membro Real']);
    }

    #[Test]
    public function a_simulacao_nao_apaga_nada(): void
    {
        Company::factory()->create(['name' => 'Demo Corp', 'registration_source' => 'demo']);

        $this->artisan('portal:limpar-demo', ['--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseHas('companies', ['name' => 'Demo Corp']);
    }

    #[Test]
    public function remove_tambem_o_que_estava_na_lixeira(): void
    {
        $demo = Company::factory()->create(['registration_source' => 'demo']);
        $demo->delete();

        $this->artisan('portal:limpar-demo')->assertSuccessful();

        $this->assertSame(0, Company::withTrashed()->count());
    }
}
