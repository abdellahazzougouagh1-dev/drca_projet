<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marches', function (Blueprint $table) {
            if (!Schema::hasColumn('marches', 'objet_marche')) {
                $table->text('objet_marche')->nullable()->after('titulaire');
            }
            if (!Schema::hasColumn('marches', 'qualite_gerant')) {
                $table->string('qualite_gerant')->nullable()->after('objet_marche');
            }
        });

        Schema::table('fournisseurs', function (Blueprint $table) {
            if (!Schema::hasColumn('fournisseurs', 'qualite_representant')) {
                $table->string('qualite_representant')->nullable()->after('representant');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marches', function (Blueprint $table) {
            if (Schema::hasColumn('marches', 'qualite_gerant')) {
                $table->dropColumn('qualite_gerant');
            }
            if (Schema::hasColumn('marches', 'objet_marche')) {
                $table->dropColumn('objet_marche');
            }
        });

        Schema::table('fournisseurs', function (Blueprint $table) {
            if (Schema::hasColumn('fournisseurs', 'qualite_representant')) {
                $table->dropColumn('qualite_representant');
            }
        });
    }
};
