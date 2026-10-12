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
            $table->string('numero_avis')->nullable()->after('numero_decision');
            $table->date('date_limite_devis')->nullable()->after('numero_avis');
            $table->string('heure_limite_devis')->nullable()->after('date_limite_devis');
            $table->string('lieu_execution')->nullable()->after('heure_limite_devis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn(['numero_avis', 'date_limite_devis', 'heure_limite_devis', 'lieu_execution']);
        });
    }
};
