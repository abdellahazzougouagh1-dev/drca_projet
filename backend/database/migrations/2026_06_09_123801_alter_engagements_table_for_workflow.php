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
        Schema::table('engagements', function (Blueprint $table) {
            $table->renameColumn('numero_engagement', 'numero');
            $table->renameColumn('montant_engagement', 'montant_global');
            $table->foreignId('type_engagement_id')->nullable()->constrained('type_engagements')->nullOnDelete();
            $table->text('objet')->nullable();
            $table->string('statut_actuel')->nullable();
            $table->json('data_specifique')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('engagements', function (Blueprint $table) {
            $table->dropForeign(['type_engagement_id']);
            $table->dropColumn(['type_engagement_id', 'objet', 'statut_actuel', 'data_specifique']);
            $table->renameColumn('numero', 'numero_engagement');
            $table->renameColumn('montant_global', 'montant_engagement');
        });
    }
};
