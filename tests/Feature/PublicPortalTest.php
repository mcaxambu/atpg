<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Member;
use App\Models\Specialty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicPortalTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function todas_as_paginas_publicas_respondem(): void
    {
        Specialty::factory()->create();
        $company = Company::factory()->create();
        Member::factory()->for($company)->create();

        $urls = [
            route('home'),
            route('members.index'),
            route('companies.index'),
            route('events'),
            route('posts.index'),
            route('about'),
            route('join'),
            route('projects'),
            route('benefits'),
            route('governance'),
            route('lgpd'),
            route('privacy'),
            route('cookies'),
            route('members.create'),
            route('companies.register.create'),
            route('sitemap'),
            route('admin.login'),
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk("Falhou em {$url}");
        }
    }

    #[Test]
    public function o_portal_tambem_responde_sob_o_prefixo_atpg(): void
    {
        foreach (['/atpg', '/atpg/empresas', '/atpg/membros', '/atpg/admin/login'] as $url) {
            $this->assertSame(200, $this->get($url)->getStatusCode(), "Falhou em {$url}");
        }
    }

    #[Test]
    public function o_sitemap_lista_apenas_conteudo_publicado(): void
    {
        $published = Company::factory()->create(['slug' => 'empresa-publicada']);
        $pending = Company::factory()->pending()->create(['slug' => 'empresa-pendente']);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee($published->slug)
            ->assertDontSee($pending->slug);
    }

    #[Test]
    public function a_busca_do_diretorio_filtra_empresas(): void
    {
        Company::factory()->create(['name' => 'Alpha Digital', 'segment' => 'Software']);
        Company::factory()->create(['name' => 'Beta Sistemas', 'segment' => 'Dados']);

        $this->get(route('companies.index', ['search' => 'Alpha']))
            ->assertOk()
            ->assertSee('Alpha Digital')
            ->assertDontSee('Beta Sistemas');

        $this->get(route('companies.index', ['segment' => 'Dados']))
            ->assertOk()
            ->assertSee('Beta Sistemas')
            ->assertDontSee('Alpha Digital');
    }
}
