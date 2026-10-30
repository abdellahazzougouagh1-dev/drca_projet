<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE liquidations ALTER COLUMN commission_reception TYPE JSON USING (CASE WHEN commission_reception IS NULL OR commission_reception = '' THEN NULL ELSE commission_reception::json END)");
        } else {
            DB::statement('ALTER TABLE liquidations MODIFY commission_reception JSON');
        }
    }

    public function down()
    {
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE liquidations ALTER COLUMN commission_reception TYPE VARCHAR(255)');
        } else {
            DB::statement('ALTER TABLE liquidations MODIFY commission_reception VARCHAR(255)');
        }
    }
};
