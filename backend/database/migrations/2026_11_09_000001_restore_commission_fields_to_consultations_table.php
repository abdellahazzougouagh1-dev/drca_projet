<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PgBouncer / Neon pooled connections can reject schema catalog queries
     * inside migration transactions with SQLSTATE[25P02]. Execute DDL
     * idempotently outside an explicit transaction block.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() === 'pgsql') {
            $table = $connection->getSchemaGrammar()->wrapTable('consultations');
            $connection->unprepared(
                "ALTER TABLE IF EXISTS {$table}
                    ADD COLUMN IF NOT EXISTS date_reunion date NULL,
                    ADD COLUMN IF NOT EXISTS heure_reunion varchar(255) NULL,
                    ADD COLUMN IF NOT EXISTS lieu_reunion varchar(255) NULL,
                    ADD COLUMN IF NOT EXISTS president_commission varchar(255) NULL,
                    ADD COLUMN IF NOT EXISTS membres_commission json NULL,
                    ADD COLUMN IF NOT EXISTS observations_commission text NULL;"
            );

            return;
        }

        if (!Schema::hasTable('consultations')) {
            return;
        }

        $existingColumns = array_map('strtolower', Schema::getColumnListing('consultations'));
        $columnsToAdd = [
            'date_reunion' => fn (Blueprint $table) => $table->date('date_reunion')->nullable(),
            'heure_reunion' => fn (Blueprint $table) => $table->string('heure_reunion')->nullable(),
            'lieu_reunion' => fn (Blueprint $table) => $table->string('lieu_reunion')->nullable(),
            'president_commission' => fn (Blueprint $table) => $table->string('president_commission')->nullable(),
            'membres_commission' => fn (Blueprint $table) => $table->json('membres_commission')->nullable(),
            'observations_commission' => fn (Blueprint $table) => $table->text('observations_commission')->nullable(),
        ];

        $missing = array_filter(
            $columnsToAdd,
            fn (callable $_, string $column) => !in_array($column, $existingColumns, true),
            ARRAY_FILTER_USE_BOTH
        );

        if ($missing === []) {
            return;
        }

        Schema::table('consultations', function (Blueprint $table) use ($missing) {
            foreach ($missing as $addColumn) {
                $addColumn($table);
            }
        });
    }

    public function down(): void
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() === 'pgsql') {
            $table = $connection->getSchemaGrammar()->wrapTable('consultations');
            $connection->unprepared(
                "ALTER TABLE IF EXISTS {$table}
                    DROP COLUMN IF EXISTS date_reunion,
                    DROP COLUMN IF EXISTS heure_reunion,
                    DROP COLUMN IF EXISTS lieu_reunion,
                    DROP COLUMN IF EXISTS president_commission,
                    DROP COLUMN IF EXISTS membres_commission,
                    DROP COLUMN IF EXISTS observations_commission;"
            );

            return;
        }

        if (!Schema::hasTable('consultations')) {
            return;
        }

        $existingColumns = array_map('strtolower', Schema::getColumnListing('consultations'));
        $columns = array_values(array_filter([
            'date_reunion',
            'heure_reunion',
            'lieu_reunion',
            'president_commission',
            'membres_commission',
            'observations_commission',
        ], fn (string $column) => in_array($column, $existingColumns, true)));

        if ($columns !== []) {
            Schema::table('consultations', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
