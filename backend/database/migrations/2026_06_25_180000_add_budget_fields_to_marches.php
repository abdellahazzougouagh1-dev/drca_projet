<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marches', function (Blueprint $table) {
            if (!Schema::hasColumn('marches', 'exercice')) {
                $table->string('exercice', 4)->nullable()->after('qualite_gerant');
            }
            if (!Schema::hasColumn('marches', 'type_budget')) {
                $table->string('type_budget')->nullable()->default('Investissement')->after('exercice');
            }
            if (!Schema::hasColumn('marches', 'code_budget')) {
                $table->string('code_budget')->nullable()->after('type_budget');
            }
            if (!Schema::hasColumn('marches', 'intitule_budget')) {
                $table->text('intitule_budget')->nullable()->after('code_budget');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marches', function (Blueprint $table) {
            foreach (['intitule_budget', 'code_budget', 'type_budget', 'exercice'] as $column) {
                if (Schema::hasColumn('marches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
