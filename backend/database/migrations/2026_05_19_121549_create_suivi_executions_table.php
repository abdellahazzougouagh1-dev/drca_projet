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
        Schema::create('suivi_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete();
            $table->date('date_debut')->nullable();
            $table->date('date_fin_previsionnelle')->nullable();
            $table->integer('avancement_pourcentage')->default(0);
            $table->enum('statut_execution', ['Non démarré', 'En cours', 'Bloqué', 'Terminé'])->default('Non démarré');
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suivi_executions');
    }
};
