<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestor de reunioes: convocacao, pauta, presenca e encaminhamentos.
 *
 * Nao se confunde com "eventos", que e a agenda publica do portal. Aqui e
 * governanca interna: quem foi convocado, quem confirmou, o que se deliberou
 * e o que ficou pendente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type', 32)->default('outra');
            $table->string('status', 16)->default('agendada');
            $table->dateTime('scheduled_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('location')->nullable();
            $table->string('online_url')->nullable();
            $table->text('description')->nullable();

            // Token do link de convocacao enviado por WhatsApp. Aleatorio e
            // longo: e o unico segredo que protege a pagina publica.
            $table->string('public_token', 48)->unique();
            $table->dateTime('confirmations_until')->nullable();

            // Quorum minimo de empresas para a assembleia ser valida.
            $table->unsignedSmallInteger('quorum_minimum')->nullable();

            $table->foreignId('meeting_minute_id')->nullable()
                ->constrained('meeting_minutes')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('meeting_agenda_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('presenter')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();

            // Preenchido depois da reuniao.
            $table->string('outcome', 16)->nullable();
            $table->text('outcome_notes')->nullable();
            $table->unsignedSmallInteger('votes_for')->nullable();
            $table->unsignedSmallInteger('votes_against')->nullable();
            $table->unsignedSmallInteger('votes_abstain')->nullable();

            $table->timestamps();

            $table->index(['meeting_id', 'position']);
        });

        Schema::create('meeting_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('status', 16)->default('pendente');
            $table->string('responded_by')->nullable();
            $table->dateTime('responded_at')->nullable();

            // Presenca real no dia, registrada pela associacao. Nulo enquanto
            // a reuniao nao aconteceu — diferente de "faltou".
            $table->boolean('attended')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['meeting_id', 'company_id']);
        });

        Schema::create('meeting_action_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meeting_agenda_item_id')->nullable()
                ->constrained('meeting_agenda_items')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('responsible')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 16)->default('pendente');
            $table->dateTime('completed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_action_items');
        Schema::dropIfExists('meeting_attendances');
        Schema::dropIfExists('meeting_agenda_items');
        Schema::dropIfExists('meetings');
    }
};
