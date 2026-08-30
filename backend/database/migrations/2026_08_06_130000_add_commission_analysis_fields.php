<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aoos', function (Blueprint $table) {
            if (!Schema::hasColumn('aoos', 'commission_validee')) {
                $table->boolean('commission_validee')->default(false)->after('statut');
            }
        });

        Schema::table('ouverture_plis_concurrents', function (Blueprint $table) {
            if (!Schema::hasColumn('ouverture_plis_concurrents', 'admin_conforme')) {
                $table->boolean('admin_conforme')->nullable()->after('observations');
            }
            if (!Schema::hasColumn('ouverture_plis_concurrents', 'admin_motif_rejet')) {
                $table->text('admin_motif_rejet')->nullable()->after('admin_conforme');
            }
            if (!Schema::hasColumn('ouverture_plis_concurrents', 'admin_observations')) {
                $table->text('admin_observations')->nullable()->after('admin_motif_rejet');
            }
            if (!Schema::hasColumn('ouverture_plis_concurrents', 'tech_conforme')) {
                $table->boolean('tech_conforme')->nullable()->after('admin_observations');
            }
            if (!Schema::hasColumn('ouverture_plis_concurrents', 'tech_note')) {
                $table->decimal('tech_note', 5, 2)->nullable()->after('tech_conforme');
            }
            if (!Schema::hasColumn('ouverture_plis_concurrents', 'tech_observations')) {
                $table->text('tech_observations')->nullable()->after('tech_note');
            }
            if (!Schema::hasColumn('ouverture_plis_concurrents', 'montant_ht')) {
                $table->decimal('montant_ht', 15, 2)->nullable()->after('tech_observations');
            }
            if (!Schema::hasColumn('ouverture_plis_concurrents', 'tva')) {
                $table->decimal('tva', 15, 2)->nullable()->after('montant_ht');
            }
            if (!Schema::hasColumn('ouverture_plis_concurrents', 'montant_ttc')) {
                $table->decimal('montant_ttc', 15, 2)->nullable()->after('tva');
            }
            if (!Schema::hasColumn('ouverture_plis_concurrents', 'classement')) {
                $table->integer('classement')->nullable()->after('montant_ttc');
            }
        });
    }

    public function down(): void
    {
        Schema::table('aoos', function (Blueprint $table) {
            if (Schema::hasColumn('aoos', 'commission_validee')) {
                $table->dropColumn('commission_validee');
            }
        });

        Schema::table('ouverture_plis_concurrents', function (Blueprint $table) {
            foreach ([
                'admin_conforme',
                'admin_motif_rejet',
                'admin_observations',
                'tech_conforme',
                'tech_note',
                'tech_observations',
                'montant_ht',
                'tva',
                'montant_ttc',
                'classement',
            ] as $column) {
                if (Schema::hasColumn('ouverture_plis_concurrents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
