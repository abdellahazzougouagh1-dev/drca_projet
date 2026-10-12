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
        DB::statement("ALTER TABLE consultations MODIFY mode_engagement VARCHAR(255) NOT NULL DEFAULT 'BC'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE consultations MODIFY mode_engagement ENUM('BC', 'Convention') NOT NULL DEFAULT 'BC'");
    }
};
