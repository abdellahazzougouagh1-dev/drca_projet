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
            $table->string('num_decision')->nullable()->after('os_date_effet');
            $table->date('date_decision')->nullable()->after('num_decision');
            $table->date('date_reunion_commission')->nullable()->after('date_decision');
            $table->time('heure_reunion_commission')->nullable()->after('date_reunion_commission');
            $table->string('lieu_reunion_commission')->nullable()->after('heure_reunion_commission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marches', function (Blueprint $table) {
            $table->dropColumn([
                'num_decision',
                'date_decision',
                'date_reunion_commission',
                'heure_reunion_commission',
                'lieu_reunion_commission',
            ]);
        });
    }
};
