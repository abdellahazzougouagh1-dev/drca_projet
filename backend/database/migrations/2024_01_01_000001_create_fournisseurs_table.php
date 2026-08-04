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
        Schema::create('fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->string('raison_sociale');
            $table->string('ice')->unique();
            $table->string('if'); // Identifiant Fiscal (Obligatoire)
            $table->string('patente')->nullable();
            $table->string('rc')->nullable(); // Registre de Commerce
            $table->text('adresse');
            $table->string('ville');
            $table->string('telephone');
            $table->string('email');
            $table->string('representant')->nullable();
            $table->string('domaine_activite');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fournisseurs');
    }
};
