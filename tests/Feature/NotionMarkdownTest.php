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

/**
 * Exercita um export real do Notion, com as particularidades dele.
 */
class NotionMarkdownTest extends TestCase
{
    use RefreshDatabase;

    /** Trecho no formato que o Notion gera ao exportar uma pagina em Markdown. */
    private function exportDoNotion(): string
    {
        return "\xEF\xBB\xBF# Assembleia Geral Ordinária\r\n"
            ."\r\n"
            ."**Data:** 04/08/2026\r\n"
            ."**Local:** Sede da associação\r\n"
            ."\r\n"
            ."## Pauta\r\n"
            ."\r\n"
            ."- [x]  Prestação de contas\r\n"
            ."- [ ]  Eleição da diretoria\r\n"
            ."\r\n"
            ."![Untitled](Assembleia%20Geral%20Ordinaria%20abc123/Untitled.png)\r\n"
            ."\r\n"
            ."## Deliberações\r\n"
            ."\r\n"
            ."| Item | Resultado |\r\n"
            ."| --- | --- |\r\n"
            ."| Contas | Aprovadas |\r\n"
            ."| Diretoria | Adiada |\r\n"
            ."\r\n"
            .'> Observação registrada em ata.';
    }

    private function enviarExport(): MeetingMinute
    {
        Storage::fake(MeetingMinute::DISK);

        $this->actingAs(User::factory()->create())->post(route('admin.atas.store'), [
            'title' => 'Assembleia Geral Ordinária',
            'meeting_date' => now()->subDay()->toDateString(),
            'is_published' => '1',
            'markdown_file' => UploadedFile::fake()->createWithContent('ata.md', $this->exportDoNotion()),
        ])->assertRedirect(route('admin.atas.index'));

        return MeetingMinute::firstOrFail();
    }

    #[Test]
    public function importa_o_export_do_notion_sem_bom_nem_crlf(): void
    {
        $ata = $this->enviarExport();

        $this->assertStringNotContainsString("\r", $ata->body);
        $this->assertStringNotContainsString("\xEF\xBB\xBF", $ata->body);
    }

    #[Test]
    public function descarta_o_titulo_repetido_da_pagina(): void
    {
        // O Notion repete o titulo como H1; a tela de leitura ja o exibe.
        $ata = $this->enviarExport();

        $this->assertStringNotContainsString('# Assembleia Geral Ordinária', $ata->body);
        $this->assertStringStartsWith('**Data:**', $ata->body);
    }

    #[Test]
    public function remove_imagem_de_caminho_relativo_e_avisa(): void
    {
        Storage::fake(MeetingMinute::DISK);

        $this->actingAs(User::factory()->create())->post(route('admin.atas.store'), [
            'title' => 'Assembleia',
            'meeting_date' => now()->subDay()->toDateString(),
            'markdown_file' => UploadedFile::fake()->createWithContent('ata.md', $this->exportDoNotion()),
        ])->assertSessionHas('status', fn (string $aviso) => str_contains($aviso, 'imagem'));

        $ata = MeetingMinute::firstOrFail();

        $this->assertStringNotContainsString('Untitled.png', $ata->body);
    }

    #[Test]
    public function mantem_imagem_hospedada_em_url(): void
    {
        Storage::fake(MeetingMinute::DISK);

        $this->actingAs(User::factory()->create())->post(route('admin.atas.store'), [
            'title' => 'Com imagem externa',
            'meeting_date' => now()->subDay()->toDateString(),
            'body' => 'Texto ![grafico](https://exemplo.test/grafico.png) final.',
        ]);

        $this->assertStringContainsString('https://exemplo.test/grafico.png', MeetingMinute::firstOrFail()->body);
    }

    #[Test]
    public function tabela_e_lista_de_tarefas_viram_html(): void
    {
        $ata = $this->enviarExport();
        $html = $ata->rendered_body;

        // Dependem do GitHub Flavored Markdown, nao do CommonMark puro.
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<th>Resultado</th>', $html);
        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('<blockquote>', $html);
        $this->assertStringContainsString('<h2>Pauta</h2>', $html);
    }

    #[Test]
    public function a_empresa_le_a_ata_importada(): void
    {
        $ata = $this->enviarExport();

        $empresa = User::factory()
            ->forCompany(Company::factory()->create())
            ->create();

        $this->actingAs($empresa)
            ->get(route('empresa.atas.show', $ata->id))
            ->assertOk()
            ->assertSee('Deliberações')
            ->assertSee('Aprovadas');
    }
}
