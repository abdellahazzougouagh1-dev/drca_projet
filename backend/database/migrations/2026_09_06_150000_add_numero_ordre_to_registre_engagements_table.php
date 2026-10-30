<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('registre_engagements', 'numero_ordre')) {
            Schema::table('registre_engagements', function (Blueprint $table) {
                $table->unsignedInteger('numero_ordre')->nullable()->after('id');
            });
        }

        if (Schema::hasTable('registre_engagements') && DB::table('registre_engagements')->count() > 0) {
            try {
                $ordre = 1;
                foreach (DB::table('registre_engagements')->select('id')->orderBy('id')->get() as $row) {
                    DB::table('registre_engagements')->where('id', $row->id)->update(['numero_ordre' => $ordre++]);
                }
            } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('registre_engagements', 'numero_ordre')) {
            Schema::table('registre_engagements', function (Blueprint $table) {
                $table->dropColumn('numero_ordre');
            });
        }
    }
};
