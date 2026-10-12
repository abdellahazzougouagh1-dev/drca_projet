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
        Schema::create('etape_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_engagement_id')->constrained('type_engagements')->cascadeOnDelete();
            $table->string('code');
            $table->string('libelle');
            $table->integer('ordre');
            $table->boolean('est_bloquante')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etape_workflows');
    }
};
