<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('liquidations', function (Blueprint $table) {
            if (!Schema::hasColumn('liquidations', 'num_decision')) {
                $table->string('num_decision')->nullable();
            }
            if (!Schema::hasColumn('liquidations', 'date_decision')) {
                $table->date('date_decision')->nullable();
            }
            if (!Schema::hasColumn('liquidations', 'date_reunion_commission')) {
                $table->date('date_reunion_commission')->nullable();
            }
            if (!Schema::hasColumn('liquidations', 'heure_reunion_commission')) {
                $table->string('heure_reunion_commission')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('liquidations', function (Blueprint $table) {
            $cols = array_filter(['num_decision', 'date_decision', 'date_reunion_commission', 'heure_reunion_commission'], function($col) {
                return Schema::hasColumn('liquidations', $col);
            });
            if (!empty($cols)) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
