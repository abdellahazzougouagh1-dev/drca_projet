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
        Schema::create('execution_evenements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('suivi_execution_id')->constrained('suivi_executions')->cascadeOnDelete();
            $table->enum('type_evenement', ['Jalon', 'Incident', 'Note']);
            $table->text('description');
            $table->date('date_evenement');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('execution_evenements');
    }
};
