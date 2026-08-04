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
        Schema::create('aoos', function (Blueprint $table) {
            $table->id();
            $table->string('num_aoo')->unique();
            $table->text('objet');
            $table->date('date_ouverture')->nullable();
            $table->string('heure_ouverture')->nullable();
            $table->integer('nombre_lots')->default(1);
            $table->decimal('budget', 15, 2)->nullable();
            $table->string('art')->nullable();
            $table->string('par')->nullable();
            $table->string('lig')->nullable();
            $table->string('statut')->default('en_preparation');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aoos');
    }
};
