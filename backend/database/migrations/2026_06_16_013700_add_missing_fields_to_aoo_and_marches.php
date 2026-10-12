<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aoos', function (Blueprint $table) {
            $table->string('president_commission')->nullable();
            $table->json('membres_commission')->nullable();
            $table->text('etat_avancement')->nullable(); // Pour la phase 4: Analyse
        });

        Schema::table('marches', function (Blueprint $table) {
            $table->string('agent_suivi')->nullable(); // Pour la phase 3: Exécution
            $table->date('date_reception_finale')->nullable(); // Pour la phase 4: Réception
            $table->json('commission_reception')->nullable(); // Pour la phase 4: Réception
        });
    }

    public function down(): void
    {
        Schema::table('aoos', function (Blueprint $table) {
            $table->dropColumn(['president_commission', 'membres_commission', 'etat_avancement']);
        });

        Schema::table('marches', function (Blueprint $table) {
            $table->dropColumn(['agent_suivi', 'date_reception_finale', 'commission_reception']);
        });
    }
};
