<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permissao por modulo no painel administrativo.
 *
 * Nulo significa acesso total: e o que todo admin ja tinha antes desta coluna
 * existir, entao os usuarios atuais continuam como estavam. Um array limita o
 * usuario aos modulos listados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'abilities')) {
                $table->json('abilities')->nullable()->after('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('abilities');
        });
    }
};
