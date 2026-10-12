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
        Schema::create('marches', function (Blueprint $table) {
            $table->id();
            $table->string('num_marche')->unique();
            $table->foreignId('aoo_id')->constrained('aoos')->onDelete('cascade');
            $table->string('lot')->nullable();
            $table->string('titulaire');
            $table->decimal('montant', 15, 2);
            $table->date('date_signature')->nullable();
            $table->date('date_approbation')->nullable();
            $table->string('statut')->default('en_creation');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marches');
    }
};
