<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('registre_engagements', 'numero_rubrique')) {
            Schema::table('registre_engagements', function (Blueprint $table) {
                $table->string('numero_rubrique')->nullable()->after('date_engagement');
            });
        }

        if (Schema::hasTable('registre_engagements') && DB::table('registre_engagements')->count() > 0) {
            try {
                DB::statement('
                    UPDATE registre_engagements r
                    SET numero_rubrique = c.numero_engagement
                    FROM consultations c
                    WHERE r.consultation_id = c.id
                ');
            } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('registre_engagements', 'numero_rubrique')) {
            Schema::table('registre_engagements', function (Blueprint $table) {
                $table->dropColumn('numero_rubrique');
            });
        }
    }
};
