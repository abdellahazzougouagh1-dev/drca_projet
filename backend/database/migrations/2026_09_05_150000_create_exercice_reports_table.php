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
        if (!Schema::hasTable('exercice_reports')) {
            Schema::create('exercice_reports', function (Blueprint $table) {
                $table->id();
                $table->integer('exercice')->unique();
                $table->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
                $table->foreignId('notification_ligne_id')->constrained('notification_lignes')->cascadeOnDelete();
                $table->decimal('montant', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercice_reports');
    }
};
