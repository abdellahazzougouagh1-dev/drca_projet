<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registre_engagements', function (Blueprint $table) {
            $table->string('numero_rubrique')->nullable()->after('date_engagement');
        });

        foreach (\App\Models\RegistreEngagement::with('consultation')->cursor() as $registre) {
            $registre->update([
                'numero_rubrique' => $registre->consultation?->numero_engagement,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('registre_engagements', function (Blueprint $table) {
            $table->dropColumn('numero_rubrique');
        });
    }
};
