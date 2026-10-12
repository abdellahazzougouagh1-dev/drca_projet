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
        Schema::create('liquidation_financieres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete();
            $table->decimal('montant_a_payer', 15, 2);
            $table->string('reference_facture')->nullable();
            $table->date('date_facture')->nullable();
            $table->string('ordre_imputation')->nullable();
            $table->string('ordre_paiement')->nullable();
            $table->string('ordre_virement')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidation_financieres');
    }
};
