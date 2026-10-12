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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            // Un Budget appartient à une Consultation
            $table->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete();
            
            $table->string('art');
            $table->string('par');
            $table->string('lig');
            $table->string('code_imputation');
            $table->integer('exercice_budgetaire');
            
            $table->decimal('montant_estimatif_ht', 15, 2);
            $table->decimal('tva', 5, 2); // Pourcentage de TVA (ex: 20.00)
            $table->decimal('montant_ttc', 15, 2); // Calculé automatiquement
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
