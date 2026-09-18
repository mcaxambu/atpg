<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\MeetingMinute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MeetingMinuteTest extends TestCase
{
    use RefreshDatabase;

    private function pdf(string $nome = 'ata.pdf'): UploadedFile
    {
        // %PDF no inicio: sem isso a validacao de mimetype recusa o arquivo.
        return UploadedFile::fake()->createWithContent($nome, '%PDF-1.4 conteudo de teste');
    }

    private function empresaLogada(): User
    {
        return User::factory()->forCompany(Company::factory()->create())->create();
    }

    #[Test]
    public function o_administrador_publica_uma_ata(): void
    {
        Storage::fake(MeetingMinute::DISK);
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.atas.store'), [
            'title' => 'Assembleia Geral Ordinária',
            'meeting_date' => now()->subWeek()->toDateString(),
            'summary' => 'Prestação de contas do exercício.',
            'is_published' => '1',
            'file' => $this->pdf(),
        ])->assertRedirect(route('admin.atas.index'));

        $ata = MeetingMinute::first();

        $this->assertSame('Assembleia Geral Ordinária', $ata->title);
        $this->assertSame($admin->id, $ata->uploaded_by);
        $this->assertTrue($ata->is_published);
        Storage::disk(MeetingMinute::DISK)->assertExists($ata->file_path);
    }

    #[Test]
    public function o_pdf_nao_fica_no_disco_publico(): void
    {
        Storage::fake(MeetingMinute::DISK);
        Storage::fake('public');

        $this->actingAs(User::factory()->create())->post(route('admin.atas.store'), [
            'title' => 'Reunião de diretoria',
            'meeting_date' => now()->subDay()->toDateString(),
            'file' => $this->pdf(),
        ]);

        // O conteudo e restrito: no disco publico bastaria adivinhar a URL.
        $this->assertEmpty(Storage::disk('public')->allFiles());
        $this->assertNotEmpty(Storage::disk(MeetingMinute::DISK)->allFiles());
    }

    #[Test]
    public function so_aceita_pdf(): void
    {
        Storage::fake(MeetingMinute::DISK);

        $this->actingAs(User::factory()->create())->post(route('admin.atas.store'), [
            'title' => 'Ata falsa',
            'meeting_date' => now()->subDay()->toDateString(),
            'file' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertSessionHasErrors('file');

        $this->assertSame(0, MeetingMinute::count());
    }

    #[Test]
    public function recusa_reuniao_com_data_futura(): void
    {
        Storage::fake(MeetingMinute::DISK);

        $this->actingAs(User::factory()->create())->post(route('admin.atas.store'), [
            'title' => 'Reunião que ainda não houve',
            'meeting_date' => now()->addWeek()->toDateString(),
            'file' => $this->pdf(),
        ])->assertSessionHasErrors('meeting_date');
    }

    #[Test]
    public function a_empresa_ve_e_baixa_as_atas_publicadas(): void
    {
        Storage::fake(MeetingMinute::DISK);
        $user = $this->empresaLogada();

        $ata = MeetingMinute::factory()->create(['title' => 'Assembleia de Março']);
        Storage::disk(MeetingMinute::DISK)->put($ata->file_path, '%PDF-1.4 teste');

        $this->actingAs($user)
            ->get(route('empresa.atas.index'))
            ->assertOk()
            ->assertSee('Assembleia de Março');

        $this->actingAs($user)
            ->get(route('empresa.atas.download', $ata->id))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename='.$ata->file_name);
    }

    #[Test]
    public function a_empresa_nao_ve_nem_baixa_rascunho(): void
    {
        Storage::fake(MeetingMinute::DISK);
        $user = $this->empresaLogada();

        $rascunho = MeetingMinute::factory()->rascunho()->create(['title' => 'Ata em rascunho']);
        Storage::disk(MeetingMinute::DISK)->put($rascunho->file_path, '%PDF-1.4 teste');

        $this->actingAs($user)
            ->get(route('empresa.atas.index'))
            ->assertOk()
            ->assertDontSee('Ata em rascunho');

        // Trocar o id na URL tambem nao alcanca o rascunho.
        $this->actingAs($user)
            ->get(route('empresa.atas.download', $rascunho->id))
            ->assertNotFound();
    }

    #[Test]
    public function visitante_nao_baixa_ata(): void
    {
        Storage::fake(MeetingMinute::DISK);
        $ata = MeetingMinute::factory()->create();
        Storage::disk(MeetingMinute::DISK)->put($ata->file_path, '%PDF-1.4 teste');

        $this->get(route('empresa.atas.download', $ata->id))->assertRedirect(route('entrar'));
        $this->get(route('admin.atas.download', $ata))->assertRedirect(route('entrar'));
    }

    #[Test]
    public function a_empresa_nao_entra_na_gestao_de_atas(): void
    {
        $user = $this->empresaLogada();

        $this->actingAs($user)->get(route('admin.atas.index'))->assertRedirect(route('empresa.dashboard'));
        $this->actingAs($user)->get(route('admin.atas.create'))->assertRedirect(route('empresa.dashboard'));
    }

    #[Test]
    public function trocar_o_arquivo_remove_o_anterior(): void
    {
        Storage::fake(MeetingMinute::DISK);
        $admin = User::factory()->create();

        $ata = MeetingMinute::factory()->create();
        Storage::disk(MeetingMinute::DISK)->put($ata->file_path, '%PDF-1.4 antigo');
        $caminhoAntigo = $ata->file_path;

        $this->actingAs($admin)->put(route('admin.atas.update', $ata), [
            'title' => $ata->title,
            'meeting_date' => $ata->meeting_date->toDateString(),
            'is_published' => '1',
            'file' => $this->pdf('nova.pdf'),
        ])->assertRedirect();

        $ata->refresh();

        $this->assertNotSame($caminhoAntigo, $ata->file_path);
        Storage::disk(MeetingMinute::DISK)->assertMissing($caminhoAntigo);
        Storage::disk(MeetingMinute::DISK)->assertExists($ata->file_path);
    }

    #[Test]
    public function aceita_ata_so_com_markdown_sem_pdf(): void
    {
        Storage::fake(MeetingMinute::DISK);

        $this->actingAs(User::factory()->create())->post(route('admin.atas.store'), [
            'title' => 'Reunião de diretoria',
            'meeting_date' => now()->subDay()->toDateString(),
            'body' => "# Pauta\n\n- Prestação de contas\n- Eleição",
            'is_published' => '1',
        ])->assertRedirect(route('admin.atas.index'));

        $ata = MeetingMinute::first();

        $this->assertTrue($ata->hasBody());
        $this->assertFalse($ata->hasFile());
    }

    #[Test]
    public function exige_pelo_menos_pdf_ou_texto(): void
    {
        Storage::fake(MeetingMinute::DISK);

        $this->actingAs(User::factory()->create())->post(route('admin.atas.store'), [
            'title' => 'Ata vazia',
            'meeting_date' => now()->subDay()->toDateString(),
        ])->assertSessionHasErrors('file');

        $this->assertSame(0, MeetingMinute::count());
    }

    #[Test]
    public function o_arquivo_md_enviado_vira_o_texto_da_ata(): void
    {
        Storage::fake(MeetingMinute::DISK);

        // Com CRLF e BOM, como sai de um editor no Windows.
        $conteudo = "\xEF\xBB\xBF# Assembleia\r\n\r\n- Item um\r\n- Item dois";

        $this->actingAs(User::factory()->create())->post(route('admin.atas.store'), [
            'title' => 'Assembleia de agosto',
            'meeting_date' => now()->subDay()->toDateString(),
            'markdown_file' => UploadedFile::fake()->createWithContent('ata.md', $conteudo),
        ])->assertRedirect();

        $ata = MeetingMinute::first();

        // O arquivo chega em Markdown e e convertido na importacao: o painel
        // guarda HTML desde 17/09/2026, e a ata tem de abrir formatada no
        // editor, nao com a marcacao a mostra.
        $this->assertStringStartsWith('<ul>', $ata->body);
        $this->assertStringContainsString('<li>Item um</li>', $ata->body);

        // O H1 inicial e descartado: no export do Notion ele e o titulo da
        // pagina, que a tela de leitura ja mostra acima do conteudo.
        $this->assertStringNotContainsString('Assembleia', $ata->body);
        $this->assertStringNotContainsString("\r", $ata->body);
        $this->assertStringNotContainsString("\xEF\xBB\xBF", $ata->body);
    }

    #[Test]
    public function o_markdown_vira_html_na_leitura(): void
    {
        Storage::fake(MeetingMinute::DISK);
        $user = $this->empresaLogada();

        $ata = MeetingMinute::factory()->create([
            'title' => 'Assembleia de Agosto',
            'body' => "## Deliberações\n\n- Contas aprovadas",
        ]);

        $this->actingAs($user)
            ->get(route('empresa.atas.show', $ata->id))
            ->assertOk()
            ->assertSee('<h2>Deliberações</h2>', false)
            ->assertSee('<li>Contas aprovadas</li>', false);
    }

    #[Test]
    public function html_dentro_do_markdown_nao_e_executado(): void
    {
        Storage::fake(MeetingMinute::DISK);
        $user = $this->empresaLogada();

        $ata = MeetingMinute::factory()->create([
            'body' => 'Texto <script>alert("xss")</script> e <img src=x onerror=alert(1)>',
        ]);

        $resposta = $this->actingAs($user)->get(route('empresa.atas.show', $ata->id))->assertOk();

        /*
            Desde a virada para HTML a marcacao perigosa nao e escapada: ela e
            RETIRADA na gravacao, pela lista de permissao (App\Support\SafeHtml)
            — e retirada de novo na exibicao. Uma conta de administrador
            comprometida nao pode virar XSS armazenado para todos os associados.
        */
        $this->assertStringNotContainsString('script', $ata->fresh()->body);

        $resposta->assertDontSee('<script>alert', false);
        $resposta->assertDontSee('<img src=x', false);
        $resposta->assertDontSee('onerror', false);

        // O texto em volta sobrevive: limpar formatacao nao pode apagar a frase.
        $resposta->assertSee('Texto', false);
    }

    #[Test]
    public function a_busca_alcanca_o_texto_da_ata(): void
    {
        Storage::fake(MeetingMinute::DISK);
        $user = $this->empresaLogada();

        MeetingMinute::factory()->create([
            'title' => 'Reunião ordinária',
            'summary' => null,
            'body' => 'Aprovada a reforma do estatuto social.',
        ]);
        MeetingMinute::factory()->create(['title' => 'Outra reunião', 'summary' => null, 'body' => 'Assunto diverso.']);

        $this->actingAs($user)
            ->get(route('empresa.atas.index', ['q' => 'estatuto']))
            ->assertOk()
            ->assertSee('Reunião ordinária')
            ->assertDontSee('Outra reunião');
    }

    #[Test]
    public function ata_sem_texto_nao_tem_pagina_de_leitura(): void
    {
        Storage::fake(MeetingMinute::DISK);
        $user = $this->empresaLogada();

        $ata = MeetingMinute::factory()->create(['body' => null]);

        $this->actingAs($user)->get(route('empresa.atas.show', $ata->id))->assertNotFound();
    }

    #[Test]
    public function o_painel_nao_oferece_baixar_em_ata_sem_pdf(): void
    {
        Storage::fake(MeetingMinute::DISK);

        $comTexto = MeetingMinute::factory()->create([
            'title' => 'Ata só com texto',
            'file_path' => null, 'file_name' => null, 'file_size' => 0,
            'body' => '## Deliberações',
        ]);

        $resposta = $this->actingAs(User::factory()->create())
            ->get(route('admin.atas.index'))
            ->assertOk();

        $resposta->assertDontSee(route('admin.atas.download', $comTexto), false);
        $resposta->assertSee(route('admin.atas.show', $comTexto), false);
    }

    #[Test]
    public function baixar_ata_sem_pdf_leva_para_a_leitura(): void
    {
        Storage::fake(MeetingMinute::DISK);

        $ata = MeetingMinute::factory()->create([
            'file_path' => null, 'file_name' => null, 'file_size' => 0,
            'body' => '## Deliberações',
        ]);

        // Era um 404 seco; agora abre o texto, que e o que a pessoa queria.
        $this->actingAs(User::factory()->create())
            ->get(route('admin.atas.download', $ata))
            ->assertRedirect(route('admin.atas.show', $ata));

        $this->actingAs($this->empresaLogada())
            ->get(route('empresa.atas.download', $ata->id))
            ->assertRedirect(route('empresa.atas.show', $ata->id));
    }

    #[Test]
    public function ata_sem_pdf_e_sem_texto_da_404(): void
    {
        Storage::fake(MeetingMinute::DISK);

        $ata = MeetingMinute::factory()->create([
            'file_path' => null, 'file_name' => null, 'file_size' => 0, 'body' => null,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.atas.download', $ata))
            ->assertNotFound();
    }

    #[Test]
    public function o_painel_le_a_ata_em_markdown(): void
    {
        Storage::fake(MeetingMinute::DISK);

        $ata = MeetingMinute::factory()->create([
            'title' => 'Reunião de diretoria',
            'body' => "## Deliberações\n\n- Contas aprovadas",
            'is_published' => false,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.atas.show', $ata))
            ->assertOk()
            ->assertSee('<h2>Deliberações</h2>', false)
            ->assertSee('rascunho');
    }

    #[Test]
    public function as_atas_saem_da_mais_recente_para_a_mais_antiga(): void
    {
        Storage::fake(MeetingMinute::DISK);

        MeetingMinute::factory()->create(['title' => 'Mais antiga', 'meeting_date' => now()->subMonths(6)]);
        MeetingMinute::factory()->create(['title' => 'Mais recente', 'meeting_date' => now()->subDay()]);

        $lista = MeetingMinute::published()->recentFirst()->pluck('title')->all();

        $this->assertSame(['Mais recente', 'Mais antiga'], $lista);
    }
}
