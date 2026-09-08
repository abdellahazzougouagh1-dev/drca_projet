<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registre_engagements', function (Blueprint $table) {
            $table->foreignId('aoo_id')
                ->nullable()
                ->after('consultation_id')
                ->constrained('aoos')
                ->cascadeOnDelete();
            $table->unique('aoo_id');
        });
    }

    public function down(): void
    {
        Schema::table('registre_engagements', function (Blueprint $table) {
            $table->dropUnique('registre_engagements_aoo_id_unique');
            $table->dropForeign(['aoo_id']);
            $table->dropColumn('aoo_id');
        });
    }
};
