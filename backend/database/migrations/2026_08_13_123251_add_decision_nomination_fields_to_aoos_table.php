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
        Schema::table('aoos', function (Blueprint $table) {
            if (!Schema::hasColumn('aoos', 'references_juridiques')) {
                $table->json('references_juridiques')->nullable();
            }
            if (!Schema::hasColumn('aoos', 'signataire_nom')) {
                $table->string('signataire_nom')->nullable();
            }
            if (!Schema::hasColumn('aoos', 'signataire_fonction')) {
                $table->string('signataire_fonction')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aoos', function (Blueprint $table) {
            $table->dropColumn(['references_juridiques', 'signataire_nom', 'signataire_fonction']);
        });
    }
};
