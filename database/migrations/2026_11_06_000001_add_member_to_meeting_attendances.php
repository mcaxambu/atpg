<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Associado pessoa fisica na reuniao.
 *
 * Ate aqui a unidade da presenca era a empresa: `company_id` obrigatorio. Mas
 * a associacao tem membro sem empresa, e ele vale o mesmo numa reuniao —
 * convocacao, confirmacao, check-in e quorum (decisao do usuario em 06/11).
 *
 * A linha passa a apontar para UM dos dois: empresa OU membro, nunca os dois,
 * nunca nenhum. A regra fica no banco tambem, porque uma linha sem dono nao
 * tem conserto depois — ela aparece como presente sem nome.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->after('company_id')
                ->constrained('members')->cascadeOnDelete();

            $table->unique(['meeting_id', 'member_id']);
        });

        // A coluna vira nulavel em instrucao separada: dentro do mesmo
        // Blueprint o indice unico antigo atrapalha a alteracao.
        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->change();
        });

        $this->aplicarRegraDeUmDono();
    }

    public function down(): void
    {
        $this->removerRegraDeUmDono();

        // Linhas de membro nao cabem no formato antigo: sao exatamente as que
        // ficariam sem dono.
        DB::table('meeting_attendances')->whereNotNull('member_id')->delete();

        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->dropUnique(['meeting_id', 'member_id']);
            $table->dropConstrainedForeignId('member_id');
        });

        Schema::table('meeting_attendances', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
        });
    }

    private function aplicarRegraDeUmDono(): void
    {
        try {
            DB::statement(
                'ALTER TABLE meeting_attendances ADD CONSTRAINT meeting_attendances_um_dono '.
                'CHECK ((company_id IS NULL) <> (member_id IS NULL))'
            );
        } catch (\Throwable $erro) {
            // SQLite (usado nos testes) nao aceita adicionar CHECK depois. A
            // mesma regra e garantida no modelo, que e por onde tudo passa.
            report($erro);
        }
    }

    private function removerRegraDeUmDono(): void
    {
        try {
            DB::statement('ALTER TABLE meeting_attendances DROP CONSTRAINT meeting_attendances_um_dono');
        } catch (\Throwable $erro) {
            report($erro);
        }
    }
};
