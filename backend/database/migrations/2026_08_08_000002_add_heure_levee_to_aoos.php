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
            if (!Schema::hasColumn('aoos', 'heure_levee')) {
                $table->string('heure_levee')->nullable()->after('heure_ouverture');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aoos', function (Blueprint $table) {
            if (Schema::hasColumn('aoos', 'heure_levee')) {
                $table->dropColumn('heure_levee');
            }
        });
    }
};
