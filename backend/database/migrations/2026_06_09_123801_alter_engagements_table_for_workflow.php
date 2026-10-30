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

        if (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE engagements ALTER COLUMN type_engagement DROP NOT NULL');
        } else {
            \Illuminate\Support\Facades\Schema::table('engagements', function (Blueprint $table) {
                $table->string('type_engagement')->nullable()->change();
            });
        }
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
