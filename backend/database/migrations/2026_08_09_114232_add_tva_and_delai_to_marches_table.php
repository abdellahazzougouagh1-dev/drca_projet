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
        Schema::table('marches', function (Blueprint $table) {
            $table->decimal('taux_tva', 5, 2)->nullable()->default(20.00);
            $table->integer('delai_execution')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marches', function (Blueprint $table) {
            $table->dropColumn(['taux_tva', 'delai_execution']);
        });
    }
};
