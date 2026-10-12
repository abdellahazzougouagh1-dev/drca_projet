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
        Schema::create('document_generes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etape_instance_id')->constrained('etape_instances')->cascadeOnDelete();
            $table->foreignId('document_modele_id')->constrained('document_modeles')->cascadeOnDelete();
            $table->string('chemin_pdf');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_generes');
    }
};
