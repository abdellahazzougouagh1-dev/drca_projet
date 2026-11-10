<?php

namespace App\Models;

use App\Services\BudgetHierarchyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class NotificationLigne extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_id',
        'article',
        'paragraphe',
        'ligne_budgetaire',
        'libelle',
        'type_budget',
        'domaine',
        'reports',
        'diminution_report',
        'credits_neufs',
        'diminution_credit_neuf',
        'credits_engagements',
        'diminution_credit_engagement',
        'total_credits',
    ];

    protected $casts = [
        'reports' => 'decimal:2',
        'diminution_report' => 'decimal:2',
        'credits_neufs' => 'decimal:2',
        'diminution_credit_neuf' => 'decimal:2',
        'credits_engagements' => 'decimal:2',
        'diminution_credit_engagement' => 'decimal:2',
        'total_credits' => 'decimal:2',
    ];

    protected $appends = ['credits_engages', 'credits_disponibles'];

    protected static function booted(): void
    {
        static::saving(function (NotificationLigne $ligne) {
            $ligne->total_credits = ($ligne->reports ?? 0)
                                  + ($ligne->credits_neufs ?? 0)
                                  + ($ligne->credits_engagements ?? 0);
        });

        static::saved(function () {
            static::clearBudgetCache();
        });

        static::deleted(function () {
            static::clearBudgetCache();
        });
    }

    public static function clearBudgetCache(): void
    {
        Cache::forget('notif_lignes_engage_neuf_map');
        Cache::forget('notif_lignes_mouvements_map');
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(NotificationMouvement::class, 'notification_ligne_id');
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function marches(): HasMany
    {
        return $this->hasMany(Marche::class);
    }

    public function aoos(): HasMany
    {
        return $this->hasMany(Aoo::class);
    }

    protected static $engageNeufCache = null;
    protected static $mouvementsCache = null;

    protected static function getCachedEngageNeuf(int $ligneId, string $domaine): float
    {
        $data = Cache::remember('notif_lignes_engage_neuf_map', 60, function () {
            $map = [];

            // Group sum by consultation.notification_ligne_id (excluding cancelled/rejected dossiers)
            $consultations = RegistreEngagement::query()
                ->join('consultations', 'registre_engagements.consultation_id', '=', 'consultations.id')
                ->whereNotNull('consultations.notification_ligne_id')
                ->selectRaw('consultations.notification_ligne_id as lid, consultations.statut_dossier as statut, UPPER(COALESCE(registre_engagements.budget, \'\')) as bgt, COALESCE(registre_engagements.montant_engager_neuf, registre_engagements.montant_depense_neuf, 0) as amount')
                ->get();

            foreach ($consultations as $row) {
                if (BudgetHierarchyService::isOrdonnancementCancelledOrRejected($row->statut)) {
                    continue;
                }
                $b = str_contains($row->bgt, 'FONCT') ? 'FONCTIONNEMENT' : 'INVESTISSEMENT';
                $map[$row->lid][$b] = ($map[$row->lid][$b] ?? 0) + (float) $row->amount;
            }

            // Include unsynced Engagement records not yet in registre_engagements
            $unsyncedEngagements = Engagement::query()
                ->join('consultations', 'engagements.consultation_id', '=', 'consultations.id')
                ->whereNotNull('consultations.notification_ligne_id')
                ->whereNotExists(function ($q) {
                    $q->selectRaw('1')
                        ->from('registre_engagements')
                        ->where(function ($sub) {
                            $sub->whereColumn('registre_engagements.engagement_id', 'engagements.id')
                                ->orWhereColumn('registre_engagements.consultation_id', 'engagements.consultation_id');
                        });
                })
                ->selectRaw('consultations.notification_ligne_id as lid, consultations.statut_dossier as statut, UPPER(COALESCE(consultations.type_budget, \'\')) as bgt, COALESCE(engagements.montant_global, 0) as amount')
                ->get();

            foreach ($unsyncedEngagements as $row) {
                if (BudgetHierarchyService::isOrdonnancementCancelledOrRejected($row->statut)) {
                    continue;
                }
                $b = str_contains($row->bgt, 'FONCT') ? 'FONCTIONNEMENT' : 'INVESTISSEMENT';
                $map[$row->lid][$b] = ($map[$row->lid][$b] ?? 0) + (float) $row->amount;
            }

            // Group sum by aoo.notification_ligne_id
            $aoos = RegistreEngagement::query()
                ->join('aoos', 'registre_engagements.aoo_id', '=', 'aoos.id')
                ->whereNotNull('aoos.notification_ligne_id')
                ->selectRaw('aoos.notification_ligne_id as lid, aoos.statut as statut, UPPER(COALESCE(registre_engagements.budget, \'\')) as bgt, COALESCE(registre_engagements.montant_engager_neuf, registre_engagements.montant_depense_neuf, 0) as amount')
                ->get();

            foreach ($aoos as $row) {
                if (BudgetHierarchyService::isOrdonnancementCancelledOrRejected($row->statut)) {
                    continue;
                }
                $b = str_contains($row->bgt, 'FONCT') ? 'FONCTIONNEMENT' : 'INVESTISSEMENT';
                $map[$row->lid][$b] = ($map[$row->lid][$b] ?? 0) + (float) $row->amount;
            }

            // Group sum by marche.notification_ligne_id
            $marches = RegistreEngagement::query()
                ->join('marches', 'registre_engagements.marche_id', '=', 'marches.id')
                ->whereNotNull('marches.notification_ligne_id')
                ->selectRaw('marches.notification_ligne_id as lid, marches.statut as statut, UPPER(COALESCE(registre_engagements.budget, \'\')) as bgt, COALESCE(registre_engagements.montant_engager_neuf, registre_engagements.montant_depense_neuf, 0) as amount')
                ->get();

            foreach ($marches as $row) {
                if (BudgetHierarchyService::isOrdonnancementCancelledOrRejected($row->statut)) {
                    continue;
                }
                $b = str_contains($row->bgt, 'FONCT') ? 'FONCTIONNEMENT' : 'INVESTISSEMENT';
                $map[$row->lid][$b] = ($map[$row->lid][$b] ?? 0) + (float) $row->amount;
            }

            return $map;
        });

        $b = $domaine === 'FONCTIONNEMENT' ? 'FONCTIONNEMENT' : 'INVESTISSEMENT';
        return (float) ($data[$ligneId][$b] ?? 0);
    }

    protected static function getCachedMouvements(int $ligneId): float
    {
        $map = Cache::remember('notif_lignes_mouvements_map', 60, function () {
            return NotificationMouvement::query()
                ->where('type_credit', 'NEUF_CC')
                ->where('nature', 'ALIMENTATION')
                ->selectRaw('notification_ligne_id, sum(COALESCE(montant, 0)) as total')
                ->groupBy('notification_ligne_id')
                ->pluck('total', 'notification_ligne_id')
                ->all();
        });

        return (float) ($map[$ligneId] ?? 0);
    }

    public function getCreditsEngagesAttribute()
    {
        $domaine = strtoupper($this->domaine ?: ($this->relationLoaded('notification') ? $this->notification?->domaine : null) ?: 'INVESTISSEMENT');
        $engageNeuf = static::getCachedEngageNeuf($this->id, $domaine);

        if ($domaine === 'FONCTIONNEMENT') {
            return round(
                max(0, floatval($this->reports ?? 0) - floatval($this->diminution_report ?? 0))
                + floatval($engageNeuf),
                2
            );
        }

        $creditsConsolides = static::getCachedMouvements($this->id);

        return round(
            max(0, floatval($this->reports ?? 0) - floatval($this->diminution_report ?? 0))
            + max(0, floatval($this->credits_engagements ?? 0) - floatval($this->diminution_credit_engagement ?? 0))
            + floatval($creditsConsolides)
            + floatval($engageNeuf),
            2
        );
    }

    public function getCreditsDisponiblesAttribute()
    {
        $netCreditsNeufs = max(0, floatval($this->credits_neufs ?? 0) - floatval($this->diminution_credit_neuf ?? 0));
        $domaine = strtoupper($this->domaine ?: ($this->relationLoaded('notification') ? $this->notification?->domaine : null) ?: 'INVESTISSEMENT');
        $engageNeuf = static::getCachedEngageNeuf($this->id, $domaine);

        return round(max(0, $netCreditsNeufs - $engageNeuf), 2);
    }
}
