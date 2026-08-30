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
        Schema::table('fournisseurs', function (Blueprint $table) {
            if (!Schema::hasColumn('fournisseurs', 'forme_juridique')) {
                $table->string('forme_juridique')->nullable();
            }
            if (!Schema::hasColumn('fournisseurs', 'capital_social')) {
                $table->string('capital_social')->nullable();
            }
            if (!Schema::hasColumn('fournisseurs', 'ville_rc')) {
                $table->string('ville_rc')->nullable();
            }
            if (!Schema::hasColumn('fournisseurs', 'fax')) {
                $table->string('fax')->nullable();
            }
            if (!Schema::hasColumn('fournisseurs', 'domicile_elu')) {
                $table->text('domicile_elu')->nullable();
            }
            if (!Schema::hasColumn('fournisseurs', 'pays')) {
                $table->string('pays')->nullable()->default('Maroc');
            }
            if (!Schema::hasColumn('fournisseurs', 'taxe_professionnelle')) {
                $table->string('taxe_professionnelle')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->dropColumn([
                'forme_juridique',
                'capital_social',
                'ville_rc',
                'fax',
                'domicile_elu',
                'pays',
                'taxe_professionnelle',
            ]);
        });
    }
};
