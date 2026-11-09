<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('consultations')) {
            return;
        }

        Schema::table('consultations', function (Blueprint $table) {
            if (!Schema::hasColumn('consultations', 'date_reunion')) {
                $table->date('date_reunion')->nullable();
            }
            if (!Schema::hasColumn('consultations', 'heure_reunion')) {
                $table->string('heure_reunion')->nullable();
            }
            if (!Schema::hasColumn('consultations', 'lieu_reunion')) {
                $table->string('lieu_reunion')->nullable();
            }
            if (!Schema::hasColumn('consultations', 'president_commission')) {
                $table->string('president_commission')->nullable();
            }
            if (!Schema::hasColumn('consultations', 'membres_commission')) {
                $table->json('membres_commission')->nullable();
            }
            if (!Schema::hasColumn('consultations', 'observations_commission')) {
                $table->text('observations_commission')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('consultations')) {
            return;
        }

        Schema::table('consultations', function (Blueprint $table) {
            $columns = array_values(array_filter([
                'date_reunion',
                'heure_reunion',
                'lieu_reunion',
                'president_commission',
                'membres_commission',
                'observations_commission',
            ], fn (string $column) => Schema::hasColumn('consultations', $column)));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
