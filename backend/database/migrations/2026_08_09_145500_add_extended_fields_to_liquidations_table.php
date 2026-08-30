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
        Schema::table('liquidations', function (Blueprint $table) {
            // Infos Liquidation
            $table->string('num_liquidation')->nullable()->after('id');
            $table->string('exercice_budgetaire')->nullable()->after('num_liquidation');
            $table->string('objet_liquidation')->nullable()->after('exercice_budgetaire');
            
            // Service Fait
            $table->date('date_debut_prestations')->nullable()->after('reference_service_fait');
            $table->date('date_fin_prestations')->nullable()->after('date_debut_prestations');
            $table->decimal('pourcentage_execution', 5, 2)->nullable()->after('date_fin_prestations');
            $table->string('fonction_agent')->nullable()->after('agent_responsable');
            $table->string('service_agent')->nullable()->after('fonction_agent');

            // RÃ©ception
            $table->string('type_reception')->nullable()->after('reference_pv_reception');
            $table->string('commission_reception')->nullable();
            $table->string('president_commission')->nullable();
            $table->string('membres_commission')->nullable();
            $table->string('resultat_reception')->nullable();
            $table->text('reserves_reception')->nullable();
            $table->date('date_levee_reserves')->nullable();

            // Facture
            $table->string('objet_facture')->nullable()->after('date_reception_facture');
            $table->string('periode_facture')->nullable()->after('objet_facture');
            $table->date('echeance_facture')->nullable()->after('periode_facture');
            $table->string('reference_facture_fournisseur')->nullable()->after('echeance_facture');

            // DÃ©compte / Finances
            $table->date('periode_du')->nullable()->after('type_decompte');
            $table->date('periode_au')->nullable()->after('periode_du');
            $table->decimal('montant_brut_ht', 15, 2)->default(0)->after('periode_au');
            $table->decimal('montant_brut_ttc', 15, 2)->default(0)->after('montant_brut_ht');
            $table->decimal('retenue_garantie', 15, 2)->default(0)->after('montant_brut_ttc');
            $table->decimal('penalites_retard', 15, 2)->default(0)->after('retenue_garantie');
            $table->decimal('avances_a_recuperer', 15, 2)->default(0)->after('penalites_retard');
            $table->decimal('autres_retenues', 15, 2)->default(0)->after('avances_a_recuperer');
            $table->decimal('autres_deductions', 15, 2)->default(0)->after('autres_retenues');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('liquidations', function (Blueprint $table) {
            //
        });
    }
};
