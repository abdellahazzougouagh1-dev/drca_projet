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
        Schema::table('prestations', function (Blueprint $table) {
            if (!Schema::hasColumn('prestations', 'numero_prix')) {
                $table->string('numero_prix')->nullable()->after('consultation_id');
            }
            if (!Schema::hasColumn('prestations', 'specification')) {
                $table->text('specification')->nullable();
            }
            if (!Schema::hasColumn('prestations', 'garantie_exigee')) {
                $table->string('garantie_exigee')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $cols = array_filter(['numero_prix', 'specification', 'garantie_exigee'], function($col) {
                return Schema::hasColumn('prestations', $col);
            });
            if (!empty($cols)) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
