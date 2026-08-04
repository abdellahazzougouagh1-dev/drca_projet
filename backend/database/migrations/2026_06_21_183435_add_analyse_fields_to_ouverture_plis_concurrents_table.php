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
        Schema::table('ouverture_plis_concurrents', function (Blueprint $table) {
            $table->string('ref_courrier')->nullable();
            $table->string('signataire_titre')->nullable();
            $table->string('gerant_nom')->nullable();
            $table->enum('statut_analyse', ['retenu', 'ecarte'])->nullable();
            $table->text('motif_ecartement')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ouverture_plis_concurrents', function (Blueprint $table) {
            $table->dropColumn([
                'ref_courrier',
                'signataire_titre',
                'gerant_nom',
                'statut_analyse',
                'motif_ecartement'
            ]);
        });
    }
};
