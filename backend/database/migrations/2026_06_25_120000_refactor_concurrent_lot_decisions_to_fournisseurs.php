<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('concurrent_lot_decisions')) {
            return;
        }

        Schema::table('concurrent_lot_decisions', function (Blueprint $table) {
            if (!Schema::hasColumn('concurrent_lot_decisions', 'aoo_id')) {
                $table->foreignId('aoo_id')->nullable()->after('id')->constrained('aoos')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('concurrent_lot_decisions', 'fournisseur_id')) {
                $table->foreignId('fournisseur_id')->nullable()->after('lot_id')->constrained('fournisseurs')->restrictOnDelete();
            }
        });

        if (Schema::hasColumn('concurrent_lot_decisions', 'concurrent_id')) {
            $rows = DB::table('concurrent_lot_decisions')->get();
            foreach ($rows as $row) {
                $concurrent = DB::table('ouverture_plis_concurrents')->where('id', $row->concurrent_id)->first();
                if ($concurrent) {
                    DB::table('concurrent_lot_decisions')->where('id', $row->id)->update([
                        'aoo_id' => $concurrent->aoo_id,
                        'fournisseur_id' => $concurrent->fournisseur_id,
                    ]);
                }
            }

            Schema::table('concurrent_lot_decisions', function (Blueprint $table) {
                $table->dropForeign(['concurrent_id']);
            });

            Schema::table('concurrent_lot_decisions', function (Blueprint $table) {
                $table->dropUnique('concurrent_lot_decisions_concurrent_id_lot_id_unique');
                $table->dropColumn('concurrent_id');
            });
        }

        if (!$this->indexExists('concurrent_lot_decisions', 'concurrent_lot_decisions_aoo_id_lot_id_fournisseur_id_unique')) {
            Schema::table('concurrent_lot_decisions', function (Blueprint $table) {
                $table->unique(['aoo_id', 'lot_id', 'fournisseur_id'], 'concurrent_lot_decisions_aoo_id_lot_id_fournisseur_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('concurrent_lot_decisions')) {
            return;
        }

        if ($this->indexExists('concurrent_lot_decisions', 'concurrent_lot_decisions_aoo_id_lot_id_fournisseur_id_unique')) {
            Schema::table('concurrent_lot_decisions', function (Blueprint $table) {
                $table->dropUnique('concurrent_lot_decisions_aoo_id_lot_id_fournisseur_id_unique');
            });
        }

        Schema::table('concurrent_lot_decisions', function (Blueprint $table) {
            if (!Schema::hasColumn('concurrent_lot_decisions', 'concurrent_id')) {
                $table->foreignId('concurrent_id')->nullable()->after('id')->constrained('ouverture_plis_concurrents')->cascadeOnDelete();
            }
        });

        Schema::table('concurrent_lot_decisions', function (Blueprint $table) {
            if (Schema::hasColumn('concurrent_lot_decisions', 'fournisseur_id')) {
                $table->dropForeign(['fournisseur_id']);
                $table->dropColumn('fournisseur_id');
            }
            if (Schema::hasColumn('concurrent_lot_decisions', 'aoo_id')) {
                $table->dropForeign(['aoo_id']);
                $table->dropColumn('aoo_id');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

        return count($indexes) > 0;
    }
};
