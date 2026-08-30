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
        Schema::create('liquidation_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liquidation_id')->constrained()->cascadeOnDelete();
            // Link to the initial item, if any.
            $table->foreignId('marche_bordereau_item_id')->nullable()->constrained('marche_bordereau_items')->nullOnDelete();
            
            $table->string('designation');
            $table->string('unite')->nullable();
            $table->decimal('quantite_prevue', 15, 2)->default(0);
            $table->decimal('quantite_executee', 15, 2)->default(0);
            $table->decimal('prix_unitaire_ht', 15, 2)->default(0);
            $table->decimal('montant_ht', 15, 2)->default(0);
            $table->decimal('taux_tva', 5, 2)->default(20);
            $table->decimal('montant_tva', 15, 2)->default(0);
            $table->decimal('montant_ttc', 15, 2)->default(0);
            $table->text('observations')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidation_lignes');
    }
};
