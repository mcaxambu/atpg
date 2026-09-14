<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vinculo do usuario com o cadastro dele no diretorio.
 *
 * O papel "member" so faz sentido com este vinculo: e o registro que ele vai
 * poder editar. Se o cadastro sai do ar, o acesso vai junto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'member_id')) {
                $table->foreignId('member_id')->nullable()->after('company_id')
                    ->constrained()->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_id');
        });
    }
};
