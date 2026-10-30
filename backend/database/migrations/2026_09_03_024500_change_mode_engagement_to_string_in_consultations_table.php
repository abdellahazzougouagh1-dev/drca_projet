<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
            DB::statement("ALTER TABLE consultations DROP CONSTRAINT IF EXISTS consultations_mode_engagement_check");
            DB::statement("ALTER TABLE consultations ALTER COLUMN mode_engagement TYPE VARCHAR(255), ALTER COLUMN mode_engagement SET DEFAULT 'BC', ALTER COLUMN mode_engagement SET NOT NULL");
        } else {
            DB::statement("ALTER TABLE consultations MODIFY mode_engagement VARCHAR(255) NOT NULL DEFAULT 'BC'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE consultations ALTER COLUMN mode_engagement TYPE VARCHAR(255)");
        } else {
            DB::statement("ALTER TABLE consultations MODIFY mode_engagement ENUM('BC', 'Convention') NOT NULL DEFAULT 'BC'");
        }
    }
};
