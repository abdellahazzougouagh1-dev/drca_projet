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
            if (!Schema::hasColumn('aoos', 'notification_ligne_id')) {
                $table->foreignId('notification_ligne_id')
                      ->nullable()
                      ->after('lig')
                      ->constrained('notification_lignes')
                      ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aoos', function (Blueprint $table) {
            if (Schema::hasColumn('aoos', 'notification_ligne_id')) {
                $table->dropForeign(['notification_ligne_id']);
                $table->dropColumn('notification_ligne_id');
            }
        });
    }
};
