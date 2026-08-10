<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Introduz papeis no painel e o vinculo do usuario com a empresa dele.
 *
 * A senha passa a aceitar nulo: o responsavel pela empresa recebe um convite
 * ao ser aprovado e so define a senha ao aceitar. Ate la o acesso existe mas
 * nao esta ativado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 16)->default('admin')->after('email');
            }

            if (! Schema::hasColumn('users', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('role')
                    ->constrained()->cascadeOnDelete();
            }

            if (! Schema::hasColumn('users', 'invited_at')) {
                $table->timestamp('invited_at')->nullable()->after('company_id');
            }
        });

        // Quem ja existia e da equipe da associacao.
        DB::table('users')->whereNull('role')->orWhere('role', '')->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['role', 'invited_at']);
        });
    }
};
