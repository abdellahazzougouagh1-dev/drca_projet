<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE consultations ALTER COLUMN objet_consultation TYPE TEXT, ALTER COLUMN objet_consultation DROP NOT NULL");
            DB::statement("ALTER TABLE consultations ALTER COLUMN type_prestation TYPE TEXT, ALTER COLUMN type_prestation DROP NOT NULL");
        } else {
            DB::statement("ALTER TABLE consultations MODIFY objet_consultation TEXT NULL");
            DB::statement("ALTER TABLE consultations MODIFY type_prestation TEXT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE consultations ALTER COLUMN objet_consultation TYPE VARCHAR(255)");
            DB::statement("ALTER TABLE consultations ALTER COLUMN type_prestation TYPE VARCHAR(255)");
        } else {
            DB::statement("ALTER TABLE consultations MODIFY objet_consultation VARCHAR(255) NULL");
            DB::statement("ALTER TABLE consultations MODIFY type_prestation VARCHAR(255) NULL");
        }
    }
};
