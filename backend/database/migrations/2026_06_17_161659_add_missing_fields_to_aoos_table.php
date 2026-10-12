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
            $table->string('num_decision_nomination')->nullable()->after('objet');
            $table->date('date_convocation')->nullable()->after('num_decision_nomination');
            $table->string('lieu_ouverture')->nullable()->after('heure_ouverture');
            $table->string('num_aoo_interne')->nullable()->after('num_aoo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aoos', function (Blueprint $table) {
            $table->dropColumn([
                'num_decision_nomination',
                'date_convocation',
                'lieu_ouverture',
                'num_aoo_interne'
            ]);
        });
    }
};
