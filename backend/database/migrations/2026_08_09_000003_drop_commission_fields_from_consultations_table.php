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
            $table->dropColumn([
                'date_reunion',
                'heure_reunion',
                'lieu_reunion',
                'president_commission',
                'membres_commission',
                'observations_commission',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->date('date_reunion')->nullable();
            $table->string('heure_reunion')->nullable();
            $table->string('lieu_reunion')->nullable();
            $table->string('president_commission')->nullable();
            $table->json('membres_commission')->nullable();
            $table->text('observations_commission')->nullable();
        });
    }
};
