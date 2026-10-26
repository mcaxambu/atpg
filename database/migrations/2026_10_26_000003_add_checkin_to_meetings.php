<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Check-in de presença nas reuniões.
 *
 * A presença ja existia (`attended`), mas dependia de alguem da diretoria
 * marcar a lista inteira no fim — e, na pratica, isso nunca acontecia: o
 * portal tinha 7 reunioes e nenhuma com presenca registrada. O check-in
 * inverte quem faz o trabalho: quem chega se registra.
 *
 * `checkin_code` e o codigo mostrado NA SALA. Sem ele, qualquer associado
 * logado faria check-in de casa, e a presenca deixaria de significar
 * presenca. Nao e seguranca forte — e o suficiente para exigir estar onde a
 * reuniao acontece.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('checkin_code', 6)->nullable()->after('public_token');
        });

        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->after('attended');
            // Quem registrou: a propria empresa ou a diretoria na recepcao.
            $table->string('checked_in_source', 20)->nullable()->after('checked_in_at');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('checkin_code');
        });

        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->dropColumn(['checked_in_at', 'checked_in_source']);
        });
    }
};
