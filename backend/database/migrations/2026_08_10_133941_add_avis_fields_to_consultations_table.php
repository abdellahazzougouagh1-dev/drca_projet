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
        Schema::table('consultations', function (Blueprint $table) {
            if (!Schema::hasColumn('consultations', 'objet_consultation_ar')) {
                $table->string('objet_consultation_ar')->nullable()->after('objet_consultation');
            }
            if (!Schema::hasColumn('consultations', 'lieu_reunion_ar')) {
                $table->string('lieu_reunion_ar')->nullable();
            }
            if (!Schema::hasColumn('consultations', 'cautionnement_provisoire')) {
                $table->decimal('cautionnement_provisoire', 15, 2)->nullable()->after('type_budget');
            }
        });

        Schema::table('document_generes', function (Blueprint $table) {
            if (!Schema::hasColumn('document_generes', 'consultation_id')) {
                $table->unsignedBigInteger('consultation_id')->nullable()->after('aoo_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $columnsToDrop = array_filter(['objet_consultation_ar', 'lieu_reunion_ar', 'cautionnement_provisoire'], function ($col) {
                return Schema::hasColumn('consultations', $col);
            });
            if (!empty($columnsToDrop)) {
                $table->dropColumn(array_values($columnsToDrop));
            }
        });

        Schema::table('document_generes', function (Blueprint $table) {
            if (Schema::hasColumn('document_generes', 'consultation_id')) {
                $table->dropColumn('consultation_id');
            }
        });
    }
};
