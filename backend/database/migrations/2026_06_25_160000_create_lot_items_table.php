<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lot_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('numero')->default(1);
            $table->string('designation');
            $table->string('unite')->nullable();
            $table->decimal('quantite', 15, 2)->default(1);
            $table->decimal('prix_unitaire_ht', 15, 2)->default(0);
            $table->decimal('montant_ht', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lot_items');
    }
};
