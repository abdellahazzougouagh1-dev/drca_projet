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
        DB::statement("ALTER TABLE consultations MODIFY objet_consultation TEXT NULL");
        DB::statement("ALTER TABLE consultations MODIFY type_prestation TEXT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE consultations MODIFY objet_consultation VARCHAR(255) NULL");
        DB::statement("ALTER TABLE consultations MODIFY type_prestation VARCHAR(255) NULL");
    }
};
