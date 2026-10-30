<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('registre_engagements', 'credit_consolide')) {
            Schema::table('registre_engagements', function (Blueprint $table) {
                $table->decimal('credit_consolide', 15, 2)->nullable()->after('credit_ouvert_ce');
            });
        }

        if (Schema::hasTable('registre_engagements') && DB::table('registre_engagements')->count() > 0) {
            try {
                DB::statement('
                    UPDATE registre_engagements r
                    SET credit_consolide = COALESCE(nl.reports, 0) + COALESCE(nl.credits_neufs, 0)
                    FROM consultations c
                    JOIN notification_lignes nl ON c.notification_ligne_id = nl.id
                    WHERE r.consultation_id = c.id
                ');
            } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('registre_engagements', 'credit_consolide')) {
            Schema::table('registre_engagements', function (Blueprint $table) {
                $table->dropColumn('credit_consolide');
            });
        }
    }
};
