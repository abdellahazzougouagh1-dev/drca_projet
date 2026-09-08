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
        Schema::table('lots', function (Blueprint $table) {
            if (!Schema::hasColumn('lots', 'notification_ligne_id')) {
                $table->foreignId('notification_ligne_id')->nullable()->after('cautionnement_provisoire')->constrained('notification_lignes')->nullOnDelete();
            }
            if (!Schema::hasColumn('lots', 'art')) {
                $table->string('art')->nullable()->after('notification_ligne_id');
            }
            if (!Schema::hasColumn('lots', 'par')) {
                $table->string('par')->nullable()->after('art');
            }
            if (!Schema::hasColumn('lots', 'lig')) {
                $table->string('lig')->nullable()->after('par');
            }
            if (!Schema::hasColumn('lots', 'imputation')) {
                $table->string('imputation')->nullable()->after('lig');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            if (Schema::hasColumn('lots', 'notification_ligne_id')) {
                $table->dropForeign(['notification_ligne_id']);
                $table->dropColumn('notification_ligne_id');
            }
            $columns = ['art', 'par', 'lig', 'imputation'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('lots', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
