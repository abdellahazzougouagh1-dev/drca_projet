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
        Schema::table('document_generes', function (Blueprint $table) {
            if (!Schema::hasColumn('document_generes', 'aoo_id')) {
                $table->unsignedBigInteger('aoo_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('document_generes', 'type_document')) {
                $table->string('type_document')->nullable();
            }
            if (!Schema::hasColumn('document_generes', 'nom_fichier')) {
                $table->string('nom_fichier')->nullable();
            }
            if (!Schema::hasColumn('document_generes', 'chemin_fichier')) {
                $table->string('chemin_fichier')->nullable();
            }
            if (!Schema::hasColumn('document_generes', 'version')) {
                $table->integer('version')->default(1);
            }
            if (!Schema::hasColumn('document_generes', 'statut')) {
                $table->string('statut')->nullable();
            }
            if (!Schema::hasColumn('document_generes', 'utilisateur_id')) {
                $table->unsignedBigInteger('utilisateur_id')->nullable();
            }
            
            // make old ones nullable if not already
            if (Schema::hasColumn('document_generes', 'etape_instance_id')) {
                $table->unsignedBigInteger('etape_instance_id')->nullable()->change();
            }
            if (Schema::hasColumn('document_generes', 'document_modele_id')) {
                $table->unsignedBigInteger('document_modele_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_generes', function (Blueprint $table) {
            $cols = array_filter(['aoo_id', 'type_document', 'nom_fichier', 'chemin_fichier', 'version', 'statut', 'utilisateur_id'], function($col) {
                return Schema::hasColumn('document_generes', $col);
            });
            if (!empty($cols)) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
