<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Convite do funil e primeiro acesso do associado.
 *
 * O token do convite e o que liga o prospecto ao cadastro: sem ele, a empresa
 * preenche o formulario publico e ninguem sabe que aquele cadastro nasceu da
 * conversa que a diretoria vinha tendo ha dois meses.
 *
 * `last_login_at` alimenta o acompanhamento pos-aprovacao. E o unico sinal que
 * responde "essa empresa chegou a entrar no painel?" — sem ele, so daria para
 * saber perguntando, que e exatamente o trabalho manual que queremos evitar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->string('invite_token', 64)->nullable()->unique()->after('member_id');
            $table->timestamp('invited_at')->nullable()->after('invite_token');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn(['invite_token', 'invited_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_login_at');
        });
    }
};
