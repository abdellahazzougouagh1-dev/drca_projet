<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE registre_engagements ALTER COLUMN consultation_id DROP NOT NULL');
        } else {
            Schema::table('registre_engagements', function (Blueprint $table) {
                $table->foreignId('consultation_id')->nullable()->change();
            });
        }

        if (!Schema::hasColumn('registre_engagements', 'marche_id')) {
            Schema::table('registre_engagements', function (Blueprint $table) {
                $table->foreignId('marche_id')->nullable()->after('consultation_id')->constrained('marches')->cascadeOnDelete();
            });
        }

        $hasMarcheUniqueIndex = collect(Schema::getIndexes('registre_engagements'))
            ->contains(fn (array $index) => $index['unique'] && $index['columns'] === ['marche_id']);

        if (!$hasMarcheUniqueIndex) {
            Schema::table('registre_engagements', function (Blueprint $table) {
                $table->unique('marche_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('registre_engagements', function (Blueprint $table) {
            $table->dropUnique('registre_engagements_marche_id_unique');
            $table->dropForeign(['marche_id']);
            $table->dropColumn('marche_id');
        });
    }
};
