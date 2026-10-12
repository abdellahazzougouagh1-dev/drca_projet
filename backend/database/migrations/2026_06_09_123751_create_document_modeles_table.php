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
        Schema::create('document_modeles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etape_workflow_id')->constrained('etape_workflows')->cascadeOnDelete();
            $table->string('code');
            $table->string('libelle');
            $table->string('chemin_blade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_modeles');
    }
};
