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
        if (Schema::hasTable('commission_receptions')) {
            Schema::table('commission_receptions', function (Blueprint $table) {
                if (!Schema::hasColumn('commission_receptions', 'type_reception')) {
                    $table->string('type_reception')->default('définitive')->after('numero_decision');
                }
                if (!Schema::hasColumn('commission_receptions', 'periode_du')) {
                    $table->date('periode_du')->nullable()->after('type_reception');
                }
                if (!Schema::hasColumn('commission_receptions', 'periode_au')) {
                    $table->date('periode_au')->nullable()->after('periode_du');
                }
                if (!Schema::hasColumn('commission_receptions', 'prestations_receptionnees')) {
                    $table->json('prestations_receptionnees')->nullable()->after('periode_au');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('commission_receptions')) {
            Schema::table('commission_receptions', function (Blueprint $table) {
                if (Schema::hasColumn('commission_receptions', 'type_reception')) $table->dropColumn('type_reception');
                if (Schema::hasColumn('commission_receptions', 'periode_du')) $table->dropColumn('periode_du');
                if (Schema::hasColumn('commission_receptions', 'periode_au')) $table->dropColumn('periode_au');
                if (Schema::hasColumn('commission_receptions', 'prestations_receptionnees')) $table->dropColumn('prestations_receptionnees');
            });
        }
    }
};
