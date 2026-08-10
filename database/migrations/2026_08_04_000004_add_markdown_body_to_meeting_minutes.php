<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite que a ata tenha corpo em Markdown alem (ou no lugar) do PDF.
 *
 * O PDF continua sendo o documento assinado; o Markdown e a versao legivel na
 * tela, pesquisavel e acessivel. Por isso os campos de arquivo passam a
 * aceitar nulo: uma ata pode existir so com o texto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meeting_minutes', function (Blueprint $table) {
            $table->longText('body')->nullable()->after('summary');
        });

        Schema::table('meeting_minutes', function (Blueprint $table) {
            $table->string('file_path')->nullable()->change();
            $table->string('file_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('meeting_minutes', function (Blueprint $table) {
            $table->dropColumn('body');
        });
    }
};
