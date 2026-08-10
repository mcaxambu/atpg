<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Member;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Remove os cadastros ficticios criados pelo PortalDemoSeeder.
 *
 * A marca e o campo `registration_source = 'demo'`, gravado pelo seeder.
 * Cadastros reais (`public_form`, `admin`) nunca sao tocados.
 */
class PurgeDemoData extends Command
{
    protected $signature = 'portal:limpar-demo
                            {--dry-run : Apenas lista o que seria removido}';

    protected $description = 'Remove empresas e membros de demonstração, preservando os cadastros reais';

    private const SOURCE = 'demo';

    public function handle(): int
    {
        $companies = Company::withTrashed()->where('registration_source', self::SOURCE)->get();
        $members = Member::withTrashed()->where('registration_source', self::SOURCE)->get();

        if ($companies->isEmpty() && $members->isEmpty()) {
            $this->info('Nenhum dado de demonstração encontrado.');

            return self::SUCCESS;
        }

        $this->line('Empresas de demonstração ('.$companies->count().'):');
        $companies->each(fn (Company $c) => $this->line("  - {$c->name}"));

        $this->newLine();
        $this->line('Membros de demonstração ('.$members->count().'):');
        $members->each(fn (Member $m) => $this->line("  - {$m->name}"));

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->comment('Simulação: nada foi removido.');

            return self::SUCCESS;
        }

        $this->newLine();

        // Membros primeiro: eles referenciam as empresas.
        foreach ($members as $member) {
            $this->deleteFile($member->photo_path);
            $member->specialties()->detach();
            $member->experiences()->delete();
            $member->projects()->delete();
            $member->certifications()->delete();
            $member->forceDelete();
        }

        foreach ($companies as $company) {
            $this->deleteFile($company->logo_path);
            $company->forceDelete();
        }

        $this->info("Removidos: {$companies->count()} empresa(s) e {$members->count()} membro(s) de demonstração.");
        $this->comment('Cadastros reais (formulário público e internos) foram preservados.');

        return self::SUCCESS;
    }

    private function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
