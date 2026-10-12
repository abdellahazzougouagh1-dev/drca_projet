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
        Schema::table('consultations', function (Blueprint $table) {
            $table->date('date_reunion')->nullable();
            $table->time('heure_reunion')->nullable();
            $table->string('lieu_reunion')->nullable();
            $table->string('president_commission')->nullable();
            $table->text('membres_commission')->nullable();
            $table->text('observations_commission')->nullable();
        });

        Schema::table('offres', function (Blueprint $table) {
            $table->decimal('montant_apres_verification', 15, 2)->nullable();
            $table->boolean('retenu')->default(false);
            $table->text('observations_offre')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offres', function (Blueprint $table) {
            $table->dropColumn(['montant_apres_verification', 'retenu', 'observations_offre']);
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn([
                'date_reunion', 'heure_reunion', 'lieu_reunion', 
                'president_commission', 'membres_commission', 'observations_commission'
            ]);
        });
    }
};
