<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Industrias de Ponta Grossa — o MERCADO da associacao, nao os associados.
 *
 * O cruzamento e o motivo desta tabela existir: a industria marca o que
 * PRECISA no mesmo vocabulario de especialidades que a associada usa para
 * marcar o que FAZ. Necessidade em texto livre nao cruza com nada — o campo
 * `segment` das empresas ja mostra isso, com 22 grafias diferentes para 27
 * cadastros.
 *
 * Os campos de contato e de anotacao ficam aqui, mas so a diretoria os ve: as
 * associadas enxergam a industria e a necessidade, nao o telefone de quem
 * decide. Algumas delas concorrem entre si.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industries', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('legal_name')->nullable();
            $table->string('cnpj', 20)->nullable();

            $table->string('sector')->nullable();
            $table->string('size', 20)->nullable();
            $table->unsignedInteger('employees')->nullable();

            $table->string('city')->default('Ponta Grossa');
            $table->string('state', 2)->default('PR');
            $table->string('neighborhood')->nullable();
            $table->string('address')->nullable();
            $table->string('site_url')->nullable();

            $table->text('description')->nullable();

            // ----- So a diretoria enxerga daqui para baixo -----
            $table->string('contact_name')->nullable();
            $table->string('contact_role')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->text('notes')->nullable();
            $table->date('last_contact_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'name']);
            $table->index('sector');
        });

        // O que a industria PRECISA.
        Schema::create('industry_specialty', function (Blueprint $table) {
            $table->foreignId('industry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained()->cascadeOnDelete();

            $table->primary(['industry_id', 'specialty_id']);
        });

        // O que a associada FAZ. Ate aqui so os MEMBROS tinham especialidade;
        // sem este lado, o cruzamento nao teria com o que cruzar.
        Schema::create('company_specialty', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained()->cascadeOnDelete();

            $table->primary(['company_id', 'specialty_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_specialty');
        Schema::dropIfExists('industry_specialty');
        Schema::dropIfExists('industries');
    }
};
