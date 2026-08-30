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
            if (!Schema::hasColumn('aoos', 'date_decision_nomination')) {
                $table->date('date_decision_nomination')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aoos', function (Blueprint $table) {
            if (Schema::hasColumn('aoos', 'date_decision_nomination')) {
                $table->dropColumn('date_decision_nomination');
            }
        });
    }
};
