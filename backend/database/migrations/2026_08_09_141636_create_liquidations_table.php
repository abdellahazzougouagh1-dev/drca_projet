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
        Schema::create('liquidations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marche_id')->constrained()->cascadeOnDelete();
            
            // Service Fait
            $table->enum('type_execution', ['totale', 'partielle'])->default('totale');
            $table->string('reference_service_fait')->nullable();
            $table->date('date_service_fait')->nullable();
            $table->date('date_reception')->nullable();
            $table->string('reference_pv_reception')->nullable();
            $table->string('agent_responsable')->nullable();

            // Facture
            $table->string('num_facture')->nullable();
            $table->date('date_facture')->nullable();
            $table->date('date_reception_facture')->nullable();

            // Décompte
            $table->string('num_decompte')->nullable();
            $table->date('date_decompte')->nullable();
            $table->enum('type_decompte', ['provisoire', 'definitif', 'situation'])->default('provisoire');

            // Finances
            $table->decimal('montant_ht', 15, 2)->default(0);
            $table->decimal('taux_tva', 5, 2)->default(20);
            $table->decimal('montant_tva', 15, 2)->default(0);
            $table->decimal('montant_ttc', 15, 2)->default(0);
            $table->decimal('retenues', 15, 2)->default(0);
            $table->decimal('net_a_payer', 15, 2)->default(0);

            // Workflow
            $table->string('statut')->default('BROUILLON');
            $table->text('motif_rejet')->nullable();
            $table->text('observations')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidations');
    }
};
