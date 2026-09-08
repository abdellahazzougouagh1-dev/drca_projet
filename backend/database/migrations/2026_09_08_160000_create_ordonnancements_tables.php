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
        Schema::create('ordonnancements', function (Blueprint $table) {
            $table->id();
            $table->string('num_ordonnancement')->unique(); // ex: ORD-2024-001
            $table->string('num_op')->nullable(); // ex: OP N° 38
            $table->string('exercice', 10)->default('2024');
            $table->date('date_ordonnancement');
            
            // Relations
            $table->foreignId('liquidation_id')->nullable()->constrained('liquidations')->nullOnDelete();
            $table->foreignId('marche_id')->nullable()->constrained('marches')->nullOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
            $table->foreignId('fournisseur_id')->nullable()->constrained('fournisseurs')->nullOnDelete();
            $table->foreignId('notification_ligne_id')->nullable()->constrained('notification_lignes')->nullOnDelete();

            // Informations administratives
            $table->string('type_procedure')->default('Bon de commande'); // Marché, Bon de commande, Convention
            $table->string('reference')->nullable(); // BC N°01/INV/2024/DRCA-RSK
            $table->string('beneficiaire_nom')->nullable();
            $table->string('budget_type')->default('Investissement'); // Investissement, Fonctionnement
            $table->string('creance')->default('Reste à payer');
            
            // Imputation budgétaire
            $table->string('code_imputation')->nullable(); // 225320
            $table->string('article')->nullable(); // 415
            $table->string('paragraphe')->nullable(); // 20
            $table->string('ligne')->nullable(); // 13
            $table->string('sous_ligne')->nullable(); // 0
            $table->text('intitule_depense')->nullable();

            // Montants
            $table->decimal('montant_brut', 15, 2)->default(0); // Montant TTC de la liquidation
            $table->decimal('retenue_tva', 15, 2)->default(0);
            $table->decimal('retenue_ias', 15, 2)->default(0);
            $table->decimal('autres_retenues', 15, 2)->default(0);
            $table->decimal('net_a_payer', 15, 2)->default(0);

            // Crédits
            $table->decimal('credit_consolide', 15, 2)->default(0); // CC
            $table->decimal('credit_neuf', 15, 2)->default(0); // CN
            $table->decimal('ras_total', 15, 2)->default(0); // RAS
            $table->decimal('rap_total', 15, 2)->default(0); // RAP

            // Statuts
            $table->string('statut')->default('À payer'); // Brouillon, À payer, Ordonnancé, Transmis au trésorier, Payé, Rejeté, Annulé
            $table->date('date_transmission_tresorier')->nullable();
            $table->date('date_paiement')->nullable();
            $table->text('observations')->nullable();
            $table->text('motif_rejet')->nullable();

            $table->timestamps();
        });

        Schema::create('ordonnancement_ordres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordonnancement_id')->constrained('ordonnancements')->cascadeOnDelete();
            $table->string('num_ordre')->nullable(); // OP N° 38, OV N° 39, OI N° 40
            $table->string('type_mouvement'); // Paiement fournisseur, Retenue à la source TVA, Retenue à la source IAS, etc.
            $table->string('mode_paiement')->default('Virement'); // Virement, Chèque, etc.
            $table->string('beneficiaire');
            $table->string('rib_compte')->nullable();
            $table->string('banque_agence')->nullable();
            $table->string('creance')->default('Reste à payer');
            $table->decimal('montant', 15, 2)->default(0);
            $table->string('statut')->default('À payer'); // À payer, Payé, Annulé
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        Schema::create('ordonnancement_historiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordonnancement_id')->constrained('ordonnancements')->cascadeOnDelete();
            $table->string('action'); // Dossier créé, Paiements générés, OP N°38 généré, etc.
            $table->string('auteur')->default('admin');
            $table->string('statut_precedent')->nullable();
            $table->string('statut_nouveau')->nullable();
            $table->text('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordonnancement_historiques');
        Schema::dropIfExists('ordonnancement_ordres');
        Schema::dropIfExists('ordonnancements');
    }
};
