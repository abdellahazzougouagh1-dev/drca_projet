<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registre_engagements', function (Blueprint $table) {
            $table->foreignId('consultation_id')->nullable()->change();
            $table->foreignId('marche_id')->nullable()->after('consultation_id')->constrained('marches')->cascadeOnDelete();
        });

        Schema::table('registre_engagements', function (Blueprint $table) {
            $table->dropUnique('registre_engagements_consultation_id_unique');
            $table->unique('consultation_id');
            $table->unique('marche_id');
        });
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
