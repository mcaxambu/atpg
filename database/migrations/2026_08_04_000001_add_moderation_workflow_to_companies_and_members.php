<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Introduz o fluxo de moderacao (pendente / aprovado / rejeitado) para empresas
 * e membros, alem de exclusao logica e indices para as colunas de filtro.
 *
 * Antes existia apenas `is_active`, que misturava "aprovado" com "publicado" e
 * deixava os cadastros publicos de membro num limbo sem fila no painel.
 */
return new class extends Migration
{
    private const TABLES = ['companies', 'members'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'status')) {
                    $blueprint->string('status', 16)->default('pending')->after('is_active');
                }

                if (! Schema::hasColumn($table, 'rejection_reason')) {
                    $blueprint->text('rejection_reason')->nullable()->after('status');
                }

                if (! Schema::hasColumn($table, 'reviewed_at')) {
                    $blueprint->timestamp('reviewed_at')->nullable()->after('rejection_reason');
                }

                if (! Schema::hasColumn($table, 'reviewed_by')) {
                    $blueprint->foreignId('reviewed_by')->nullable()->after('reviewed_at')
                        ->constrained('users')->nullOnDelete();
                }

                if (! Schema::hasColumn($table, 'deleted_at')) {
                    $blueprint->softDeletes();
                }
            });

            // Backfill: o que ja estava ativo estava, na pratica, aprovado.
            DB::table($table)->where('is_active', true)->update([
                'status' => 'approved',
                'reviewed_at' => now(),
            ]);

            DB::table($table)->where('is_active', false)->update(['status' => 'pending']);

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->index(['status', 'is_active'], "{$table}_status_is_active_index");
            });
        }

        if (! Schema::hasColumn('members', 'registration_source')) {
            Schema::table('members', function (Blueprint $blueprint) {
                $blueprint->string('registration_source', 32)->default('admin')->after('is_active');
            });
        }

        Schema::table('posts', function (Blueprint $blueprint) {
            $blueprint->index(['is_published', 'published_at'], 'posts_published_index');
        });

        Schema::table('events', function (Blueprint $blueprint) {
            $blueprint->index(['is_published', 'event_date'], 'events_published_index');
        });

        Schema::table('cms_items', function (Blueprint $blueprint) {
            $blueprint->index(['module', 'is_active', 'position'], 'cms_items_module_active_index');
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropIndex("{$table}_status_is_active_index");
                $blueprint->dropConstrainedForeignId('reviewed_by');
                $blueprint->dropColumn(['status', 'rejection_reason', 'reviewed_at', 'deleted_at']);
            });
        }

        Schema::table('members', function (Blueprint $blueprint) {
            $blueprint->dropColumn('registration_source');
        });

        Schema::table('posts', fn (Blueprint $blueprint) => $blueprint->dropIndex('posts_published_index'));
        Schema::table('events', fn (Blueprint $blueprint) => $blueprint->dropIndex('events_published_index'));
        Schema::table('cms_items', fn (Blueprint $blueprint) => $blueprint->dropIndex('cms_items_module_active_index'));
    }
};
