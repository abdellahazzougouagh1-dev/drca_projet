<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registre_engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->unique()->constrained('consultations')->cascadeOnDelete();
            $table->foreignId('engagement_id')->nullable()->constrained('engagements')->nullOnDelete();
            $table->date('date_engagement')->nullable();
            $table->string('mode_engagement')->nullable();
            $table->string('reference')->nullable();
            $table->string('reference_2')->nullable();
            $table->string('budget')->nullable();
            $table->string('code')->nullable();
            $table->string('art')->nullable();
            $table->string('par')->nullable();
            $table->string('lig')->nullable();
            $table->string('s_lig')->nullable();
            $table->text('intitule')->nullable();
            $table->decimal('credit_ouvert_cp', 15, 2)->nullable();
            $table->decimal('credit_ouvert_ce', 15, 2)->nullable();
            $table->decimal('depenses_anterieures_ce', 15, 2)->nullable();
            $table->decimal('depenses_anterieures_cp', 15, 2)->nullable();
            $table->decimal('depenses_credits_engagement', 15, 2)->nullable();
            $table->decimal('depenses_credits_consolides', 15, 2)->nullable();
            $table->decimal('depenses_rap', 15, 2)->nullable();
            $table->decimal('montant_depense_neuf', 15, 2)->nullable();
            $table->decimal('interets_moratoires', 15, 2)->nullable();
            $table->decimal('montant_engager_neuf', 15, 2)->nullable();
            $table->text('objet')->nullable();
            $table->string('beneficiaire')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registre_engagements');
    }
};
