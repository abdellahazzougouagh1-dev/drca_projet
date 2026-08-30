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
            if (!Schema::hasColumn('aoos', 'rapporteur_commission')) {
                $table->string('rapporteur_commission')->nullable()->after('president_commission');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aoos', function (Blueprint $table) {
            if (Schema::hasColumn('aoos', 'rapporteur_commission')) {
                $table->dropColumn('rapporteur_commission');
            }
        });
    }
};
