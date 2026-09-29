<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Funil de novos associados.
 *
 * O prospecto e a etapa que hoje vive no WhatsApp e na cabeca da diretoria:
 * quem esta sendo convidado, com quem, qual o proximo passo e quando. Quando
 * vira cadastro, aponta para a empresa ou o membro — e o registro continua,
 * porque a historia de como a empresa chegou vale depois de ela entrar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospects', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            // Empresa ou pessoa: a associacao aceita os dois, e o convite e o
            // formulario de cadastro sao diferentes para cada um.
            $table->string('kind', 20)->default('company');

            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('city')->nullable();
            $table->string('segment')->nullable();
            $table->string('site_url')->nullable();

            $table->string('stage', 20)->default('novo')->index();
            $table->string('source', 20)->default('prospeccao');

            // Quem esta cuidando. Sem dono, prospecto fica orfao e ninguem
            // cobra — e o motivo mais comum de funil abandonado.
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('next_action')->nullable();
            $table->date('next_action_at')->nullable()->index();

            $table->text('notes')->nullable();

            // Preenchidos quando o prospecto vira cadastro no portal.
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();

            $table->string('lost_reason')->nullable();
            $table->timestamp('stage_changed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('prospect_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('nota');
            $table->text('summary');
            $table->timestamp('happened_at');
            $table->timestamps();

            $table->index(['prospect_id', 'happened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospect_interactions');
        Schema::dropIfExists('prospects');
    }
};
