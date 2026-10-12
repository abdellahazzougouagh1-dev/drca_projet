<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            if (!Schema::hasColumn('fournisseurs', 'cnss')) {
                $table->string('cnss')->nullable()->after('qualite_representant');
            }
            if (!Schema::hasColumn('fournisseurs', 'banque')) {
                $table->string('banque')->nullable()->after('cnss');
            }
            if (!Schema::hasColumn('fournisseurs', 'agence_bancaire')) {
                $table->string('agence_bancaire')->nullable()->after('banque');
            }
            if (!Schema::hasColumn('fournisseurs', 'rib')) {
                $table->string('rib')->nullable()->after('agence_bancaire');
            }
            if (!Schema::hasColumn('fournisseurs', 'titulaire_compte')) {
                $table->string('titulaire_compte')->nullable()->after('rib');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            foreach (['titulaire_compte', 'rib', 'agence_bancaire', 'banque', 'cnss'] as $column) {
                if (Schema::hasColumn('fournisseurs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
