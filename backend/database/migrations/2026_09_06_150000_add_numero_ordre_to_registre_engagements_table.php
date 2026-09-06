<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registre_engagements', function (Blueprint $table) {
            $table->unsignedInteger('numero_ordre')->nullable()->after('id');
        });

        $ordre = 1;
        foreach (\App\Models\RegistreEngagement::orderBy('id')->cursor() as $registre) {
            $registre->update(['numero_ordre' => $ordre++]);
        }
    }

    public function down(): void
    {
        Schema::table('registre_engagements', function (Blueprint $table) {
            $table->dropColumn('numero_ordre');
        });
    }
};
