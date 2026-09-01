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
            if (!Schema::hasColumn('marches', 'num_engagement')) {
                $table->string('num_engagement')->nullable()->after('num_marche');
            }
            if (!Schema::hasColumn('marches', 'reference_engagement')) {
                $table->string('reference_engagement')->nullable()->after('num_engagement');
            }
            if (!Schema::hasColumn('marches', 'forme_engagement')) {
                $table->string('forme_engagement')->nullable()->default('Bon de Commande')->after('reference_engagement');
            }
            if (!Schema::hasColumn('marches', 'date_engagement')) {
                $table->date('date_engagement')->nullable()->after('forme_engagement');
            }
            if (!Schema::hasColumn('marches', 'article_budget')) {
                $table->string('article_budget')->nullable()->after('intitule_budget');
            }
            if (!Schema::hasColumn('marches', 'paragraphe_budget')) {
                $table->string('paragraphe_budget')->nullable()->after('article_budget');
            }
            if (!Schema::hasColumn('marches', 'ligne_budget')) {
                $table->string('ligne_budget')->nullable()->after('paragraphe_budget');
            }
            if (!Schema::hasColumn('marches', 'credit_budget_cp')) {
                $table->decimal('credit_budget_cp', 15, 2)->nullable()->after('ligne_budget');
            }
            if (!Schema::hasColumn('marches', 'credit_budget_ce')) {
                $table->decimal('credit_budget_ce', 15, 2)->nullable()->after('credit_budget_cp');
            }
            if (!Schema::hasColumn('marches', 'depenses_engagees_cp')) {
                $table->decimal('depenses_engagees_cp', 15, 2)->nullable()->after('credit_budget_ce');
            }
            if (!Schema::hasColumn('marches', 'depenses_engagees_ce')) {
                $table->decimal('depenses_engagees_ce', 15, 2)->nullable()->after('depenses_engagees_cp');
            }
            if (!Schema::hasColumn('marches', 'disponible_cp')) {
                $table->decimal('disponible_cp', 15, 2)->nullable()->after('depenses_engagees_ce');
            }
            if (!Schema::hasColumn('marches', 'disponible_ce')) {
                $table->decimal('disponible_ce', 15, 2)->nullable()->after('disponible_cp');
            }
            if (!Schema::hasColumn('marches', 'engagement_propose_cp')) {
                $table->decimal('engagement_propose_cp', 15, 2)->nullable()->after('disponible_ce');
            }
            if (!Schema::hasColumn('marches', 'engagement_propose_ce')) {
                $table->decimal('engagement_propose_ce', 15, 2)->nullable()->after('engagement_propose_cp');
            }
            if (!Schema::hasColumn('marches', 'pieces_jointes')) {
                $table->text('pieces_jointes')->nullable()->after('engagement_propose_ce');
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
                'num_engagement',
                'reference_engagement',
                'forme_engagement',
                'date_engagement',
                'article_budget',
                'paragraphe_budget',
                'ligne_budget',
                'credit_budget_cp',
                'credit_budget_ce',
                'depenses_engagees_cp',
                'depenses_engagees_ce',
                'disponible_cp',
                'disponible_ce',
                'engagement_propose_cp',
                'engagement_propose_ce',
                'pieces_jointes',
            ]);
        });
    }
};
