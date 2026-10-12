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
        Schema::table('aoos', function (Blueprint $table) {
            $table->string('reference_publication_fr')->nullable()->after('journal_fr');
            $table->date('date_publication_fr')->nullable()->after('reference_publication_fr');
            $table->string('reference_publication_ar')->nullable()->after('journal_ar');
            $table->date('date_publication_ar')->nullable()->after('reference_publication_ar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aoos', function (Blueprint $table) {
            $table->dropColumn([
                'reference_publication_fr',
                'date_publication_fr',
                'reference_publication_ar',
                'date_publication_ar',
            ]);
        });
    }
};
