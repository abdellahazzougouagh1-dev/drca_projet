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
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->string('numero_consultation')->unique();
            $table->integer('annee');
            $table->date('date_consultation');
            $table->string('objet_consultation');
            $table->text('description_detaillee')->nullable();
            $table->string('categorie');
            $table->string('type_prestation');
            $table->enum('mode_engagement', ['BC', 'Convention']);
            $table->enum('type_budget', ['Investissement', 'Fonctionnement']);
            $table->integer('delai_execution'); // en jours
            $table->string('statut_dossier')->default('Programmation');
            
            // Clé étrangère pour le fournisseur (lié après attribution)
            $table->foreignId('fournisseur_id')->nullable()->constrained('fournisseurs')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
