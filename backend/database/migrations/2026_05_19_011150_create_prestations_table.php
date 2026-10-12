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
        Schema::create('prestations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete();
            $table->string('designation');
            $table->string('unite');
            $table->decimal('quantite', 10, 2);
            $table->decimal('prix_unitaire_ht', 15, 2);
            $table->decimal('montant_ht', 15, 2); // Quantité * Prix unitaire
            $table->decimal('tva', 5, 2); // Pourcentage
            $table->decimal('montant_ttc', 15, 2); // Calculé
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestations');
    }
};
