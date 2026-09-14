<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Candidaturas recebidas pelo portal.
 *
 * Sao dados pessoais de terceiros (nome, contato e curriculo de quem nem e
 * associado), entao valem regras mais duras que o resto do portal:
 *
 * - o curriculo vai para o disco PRIVADO, nunca para storage/app/public;
 * - so a empresa dona da vaga (e ninguem mais) le a candidatura;
 * - apagar a vaga apaga as candidaturas junto, por cascade.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_opening_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->string('linkedin_url')->nullable();
            $table->text('message')->nullable();

            // Caminho no disco privado; nunca exposto direto ao navegador.
            $table->string('resume_path')->nullable();

            // Prova do consentimento, exigida pela LGPD.
            $table->timestamp('consented_at');

            // Quando a empresa abriu a candidatura pela primeira vez.
            $table->timestamp('viewed_at')->nullable();

            $table->timestamps();

            $table->index(['job_opening_id', 'created_at']);
            // Uma pessoa se candidata uma vez por vaga.
            $table->unique(['job_opening_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
