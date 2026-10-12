<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aoos', function (Blueprint $table) {
            $table->string('numero_engagement')->nullable();
            $table->date('date_engagement')->nullable();
            $table->string('reference_engagement')->nullable();
            $table->decimal('credit_ouvert_cp', 15, 2)->nullable();
            $table->decimal('credit_ouvert_ce', 15, 2)->nullable();
            $table->decimal('depenses_anterieures_cp', 15, 2)->nullable();
            $table->decimal('depenses_anterieures_ce', 15, 2)->nullable();
            $table->decimal('depenses_credits_engagement', 15, 2)->nullable();
            $table->decimal('depenses_credits_consolides', 15, 2)->nullable();
            $table->decimal('depenses_rap', 15, 2)->nullable();
            $table->decimal('montant_depense_neuf', 15, 2)->nullable();
            $table->decimal('interets_moratoires', 15, 2)->nullable();
            $table->decimal('montant_engager_neuf', 15, 2)->nullable();
            $table->text('pieces_jointes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('aoos', function (Blueprint $table) {
            $table->dropColumn([
                'numero_engagement', 'date_engagement', 'reference_engagement',
                'credit_ouvert_cp', 'credit_ouvert_ce', 'depenses_anterieures_cp',
                'depenses_anterieures_ce', 'depenses_credits_engagement',
                'depenses_credits_consolides', 'depenses_rap', 'montant_depense_neuf',
                'interets_moratoires', 'montant_engager_neuf', 'pieces_jointes',
            ]);
        });
    }
};
