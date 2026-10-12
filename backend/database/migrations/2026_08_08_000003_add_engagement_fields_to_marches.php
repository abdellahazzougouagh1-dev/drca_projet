<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('marches', function (Blueprint $table) {
            if (!Schema::hasColumn('marches', 'statut_acte_engagement')) {
                $table->string('statut_acte_engagement')->default('brouillon')->after('statut');
            }
            if (!Schema::hasColumn('marches', 'statut_marche')) {
                $table->string('statut_marche')->default('brouillon')->after('statut_acte_engagement');
            }
            if (!Schema::hasColumn('marches', 'statut_approbation')) {
                $table->string('statut_approbation')->default('brouillon')->after('statut_marche');
            }
            if (!Schema::hasColumn('marches', 'statut_os')) {
                $table->string('statut_os')->default('brouillon')->after('statut_approbation');
            }
            if (!Schema::hasColumn('marches', 'date_effet_os')) {
                $table->date('date_effet_os')->nullable()->after('os_date_effet');
            }
            if (!Schema::hasColumn('marches', 'montant_caution_definitive')) {
                $table->decimal('montant_caution_definitive', 15, 2)->nullable()->after('montant');
            }
            if (!Schema::hasColumn('marches', 'date_accuse_os')) {
                $table->date('date_accuse_os')->nullable()->after('date_effet_os');
            }
            if (!Schema::hasColumn('marches', 'date_accuse_approbation')) {
                $table->date('date_accuse_approbation')->nullable()->after('date_approbation');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marches', function (Blueprint $table) {
            $table->dropColumn([
                'statut_acte_engagement',
                'statut_marche',
                'statut_approbation',
                'statut_os',
                'date_effet_os',
                'montant_caution_definitive',
                'date_accuse_os',
                'date_accuse_approbation',
            ]);
        });
    }
};
