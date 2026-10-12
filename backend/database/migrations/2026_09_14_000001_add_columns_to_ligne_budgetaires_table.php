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
        if (!Schema::hasTable('ligne_budgetaires')) {
            Schema::create('ligne_budgetaires', function (Blueprint $table) {
                $table->id();
                $table->string('type_budget')->default('Investissement');
                $table->string('code_imputation')->nullable()->index();
                $table->string('article')->nullable();
                $table->string('paragraphe')->nullable();
                $table->string('ligne')->nullable();
                $table->text('intitule');
                $table->string('niveau')->nullable(); // rubrique, article, paragraphe, ligne
                $table->string('parent_code')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('ligne_budgetaires', function (Blueprint $table) {
                if (!Schema::hasColumn('ligne_budgetaires', 'type_budget')) {
                    $table->string('type_budget')->default('Investissement')->after('id');
                }
                if (!Schema::hasColumn('ligne_budgetaires', 'code_imputation')) {
                    $table->string('code_imputation')->nullable()->index()->after('type_budget');
                }
                if (!Schema::hasColumn('ligne_budgetaires', 'article')) {
                    $table->string('article')->nullable()->after('code_imputation');
                }
                if (!Schema::hasColumn('ligne_budgetaires', 'paragraphe')) {
                    $table->string('paragraphe')->nullable()->after('article');
                }
                if (!Schema::hasColumn('ligne_budgetaires', 'ligne')) {
                    $table->string('ligne')->nullable()->after('paragraphe');
                }
                if (!Schema::hasColumn('ligne_budgetaires', 'intitule')) {
                    $table->text('intitule')->nullable()->after('ligne');
                }
                if (!Schema::hasColumn('ligne_budgetaires', 'niveau')) {
                    $table->string('niveau')->nullable()->after('intitule');
                }
                if (!Schema::hasColumn('ligne_budgetaires', 'parent_code')) {
                    $table->string('parent_code')->nullable()->after('niveau');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ligne_budgetaires')) {
            Schema::table('ligne_budgetaires', function (Blueprint $table) {
                $columns = ['type_budget', 'code_imputation', 'article', 'paragraphe', 'ligne', 'intitule', 'niveau', 'parent_code'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('ligne_budgetaires', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
