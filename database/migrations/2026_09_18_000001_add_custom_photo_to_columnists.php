<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto propria do colunista.
 *
 * Ate aqui a foto da assinatura vinha sempre do cadastro de origem: a foto do
 * membro ou o LOGO da empresa. Numa coluna de empresa isso punha o logo ao lado
 * do nome de quem escreveu ("Rodrigo — DATAHOLDS"), quando o leitor espera ver
 * o rosto do autor. Vazia, a coluna mantem o comportamento antigo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('columnists', function (Blueprint $table) {
            $table->string('custom_photo_path')->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('columnists', function (Blueprint $table) {
            $table->dropColumn('custom_photo_path');
        });
    }
};
