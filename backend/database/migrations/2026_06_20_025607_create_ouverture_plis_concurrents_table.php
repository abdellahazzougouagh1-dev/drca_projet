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
        Schema::create('ouverture_plis_concurrents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aoo_id')->constrained('aoos')->onDelete('cascade');
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->onDelete('restrict');
            $table->string('nom_soumissionnaire')->nullable();
            $table->boolean('dh')->default(false);
            $table->boolean('cp')->default(false);
            $table->boolean('rc')->default(false);
            $table->boolean('cps')->default(false);
            $table->boolean('m_hum')->default(false);
            $table->decimal('montant_engagement', 15, 2)->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->unique(['aoo_id', 'fournisseur_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ouverture_plis_concurrents');
    }
};
