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
            $table->string('os_arret_numero')->nullable();
            $table->date('os_arret_date_signature')->nullable();
            $table->date('os_arret_date_effet')->nullable();
            $table->string('os_arret_motif')->nullable();

            $table->string('os_reprise_numero')->nullable();
            $table->date('os_reprise_date_signature')->nullable();
            $table->date('os_reprise_date_effet')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marches', function (Blueprint $table) {
            $table->dropColumn([
                'os_arret_numero',
                'os_arret_date_signature',
                'os_arret_date_effet',
                'os_arret_motif',
                'os_reprise_numero',
                'os_reprise_date_signature',
                'os_reprise_date_effet',
            ]);
        });
    }
};
