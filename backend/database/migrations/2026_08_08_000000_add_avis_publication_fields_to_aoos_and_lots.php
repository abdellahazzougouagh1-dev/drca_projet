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
        Schema::table('aoos', function (Blueprint $table) {
            $table->text('objet_ar')->nullable()->after('objet');
            $table->text('lieu_ouverture_ar')->nullable()->after('lieu_ouverture');
            $table->string('articles_rc')->default('08 et 10')->after('heure_ouverture');
        });

        Schema::table('lots', function (Blueprint $table) {
            $table->text('objet_lot_ar')->nullable()->after('objet_lot');
            $table->decimal('cautionnement_provisoire', 15, 2)->nullable()->after('estimation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aoos', function (Blueprint $table) {
            $table->dropColumn(['objet_ar', 'lieu_ouverture_ar', 'articles_rc']);
        });

        Schema::table('lots', function (Blueprint $table) {
            $table->dropColumn(['objet_lot_ar', 'cautionnement_provisoire']);
        });
    }
};
