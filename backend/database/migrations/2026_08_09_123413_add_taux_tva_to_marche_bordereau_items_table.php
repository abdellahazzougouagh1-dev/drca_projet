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
        Schema::table('marche_bordereau_items', function (Blueprint $table) {
            $table->decimal('taux_tva', 5, 2)->default(20);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marche_bordereau_items', function (Blueprint $table) {
            $table->dropColumn('taux_tva');
        });
    }
};
