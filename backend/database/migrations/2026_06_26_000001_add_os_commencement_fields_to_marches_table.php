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
            $table->date('date_notification_marche')->nullable()->after('date_approbation');
            $table->string('os_numero')->nullable()->after('date_notification_marche');
            $table->date('os_date_signature')->nullable()->after('os_numero');
            $table->date('os_date_effet')->nullable()->after('os_date_signature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marches', function (Blueprint $table) {
            $table->dropColumn([
                'date_notification_marche',
                'os_numero',
                'os_date_signature',
                'os_date_effet',
            ]);
        });
    }
};
