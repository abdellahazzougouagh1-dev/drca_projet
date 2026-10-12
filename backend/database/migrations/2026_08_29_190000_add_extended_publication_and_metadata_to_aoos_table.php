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
            if (!Schema::hasColumn('aoos', 'publications_journaux')) {
                $table->json('publications_journaux')->nullable()->after('date_publication_ar');
            }
            if (!Schema::hasColumn('aoos', 'date_publication_portail')) {
                $table->date('date_publication_portail')->nullable()->after('publications_journaux');
            }
            if (!Schema::hasColumn('aoos', 'ref_publication_portail')) {
                $table->string('ref_publication_portail')->nullable()->after('date_publication_portail');
            }
            if (!Schema::hasColumn('aoos', 'date_mise_en_ligne_portail')) {
                $table->date('date_mise_en_ligne_portail')->nullable()->after('ref_publication_portail');
            }
            if (!Schema::hasColumn('aoos', 'mode_passation')) {
                $table->string('mode_passation')->default("D’APPEL D’OFFRES OUVERT SIMPLIFIÉ SUR OFFRES DE PRIX")->after('reference');
            }
            if (!Schema::hasColumn('aoos', 'prix_reference')) {
                $table->decimal('prix_reference', 15, 2)->nullable()->after('budget');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aoos', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('aoos', 'publications_journaux')) $columnsToDrop[] = 'publications_journaux';
            if (Schema::hasColumn('aoos', 'date_publication_portail')) $columnsToDrop[] = 'date_publication_portail';
            if (Schema::hasColumn('aoos', 'ref_publication_portail')) $columnsToDrop[] = 'ref_publication_portail';
            if (Schema::hasColumn('aoos', 'date_mise_en_ligne_portail')) $columnsToDrop[] = 'date_mise_en_ligne_portail';
            if (Schema::hasColumn('aoos', 'mode_passation')) $columnsToDrop[] = 'mode_passation';
            if (Schema::hasColumn('aoos', 'prix_reference')) $columnsToDrop[] = 'prix_reference';

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
