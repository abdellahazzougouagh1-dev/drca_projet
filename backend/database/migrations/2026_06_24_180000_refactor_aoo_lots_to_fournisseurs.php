<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ouverture_plis_concurrents', function (Blueprint $table) {
            if (!Schema::hasColumn('ouverture_plis_concurrents', 'fournisseur_id')) {
                $table->foreignId('fournisseur_id')->nullable()->after('aoo_id')->constrained('fournisseurs')->nullOnDelete();
            }
        });

        Schema::table('lots', function (Blueprint $table) {
            if (Schema::hasColumn('lots', 'attributaire_concurrent_id') && !Schema::hasColumn('lots', 'attributaire_fournisseur_id')) {
                $table->foreignId('attributaire_fournisseur_id')->nullable()->after('estimation')->constrained('fournisseurs')->nullOnDelete();
            } elseif (!Schema::hasColumn('lots', 'attributaire_fournisseur_id')) {
                $table->foreignId('attributaire_fournisseur_id')->nullable()->after('estimation')->constrained('fournisseurs')->nullOnDelete();
            }
        });

        Schema::table('marches', function (Blueprint $table) {
            if (!Schema::hasColumn('marches', 'lot_id')) {
                $table->foreignId('lot_id')->nullable()->after('aoo_id')->constrained('lots')->nullOnDelete();
            }
            if (!Schema::hasColumn('marches', 'fournisseur_id')) {
                $table->foreignId('fournisseur_id')->nullable()->after('lot_id')->constrained('fournisseurs')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('marches', function (Blueprint $table) {
            if (Schema::hasColumn('marches', 'fournisseur_id')) {
                $table->dropForeign(['fournisseur_id']);
                $table->dropColumn('fournisseur_id');
            }
            if (Schema::hasColumn('marches', 'lot_id')) {
                $table->dropForeign(['lot_id']);
                $table->dropColumn('lot_id');
            }
        });

        Schema::table('lots', function (Blueprint $table) {
            if (Schema::hasColumn('lots', 'attributaire_fournisseur_id')) {
                $table->dropForeign(['attributaire_fournisseur_id']);
                $table->dropColumn('attributaire_fournisseur_id');
            }
        });

        Schema::table('ouverture_plis_concurrents', function (Blueprint $table) {
            if (Schema::hasColumn('ouverture_plis_concurrents', 'fournisseur_id')) {
                $table->dropForeign(['fournisseur_id']);
                $table->dropColumn('fournisseur_id');
            }
        });
    }
};
