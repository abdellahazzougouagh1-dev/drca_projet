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
        Schema::create('notification_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
            
            $table->string('article');
            $table->string('paragraphe');
            $table->string('ligne_budgetaire');
            $table->string('libelle');
            
            $table->decimal('reports', 15, 2)->default(0);
            $table->decimal('diminution_report', 15, 2)->default(0);
            $table->decimal('credits_neufs', 15, 2)->default(0);
            $table->decimal('diminution_credit_neuf', 15, 2)->default(0);
            $table->decimal('credits_engagements', 15, 2)->default(0);
            
            $table->decimal('total_credits', 15, 2)->default(0); // reports + credits_neufs + credits_engagements
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_lignes');
    }
};
