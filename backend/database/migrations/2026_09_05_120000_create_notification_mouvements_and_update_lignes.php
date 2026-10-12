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
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (!Schema::hasColumn('notifications', 'type_budget')) {
                    $table->string('type_budget')->default('NOTIFIER')->after('numero');
                }
                if (!Schema::hasColumn('notifications', 'domaine')) {
                    $table->string('domaine')->nullable()->after('type_budget');
                }
            });
        }

        if (Schema::hasTable('notification_lignes')) {
            Schema::table('notification_lignes', function (Blueprint $table) {
                if (!Schema::hasColumn('notification_lignes', 'type_budget')) {
                    $table->string('type_budget')->default('NOTIFIER')->after('libelle');
                }
                if (!Schema::hasColumn('notification_lignes', 'domaine')) {
                    $table->string('domaine')->nullable()->after('type_budget');
                }
                if (!Schema::hasColumn('notification_lignes', 'diminution_credit_engagement')) {
                    $table->decimal('diminution_credit_engagement', 15, 2)->default(0)->after('credits_engagements');
                }
            });
        }

        if (!Schema::hasTable('notification_mouvements')) {
            Schema::create('notification_mouvements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
                $table->foreignId('notification_ligne_id')->constrained('notification_lignes')->cascadeOnDelete();
                $table->string('type_budget')->default('NOTIFIER'); // REPORT, NOTIFIER
                $table->string('domaine')->nullable(); // FONCTIONNEMENT, INVESTISSEMENT
                $table->string('nature')->nullable(); // ALIMENTATION, DIMINUTION, REPORT
                $table->string('type_credit'); // REPORT, ENGAGEMENT, NEUF_CC, NEUF_CPN, DIMINUTION_REPORT, DIMINUTION_ENGAGEMENT, DIMINUTION_PAIEMENT
                $table->string('numero_notification')->nullable();
                $table->date('date_mouvement')->nullable();
                $table->decimal('montant', 15, 2)->default(0);
                $table->string('motif')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_mouvements');

        if (Schema::hasTable('notification_lignes')) {
            Schema::table('notification_lignes', function (Blueprint $table) {
                if (Schema::hasColumn('notification_lignes', 'diminution_credit_engagement')) {
                    $table->dropColumn('diminution_credit_engagement');
                }
                if (Schema::hasColumn('notification_lignes', 'domaine')) {
                    $table->dropColumn('domaine');
                }
                if (Schema::hasColumn('notification_lignes', 'type_budget')) {
                    $table->dropColumn('type_budget');
                }
            });
        }

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (Schema::hasColumn('notifications', 'domaine')) {
                    $table->dropColumn('domaine');
                }
                if (Schema::hasColumn('notifications', 'type_budget')) {
                    $table->dropColumn('type_budget');
                }
            });
        }
    }
};
