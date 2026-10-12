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
        Schema::table('marches', function (Blueprint $table) {
            if (!Schema::hasColumn('marches', 'num_ordre')) {
                $table->string('num_ordre')->nullable()->after('id');
            }
            if (!Schema::hasColumn('marches', 'code_ste')) {
                $table->string('code_ste')->nullable()->after('fournisseur_id');
            }
            if (!Schema::hasColumn('marches', 'date_oa')) {
                $table->date('date_oa')->nullable()->after('code_ste');
            }
            if (!Schema::hasColumn('marches', 'nature_ordre_service')) {
                $table->string('nature_ordre_service')->nullable()->default('Commencement de l\'execution')->after('os_numero');
            }
            if (!Schema::hasColumn('marches', 'caution_definitive')) {
                $table->decimal('caution_definitive', 15, 2)->nullable()->after('montant');
            }
            if (!Schema::hasColumn('marches', 'notificateur')) {
                $table->string('notificateur')->nullable()->after('agent_suivi');
            }
            if (!Schema::hasColumn('marches', 'fonction_signataire')) {
                $table->string('fonction_signataire')->nullable()->default('Chef de service/SPSO')->after('notificateur');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marches', function (Blueprint $table) {
            $table->dropColumn([
                'num_ordre',
                'code_ste',
                'date_oa',
                'nature_ordre_service',
                'caution_definitive',
                'notificateur',
                'fonction_signataire',
            ]);
        });
    }
};
