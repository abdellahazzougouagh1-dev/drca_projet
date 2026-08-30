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
        if (!Schema::hasTable('commission_receptions')) {
            Schema::create('commission_receptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete();
                $table->string('numero_decision')->nullable();
                $table->date('date_decision')->nullable();
                $table->date('date_reunion')->nullable();
                $table->string('heure_reunion')->nullable();
                $table->string('heure_fin')->nullable();
                $table->string('lieu_reunion')->nullable();
                $table->json('membres_commission')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_receptions');
    }
};
