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
        Schema::table('lot_items', function (Blueprint $table) {
            $table->decimal('tva_taux', 5, 2)->default(20.00)->after('montant_ht');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lot_items', function (Blueprint $table) {
            $table->dropColumn('tva_taux');
        });
    }
};
