<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('concurrent_lot_decisions');

        Schema::create('concurrent_lot_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aoo_id')->constrained('aoos')->onDelete('cascade');
            $table->foreignId('lot_id')->constrained('lots')->onDelete('cascade');
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->onDelete('restrict');
            $table->decimal('montant_propose', 15, 2)->nullable();
            $table->string('statut')->nullable(); // 'Retenu' | 'Ecarte'
            $table->text('motif_ecartement')->nullable();
            $table->timestamps();

            $table->unique(['aoo_id', 'lot_id', 'fournisseur_id']);
        });

        // Add attributaire_id to lots table for Phase 4 attribution
        Schema::table('lots', function (Blueprint $table) {
            $table->foreignId('attributaire_fournisseur_id')->nullable()->after('estimation')->constrained('fournisseurs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropForeign(['attributaire_fournisseur_id']);
            $table->dropColumn('attributaire_fournisseur_id');
        });
        Schema::dropIfExists('concurrent_lot_decisions');
    }
};
