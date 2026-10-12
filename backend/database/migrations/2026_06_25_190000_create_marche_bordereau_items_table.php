<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marche_bordereau_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marche_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lot_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('prix_unitaire_attributaire', 15, 2)->default(0);
            $table->decimal('montant_ht', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['marche_id', 'lot_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marche_bordereau_items');
    }
};
