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
        Schema::create('marche_clotures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marche_id')->constrained()->cascadeOnDelete();
            
            // Cautionnement / Mainlevée
            $table->string('type_cautionnement')->nullable();
            $table->string('reference_caution')->nullable();
            $table->date('date_caution')->nullable();
            $table->string('organisme_caution')->nullable();
            $table->decimal('montant_caution', 15, 2)->nullable();
            $table->date('date_validite_caution')->nullable();
            
            $table->string('motif_mainlevee')->nullable();
            $table->text('conditions_mainlevee')->nullable();
            $table->text('observations_mainlevee')->nullable();
            $table->string('signataire_mainlevee')->nullable();
            $table->date('date_signature_mainlevee')->nullable();

            // Exécution / Certificat
            $table->date('date_reception_provisoire')->nullable();
            $table->date('date_reception_definitive')->nullable();
            $table->string('qualite_execution')->nullable(); // Satisfaisante, etc.
            $table->boolean('respect_delais')->default(true);
            $table->boolean('reserves_emises')->default(false);
            $table->boolean('reserves_levees')->default(false);
            $table->string('signataire_certificat')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marche_clotures');
    }
};
