<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Event;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A capa cadastrada tem de aparecer onde o leitor passa.
 *
 * A capa do evento era gravada e só saía na página do próprio evento: quem
 * passava pela home ou pela agenda nunca via. O irmão do mesmo defeito era o
 * compartilhamento — nenhuma página oferecia a própria imagem, então todo
 * link colado no WhatsApp mostrava o logo da associação.
 */
class EventCoverTest extends TestCase
{
    use RefreshDatabase;

    private const CAPA = 'event-covers/capa-do-evento.jpg';

    private function eventoPublicado(array $extra = []): Event
    {
        return Event::create(array_merge([
            'title' => 'Cibersegurança em Foco',
            'slug' => 'ciberseguranca-em-foco',
            'type' => 'Evento',
            'description' => 'Um encontro sobre segurança.',
            'location' => 'ACIPG',
            'event_date' => now()->addWeek()->toDateString(),
            'cover_image' => self::CAPA,
            'is_published' => true,
        ], $extra));
    }

    #[Test]
    public function a_capa_aparece_na_home(): void
    {
        $this->eventoPublicado();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('storage/'.self::CAPA, false);
    }

    #[Test]
    public function a_capa_aparece_na_agenda(): void
    {
        $this->eventoPublicado();

        $this->get(route('events'))
            ->assertOk()
            ->assertSee('storage/'.self::CAPA, false);
    }

    #[Test]
    public function a_capa_aparece_na_pagina_do_evento(): void
    {
        $evento = $this->eventoPublicado();

        $this->get(route('events.show', $evento))
            ->assertOk()
            ->assertSee('storage/'.self::CAPA, false);
    }

    #[Test]
    public function evento_sem_capa_nao_quebra_a_home(): void
    {
        $this->eventoPublicado(['cover_image' => null, 'slug' => 'sem-capa', 'title' => 'Evento sem capa']);

        $this->get(route('home'))->assertOk()->assertSee('Evento sem capa');
    }

    // ----- O compartilhamento mostra a imagem certa -----

    #[Test]
    public function o_evento_compartilha_a_propria_capa(): void
    {
        $evento = $this->eventoPublicado();

        $this->get(route('events.show', $evento))
            ->assertOk()
            ->assertSee('<meta property="og:image" content="'.asset('storage/'.self::CAPA).'">', false);
    }

    #[Test]
    public function a_noticia_compartilha_a_propria_capa(): void
    {
        $post = Post::factory()->create(['cover_image' => 'post-covers/capa.jpg']);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('<meta property="og:image" content="'.asset('storage/post-covers/capa.jpg').'">', false);
    }

    #[Test]
    public function a_empresa_compartilha_o_proprio_logo(): void
    {
        $company = Company::factory()->create(['logo_path' => 'company-logos/logo.png']);

        $this->get(route('companies.show', $company->slug))
            ->assertOk()
            ->assertSee('<meta property="og:image" content="'.asset('storage/company-logos/logo.png').'">', false);
    }

    #[Test]
    public function o_membro_compartilha_a_propria_foto(): void
    {
        $member = Member::factory()->create(['photo_path' => 'member-photos/foto.jpg']);

        $this->get(route('members.show', $member->slug))
            ->assertOk()
            ->assertSee('<meta property="og:image" content="'.asset('storage/member-photos/foto.jpg').'">', false);
    }

    #[Test]
    public function sem_imagem_propria_continua_valendo_o_logo_da_associacao(): void
    {
        $evento = $this->eventoPublicado(['cover_image' => null]);

        $resposta = $this->get(route('events.show', $evento))->assertOk();

        $resposta->assertSee('og:image', false);
        $resposta->assertDontSee('storage/event-covers', false);
    }
}
