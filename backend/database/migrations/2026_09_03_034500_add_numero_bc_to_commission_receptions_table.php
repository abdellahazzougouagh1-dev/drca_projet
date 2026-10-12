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
        if (Schema::hasTable('commission_receptions') && !Schema::hasColumn('commission_receptions', 'numero_bc')) {
            Schema::table('commission_receptions', function (Blueprint $table) {
                $table->string('numero_bc')->nullable()->after('consultation_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('commission_receptions') && Schema::hasColumn('commission_receptions', 'numero_bc')) {
            Schema::table('commission_receptions', function (Blueprint $table) {
                $table->dropColumn('numero_bc');
            });
        }
    }
};
