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
        Schema::table('lots', function (Blueprint $table) {
            if (!Schema::hasColumn('lots', 'montant_attribue_ht')) {
                $table->decimal('montant_attribue_ht', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('lots', 'tva_taux_attribue')) {
                $table->decimal('tva_taux_attribue', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('lots', 'montant_attribue_ttc')) {
                $table->decimal('montant_attribue_ttc', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('lots', 'delai_execution_jours')) {
                $table->integer('delai_execution_jours')->nullable();
            }
            if (!Schema::hasColumn('lots', 'date_debut_prevue')) {
                $table->date('date_debut_prevue')->nullable();
            }
            if (!Schema::hasColumn('lots', 'date_fin_prevue')) {
                $table->date('date_fin_prevue')->nullable();
            }
            if (!Schema::hasColumn('lots', 'observations_attribution')) {
                $table->text('observations_attribution')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $cols = array_filter([
                'montant_attribue_ht',
                'tva_taux_attribue',
                'montant_attribue_ttc',
                'delai_execution_jours',
                'date_debut_prevue',
                'date_fin_prevue',
                'observations_attribution'
            ], function($col) {
                return Schema::hasColumn('lots', $col);
            });
            if (!empty($cols)) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
