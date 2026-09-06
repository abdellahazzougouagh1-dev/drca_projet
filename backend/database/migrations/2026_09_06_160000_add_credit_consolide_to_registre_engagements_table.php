<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registre_engagements', function (Blueprint $table) {
            $table->decimal('credit_consolide', 15, 2)->nullable()->after('credit_ouvert_ce');
        });

        foreach (\App\Models\RegistreEngagement::with('consultation.notificationLigne')->cursor() as $registre) {
            $ligne = $registre->consultation?->notificationLigne;
            if ($ligne) {
                $registre->update([
                    'credit_consolide' => (float) ($ligne->reports ?? 0) + (float) ($ligne->credits_neufs ?? 0),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('registre_engagements', function (Blueprint $table) {
            $table->dropColumn('credit_consolide');
        });
    }
};
