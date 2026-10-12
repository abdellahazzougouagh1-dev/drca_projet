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
        Schema::table('consultations', function (Blueprint $table) {
            if (!Schema::hasColumn('consultations', 'notification_ligne_id')) {
                $table->foreignId('notification_ligne_id')->nullable()->constrained('notification_lignes')->nullOnDelete();
            }
        });

        Schema::table('marches', function (Blueprint $table) {
            if (!Schema::hasColumn('marches', 'notification_ligne_id')) {
                $table->foreignId('notification_ligne_id')->nullable()->constrained('notification_lignes')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marches', function (Blueprint $table) {
            if (Schema::hasColumn('marches', 'notification_ligne_id')) {
                $table->dropForeign(['notification_ligne_id']);
                $table->dropColumn('notification_ligne_id');
            }
        });

        Schema::table('consultations', function (Blueprint $table) {
            if (Schema::hasColumn('consultations', 'notification_ligne_id')) {
                $table->dropForeign(['notification_ligne_id']);
                $table->dropColumn('notification_ligne_id');
            }
        });
    }
};
