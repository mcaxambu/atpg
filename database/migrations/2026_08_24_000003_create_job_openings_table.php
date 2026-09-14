<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vagas publicadas pelas empresas associadas.
 *
 * Segue o mesmo desenho de Empresa e Membro: `status` e a decisao da
 * associacao e `is_active` e a publicacao, para que a empresa possa encerrar
 * uma vaga aprovada sem perder a aprovacao.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_openings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type', 32)->default('clt');
            $table->string('workplace', 32)->default('presencial');
            $table->string('seniority', 32)->nullable();

            $table->text('description');
            $table->text('requirements')->nullable();
            $table->text('benefits')->nullable();

            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();

            // Faixa salarial e opcional e so aparece se a empresa quiser.
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->boolean('show_salary')->default(false);

            // Sem data, a vaga fica no ar ate a empresa encerrar.
            $table->date('closes_at')->nullable();

            $table->boolean('is_active')->default(false);
            $table->string('status', 16)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // A listagem publica filtra sempre por estes tres campos juntos.
            $table->index(['status', 'is_active', 'closes_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_openings');
    }
};
