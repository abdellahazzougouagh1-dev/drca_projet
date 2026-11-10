<?php

namespace App\Services;

use App\Models\Aoo;
use App\Models\Consultation;
use App\Models\Engagement;
use App\Models\Lot;
use App\Models\Marche;
use App\Models\Notification;
use App\Models\NotificationLigne;
use App\Models\Ordonnancement;
use App\Models\OrdonnancementOrdre;
use App\Models\RegistreEngagement;
use Illuminate\Validation\ValidationException;

class BudgetHierarchyService
{
    public const EPSILON = 0.01;

    public static function normalizeDomaine(?string $domaine): string
    {
        $upper = mb_strtoupper(trim((string) $domaine), 'UTF-8');
        return str_contains($upper, 'FONCT') ? 'FONCTIONNEMENT' : 'INVESTISSEMENT';
    }

    public static function imputationKey(?string $domaine, ?string $art, ?string $par, ?string $lig): string
    {
        return self::normalizeDomaine($domaine)
            . '_'
            . trim((string) $art)
            . '/'
            . trim((string) $par)
            . '/'
            . trim((string) $lig);
    }

    public static function normalizeStatusKey(?string $status): string
    {
        $s = mb_strtolower(trim((string) $status), 'UTF-8');
        return strtr($s, [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'ô' => 'o', 'ö' => 'o',
            'î' => 'i', 'ï' => 'i',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ]);
    }

    public static function isOrdonnancementCancelledOrRejected(?string $status): bool
    {
        return in_array(self::normalizeStatusKey($status), ['rejete', 'annule'], true);
    }

    public static function isOrdonnancementPaid(?string $status): bool
    {
        return in_array(self::normalizeStatusKey($status), ['paye', 'cloture'], true);
    }

    public static function isOrdreActive(?string $status): bool
    {
        return !self::isOrdonnancementCancelledOrRejected($status);
    }

    public static function isOrdrePaid(?string $status): bool
    {
        return self::isOrdonnancementPaid($status);
    }

    public static function isMarcheLiquidationValidated(?string $status): bool
    {
        return in_array(self::normalizeStatusKey($status), [
            'valide',
            'validee',
            'transmise a l\'ordonnancement',
            'ordonnancee',
            'partiellement ordonnancee',
            'totalement ordonnancee',
        ], true);
    }

    public static function classifyOrdreCategory(OrdonnancementOrdre $ordre): string
    {
        $texte = mb_strtolower(($ordre->creance ?? '') . ' ' . ($ordre->type_mouvement ?? ''), 'UTF-8');
        if (str_contains($texte, 'report')) {
            return 'reports';
        }
        if (str_contains($texte, 'consolid')) {
            return 'credits_consolides';
        }
        if (str_contains($texte, 'neuf')) {
            return 'credits_neufs';
        }
        if (
            str_contains($texte, 'retenue')
            || str_contains($texte, 'tva')
            || str_contains($texte, 'ias')
            || str_contains($texte, 'ras')
        ) {
            return 'ras';
        }
        return 'rap';
    }

    /**
     * Compute active ordonnanced and paid amounts (overall and by category) for one Ordonnancement dossier.
     * Guarantees 0 <= total_paiements <= total_ordonnance and avoids double-counting 0-ordre breakdowns.
     */
    public static function computeOrdonnancementAmounts(Ordonnancement $ord): array
    {
        $zeroBuckets = [
            'reports' => 0.0,
            'credits_consolides' => 0.0,
            'credits_neufs' => 0.0,
            'ras' => 0.0,
            'rap' => 0.0,
        ];

        $result = [
            'ordonnance' => $zeroBuckets,
            'paiement' => $zeroBuckets,
            'total_ordonnance' => 0.0,
            'total_paiements' => 0.0,
        ];

        if (self::isOrdonnancementCancelledOrRejected($ord->statut)) {
            return $result;
        }

        $ordres = $ord->relationLoaded('ordres') ? $ord->ordres : $ord->ordres()->get();

        if ($ordres->isNotEmpty()) {
            $dossierPaid = self::isOrdonnancementPaid($ord->statut);

            foreach ($ordres as $ordre) {
                if (!self::isOrdreActive($ordre->statut)) {
                    continue;
                }

                $type = self::classifyOrdreCategory($ordre);
                $montant = max(0.0, (float) $ordre->montant);

                $result['ordonnance'][$type] += $montant;
                $result['total_ordonnance'] += $montant;

                if ($dossierPaid || self::isOrdrePaid($ordre->statut)) {
                    $result['paiement'][$type] += $montant;
                    $result['total_paiements'] += $montant;
                }
            }

            $result['total_ordonnance'] = round($result['total_ordonnance'], 2);
            $result['total_paiements'] = round(min($result['total_paiements'], $result['total_ordonnance']), 2);
            return $result;
        }

        $creanceLower = mb_strtolower($ord->creance ?? '', 'UTF-8');
        $rawBrut = max(0.0, (float) ($ord->montant_brut ?? 0));
        $rawNet = max(0.0, (float) ($ord->net_a_payer ?? 0));
        $rawReports = max(0.0, (float) ($ord->paiement_reports ?? 0));
        $rawConsolid = max(0.0, (float) ($ord->credit_consolide ?? 0));
        $rawNeufs = max(0.0, (float) ($ord->credit_neuf ?? 0));
        $rawRas = max(0.0, (float) ($ord->ras_total ?? 0));
        $rawRetenues = max(0.0, (float) ($ord->retenue_tva ?? 0) + (float) ($ord->retenue_ias ?? 0) + (float) ($ord->autres_retenues ?? 0));
        $rawRap = max(0.0, (float) ($ord->rap_total ?? 0));

        $mReports = $rawReports > 0 ? $rawReports : (str_contains($creanceLower, 'report') ? $rawBrut : 0.0);
        $mConsolid = $rawConsolid > 0 ? $rawConsolid : (str_contains($creanceLower, 'consolid') ? $rawBrut : 0.0);
        $mNeufs = $rawNeufs > 0 ? $rawNeufs : (str_contains($creanceLower, 'neuf') ? $rawBrut : 0.0);
        $mRas = $rawRas > 0 ? $rawRas : $rawRetenues;
        $mRap = $rawRap > 0 ? $rawRap : (str_contains($creanceLower, 'reste') ? $rawNet : 0.0);

        $fallbackSum = max($mReports + $mConsolid + $mNeufs, $mRas + $mRap);
        $mBrut = $rawBrut > 0 ? $rawBrut : ($fallbackSum > 0 ? $fallbackSum : $rawNet);

        $isPaid = self::isOrdonnancementPaid($ord->statut);

        $result['ordonnance'] = [
            'reports' => round($mReports, 2),
            'credits_consolides' => round($mConsolid, 2),
            'credits_neufs' => round($mNeufs, 2),
            'ras' => round($mRas, 2),
            'rap' => round($mRap, 2),
        ];
        $result['total_ordonnance'] = round($mBrut, 2);

        if ($isPaid) {
            $result['paiement'] = $result['ordonnance'];
            $result['total_paiements'] = round($mBrut, 2);
        }

        return $result;
    }

    public static function resolveNotificationLigneForDossier(
        ?int $notificationLigneId,
        ?string $art,
        ?string $par,
        ?string $lig,
        ?string $domaine,
        int|string|null $exercice
    ): ?NotificationLigne {
        $art = trim((string) $art);
        $par = trim((string) $par);
        $lig = trim((string) $lig);
        $targetDom = self::normalizeDomaine($domaine);
        $targetExercice = $exercice ? (int) $exercice : null;

        if ($notificationLigneId) {
            $ligne = NotificationLigne::with('notification')->find($notificationLigneId);
            if ($ligne) {
                $ligneDom = self::normalizeDomaine($ligne->domaine ?: $ligne->notification?->domaine);
                $ligneEx = $ligne->notification?->exercice ? (int) $ligne->notification->exercice : null;

                $sameImputation = ($art === '' && $par === '' && $lig === '')
                    || (
                        trim((string) $ligne->article) === $art
                        && trim((string) $ligne->paragraphe) === $par
                        && trim((string) $ligne->ligne_budgetaire) === $lig
                    );
                $sameDom = $domaine === null || $domaine === '' || $ligneDom === $targetDom;
                $sameEx = $targetExercice === null || $ligneEx === $targetExercice;

                if ($sameImputation && $sameDom && $sameEx) {
                    return $ligne;
                }
            }
        }

        if ($art === '' || $par === '' || $lig === '') {
            return null;
        }

        $query = NotificationLigne::with('notification')
            ->where('article', $art)
            ->where('paragraphe', $par)
            ->where('ligne_budgetaire', $lig);

        if ($targetExercice !== null) {
            $query->whereHas('notification', fn ($q) => $q->where('exercice', $targetExercice));
        }

        return $query->get()->first(function (NotificationLigne $ligne) use ($targetDom) {
            $ligneDom = self::normalizeDomaine($ligne->domaine ?: $ligne->notification?->domaine);
            return $ligneDom === $targetDom;
        });
    }

    public static function resolveConsultationEngagedAmount(Consultation $consultation): float
    {
        $consultation->loadMissing(['registreEngagement', 'engagement']);

        if (self::isOrdonnancementCancelledOrRejected($consultation->statut_dossier)) {
            return 0.0;
        }

        if ($consultation->registreEngagement) {
            $reg = $consultation->registreEngagement;
            return round(max(0.0, (float) ($reg->montant_engager_neuf ?? $reg->montant_depense_neuf ?? 0)), 2);
        }

        if ($consultation->engagement) {
            return round(max(0.0, (float) ($consultation->engagement->montant_global ?? 0)), 2);
        }

        if ($consultation->montant_engager_neuf !== null) {
            return round(max(0.0, (float) $consultation->montant_engager_neuf), 2);
        }

        if ($consultation->montant_depense_neuf !== null && !empty($consultation->numero_engagement)) {
            return round(max(0.0, (float) $consultation->montant_depense_neuf), 2);
        }

        return 0.0;
    }

    public static function resolveMarcheEngagedAmount(Marche $marche): float
    {
        $marche->loadMissing(['registreEngagement']);

        if (self::isOrdonnancementCancelledOrRejected($marche->statut)) {
            return 0.0;
        }

        if ($marche->registreEngagement) {
            $reg = $marche->registreEngagement;
            return round(max(0.0, (float) ($reg->montant_engager_neuf ?? $reg->montant_depense_neuf ?? 0)), 2);
        }

        $engagedStatuses = [
            'engagement_validee',
            'liquidation_en_cours',
            'liquidation_validee',
            'ordonnancement_en_cours',
            'ordonnancement_validee',
            'cloture',
            'cloture_en_cours',
            'cloture_validee',
        ];

        if (!empty($marche->num_engagement) || in_array((string) $marche->statut, $engagedStatuses, true)) {
            $val = ((float) ($marche->engagement_propose_cp ?? 0) > 0)
                ? (float) $marche->engagement_propose_cp
                : (float) ($marche->montant ?? 0);
            return round(max(0.0, $val), 2);
        }

        return 0.0;
    }

    public static function getActiveOrdonnancedForConsultation(int $consultationId, ?int $excludeOrdId = null): float
    {
        $query = Ordonnancement::with('ordres')->where('consultation_id', $consultationId);
        if ($excludeOrdId !== null) {
            $query->where('id', '!=', $excludeOrdId);
        }

        return round((float) $query->get()->sum(function (Ordonnancement $ord) {
            return self::computeOrdonnancementAmounts($ord)['total_ordonnance'];
        }), 2);
    }

    public static function getActiveOrdonnancedForMarche(int $marcheId, ?int $excludeOrdId = null): float
    {
        $query = Ordonnancement::with('ordres')->where('marche_id', $marcheId);
        if ($excludeOrdId !== null) {
            $query->where('id', '!=', $excludeOrdId);
        }

        return round((float) $query->get()->sum(function (Ordonnancement $ord) {
            return self::computeOrdonnancementAmounts($ord)['total_ordonnance'];
        }), 2);
    }

    public static function getActiveOrdonnancedForLiquidation(int $liquidationId, ?int $excludeOrdId = null): float
    {
        $query = Ordonnancement::with('ordres')->where('liquidation_id', $liquidationId);
        if ($excludeOrdId !== null) {
            $query->where('id', '!=', $excludeOrdId);
        }

        return round((float) $query->get()->sum(function (Ordonnancement $ord) {
            return self::computeOrdonnancementAmounts($ord)['total_ordonnance'];
        }), 2);
    }

    /**
     * Build a complete fiscal-year budget hierarchy snapshot (notifications -> engagements -> ordonnancements -> paiements).
     */
    public static function computeExerciceSnapshot(int|string $exercice, bool $lockForUpdate = false, array $options = []): array
    {
        $ex = (int) $exercice;
        $excludeConsultationId = isset($options['exclude_consultation_id']) ? (int) $options['exclude_consultation_id'] : null;
        $excludeMarcheId = isset($options['exclude_marche_id']) ? (int) $options['exclude_marche_id'] : null;
        $excludeAooId = isset($options['exclude_aoo_id']) ? (int) $options['exclude_aoo_id'] : null;
        $excludeEngagementId = isset($options['exclude_engagement_id']) ? (int) $options['exclude_engagement_id'] : null;
        $excludeRegistreId = isset($options['exclude_registre_id']) ? (int) $options['exclude_registre_id'] : null;
        $excludeOrdonnancementId = isset($options['exclude_ordonnancement_id']) ? (int) $options['exclude_ordonnancement_id'] : null;
        $excludeNotificationId = isset($options['exclude_notification_id']) ? (int) $options['exclude_notification_id'] : null;
        $overrideLigneId = isset($options['override_ligne_id']) ? (int) $options['override_ligne_id'] : null;
        $overrideLigneData = $options['override_ligne_data'] ?? null;

        if ($lockForUpdate) {
            Notification::where('exercice', $ex)->lockForUpdate()->get();
        }

        $lignesQuery = NotificationLigne::with(['notification', 'mouvements'])
            ->whereHas('notification', function ($q) use ($ex, $excludeNotificationId) {
                $q->where('exercice', $ex);
                if ($excludeNotificationId !== null) {
                    $q->where('id', '!=', $excludeNotificationId);
                }
            });

        if ($lockForUpdate) {
            $lignesQuery->lockForUpdate();
        }

        $lignes = $lignesQuery->get();

        $groups = [];
        $ligneIdToGroup = [];

        foreach ($lignes as $ligne) {
            $effectiveArt = $ligne->article;
            $effectivePar = $ligne->paragraphe;
            $effectiveLig = $ligne->ligne_budgetaire;
            $effectiveDomRaw = $ligne->domaine ?: $ligne->notification?->domaine;
            $reports = (float) ($ligne->reports ?? 0);
            $dimReports = (float) ($ligne->diminution_report ?? 0);
            $creditsNeufs = (float) ($ligne->credits_neufs ?? 0);
            $dimNeufs = (float) ($ligne->diminution_credit_neuf ?? 0);
            $creditsEng = (float) ($ligne->credits_engagements ?? 0);
            $dimEng = (float) ($ligne->diminution_credit_engagement ?? 0);

            if ($overrideLigneId !== null && (int) $ligne->id === $overrideLigneId && is_array($overrideLigneData)) {
                if (array_key_exists('article', $overrideLigneData)) {
                    $effectiveArt = $overrideLigneData['article'];
                }
                if (array_key_exists('paragraphe', $overrideLigneData)) {
                    $effectivePar = $overrideLigneData['paragraphe'];
                }
                if (array_key_exists('ligne_budgetaire', $overrideLigneData)) {
                    $effectiveLig = $overrideLigneData['ligne_budgetaire'];
                }
                if (array_key_exists('domaine', $overrideLigneData) && !empty($overrideLigneData['domaine'])) {
                    $effectiveDomRaw = $overrideLigneData['domaine'];
                }
                if (array_key_exists('reports', $overrideLigneData)) {
                    $reports = (float) $overrideLigneData['reports'];
                }
                if (array_key_exists('diminution_report', $overrideLigneData)) {
                    $dimReports = (float) $overrideLigneData['diminution_report'];
                }
                if (array_key_exists('credits_neufs', $overrideLigneData)) {
                    $creditsNeufs = (float) $overrideLigneData['credits_neufs'];
                }
                if (array_key_exists('diminution_credit_neuf', $overrideLigneData)) {
                    $dimNeufs = (float) $overrideLigneData['diminution_credit_neuf'];
                }
                if (array_key_exists('credits_engagements', $overrideLigneData)) {
                    $creditsEng = (float) $overrideLigneData['credits_engagements'];
                }
                if (array_key_exists('diminution_credit_engagement', $overrideLigneData)) {
                    $dimEng = (float) $overrideLigneData['diminution_credit_engagement'];
                }
            }

            $dom = self::normalizeDomaine($effectiveDomRaw);
            $key = self::imputationKey($dom, $effectiveArt, $effectivePar, $effectiveLig);
            $ligneIdToGroup[$ligne->id] = $key;

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'domaine' => $dom,
                    'imputation' => trim((string) $effectiveArt) . '/' . trim((string) $effectivePar) . '/' . trim((string) $effectiveLig),
                    'article' => trim((string) $effectiveArt),
                    'paragraphe' => trim((string) $effectivePar),
                    'ligne_budgetaire' => trim((string) $effectiveLig),
                    'libelle' => $ligne->libelle,
                    'ligne_ids' => [],
                    'reports' => 0.0,
                    'diminution_report' => 0.0,
                    'credits_neufs' => 0.0,
                    'diminution_credit_neuf' => 0.0,
                    'credits_engagements' => 0.0,
                    'diminution_credit_engagement' => 0.0,
                    'credits_consolides' => 0.0,
                    'total_credits' => 0.0,
                    'net_reports' => 0.0,
                    'net_credits_neufs' => 0.0,
                    'net_credits_engagements' => 0.0,
                    'total_net' => 0.0,
                    'fixed_engaged' => 0.0,
                    'engage_neuf' => 0.0,
                    'total_engaged' => 0.0,
                    'available_neuf' => 0.0,
                    'available_total' => 0.0,
                    'total_ordonnanced' => 0.0,
                    'total_paid' => 0.0,
                ];
            }

            $consolides = (float) $ligne->mouvements
                ->where('type_credit', 'NEUF_CC')
                ->where('nature', 'ALIMENTATION')
                ->sum('montant');

            $groups[$key]['ligne_ids'][] = $ligne->id;
            $groups[$key]['reports'] += $reports;
            $groups[$key]['diminution_report'] += $dimReports;
            $groups[$key]['credits_neufs'] += $creditsNeufs;
            $groups[$key]['diminution_credit_neuf'] += $dimNeufs;
            $groups[$key]['credits_engagements'] += $creditsEng;
            $groups[$key]['diminution_credit_engagement'] += $dimEng;
            $groups[$key]['credits_consolides'] += $consolides;
            $groups[$key]['total_credits'] += ($reports + $creditsNeufs + $creditsEng);
        }

        foreach ($groups as &$group) {
            $group['net_reports_raw'] = $group['reports'] - $group['diminution_report'];
            $group['net_credits_neufs_raw'] = $group['credits_neufs'] - $group['diminution_credit_neuf'];
            $group['net_credits_engagements_raw'] = $group['credits_engagements'] - $group['diminution_credit_engagement'];

            $group['net_reports'] = max(0.0, $group['net_reports_raw']);
            $group['net_credits_neufs'] = max(0.0, $group['net_credits_neufs_raw']);
            $group['net_credits_engagements'] = max(0.0, $group['net_credits_engagements_raw']);
            $group['total_net'] = round($group['net_reports'] + $group['net_credits_neufs'] + $group['net_credits_engagements'], 2);

            if ($group['domaine'] === 'FONCTIONNEMENT') {
                $group['fixed_engaged'] = round($group['net_reports'], 2);
            } else {
                $group['fixed_engaged'] = round(
                    $group['net_reports'] + $group['net_credits_engagements'] + $group['credits_consolides'],
                    2
                );
            }
        }
        unset($group);

        // 1. Aggregate RegistreEngagement records for this fiscal year
        $regQuery = RegistreEngagement::with([
            'consultation.budget',
            'consultation.notificationLigne.notification',
            'marche.notificationLigne.notification',
            'aoo.notificationLigne.notification',
            'engagement',
        ]);
        if ($lockForUpdate) {
            $regQuery->lockForUpdate();
        }
        $registres = $regQuery->get();

        $orphanEngageNeuf = 0.0;

        foreach ($registres as $reg) {
            if ($excludeRegistreId !== null && (int) $reg->id === $excludeRegistreId) {
                continue;
            }
            if ($excludeConsultationId !== null && $reg->consultation_id && (int) $reg->consultation_id === $excludeConsultationId) {
                continue;
            }
            if ($excludeMarcheId !== null && $reg->marche_id && (int) $reg->marche_id === $excludeMarcheId) {
                continue;
            }
            if ($excludeAooId !== null && $reg->aoo_id && (int) $reg->aoo_id === $excludeAooId) {
                continue;
            }
            if ($excludeEngagementId !== null && $reg->engagement_id && (int) $reg->engagement_id === $excludeEngagementId) {
                continue;
            }

            if ($reg->consultation && self::isOrdonnancementCancelledOrRejected($reg->consultation->statut_dossier)) {
                continue;
            }
            if ($reg->marche && self::isOrdonnancementCancelledOrRejected($reg->marche->statut)) {
                continue;
            }
            if ($reg->aoo && self::isOrdonnancementCancelledOrRejected($reg->aoo->statut)) {
                continue;
            }

            $regEx = null;
            $linkedLigneId = null;
            $domRaw = $reg->budget;
            $art = $reg->art;
            $par = $reg->par;
            $lig = $reg->lig;

            if ($reg->consultation) {
                $c = $reg->consultation;
                $regEx = $c->budget?->exercice_budgetaire ?: $c->annee ?: $c->notificationLigne?->notification?->exercice;
                $linkedLigneId = $c->notification_ligne_id;
                $domRaw = $domRaw ?: $c->type_budget;
                $art = $art ?: $c->budget?->art;
                $par = $par ?: $c->budget?->par;
                $lig = $lig ?: $c->budget?->lig;
            } elseif ($reg->marche) {
                $m = $reg->marche;
                $regEx = $m->exercice ?: $m->notificationLigne?->notification?->exercice;
                $linkedLigneId = $m->notification_ligne_id;
                $domRaw = $domRaw ?: $m->type_budget;
                $art = $art ?: $m->article_budget;
                $par = $par ?: $m->paragraphe_budget;
                $lig = $lig ?: $m->ligne_budget;
            } elseif ($reg->aoo) {
                $a = $reg->aoo;
                $regEx = $a->notificationLigne?->notification?->exercice ?: ($a->date_preparation?->format('Y'));
                $linkedLigneId = $a->notification_ligne_id;
                $domRaw = $domRaw ?: $a->type_budget;
                $art = $art ?: $a->art;
                $par = $par ?: $a->par;
                $lig = $lig ?: $a->lig;
            }

            $regEx = (int) ($regEx ?: ($reg->date_engagement?->format('Y') ?? 0));
            if ($regEx !== $ex) {
                continue;
            }

            $amount = max(0.0, (float) ($reg->montant_engager_neuf ?? $reg->montant_depense_neuf ?? 0));
            $gKey = null;
            if ($linkedLigneId && isset($ligneIdToGroup[$linkedLigneId])) {
                $gKey = $ligneIdToGroup[$linkedLigneId];
            } else {
                $candidate = self::imputationKey($domRaw, $art, $par, $lig);
                if (isset($groups[$candidate])) {
                    $gKey = $candidate;
                }
            }

            if ($gKey !== null) {
                $groups[$gKey]['engage_neuf'] += $amount;
            } else {
                $orphanEngageNeuf += $amount;
            }
        }

        // 2. Include un-synced Engagement records (not yet backed by a RegistreEngagement)
        $unsyncedEngagementsQuery = Engagement::with([
            'consultation.budget',
            'consultation.notificationLigne.notification',
        ])->whereNotExists(function ($q) {
            $q->selectRaw('1')
                ->from('registre_engagements')
                ->where(function ($sub) {
                    $sub->whereColumn('registre_engagements.engagement_id', 'engagements.id')
                        ->orWhereColumn('registre_engagements.consultation_id', 'engagements.consultation_id');
                });
        });

        if ($lockForUpdate) {
            $unsyncedEngagementsQuery->lockForUpdate();
        }

        foreach ($unsyncedEngagementsQuery->get() as $eng) {
            if ($excludeEngagementId !== null && (int) $eng->id === $excludeEngagementId) {
                continue;
            }
            if ($excludeConsultationId !== null && $eng->consultation_id && (int) $eng->consultation_id === $excludeConsultationId) {
                continue;
            }
            if ($eng->consultation && self::isOrdonnancementCancelledOrRejected($eng->consultation->statut_dossier)) {
                continue;
            }

            $c = $eng->consultation;
            $engEx = (int) ($c?->budget?->exercice_budgetaire ?: $c?->annee ?: $c?->notificationLigne?->notification?->exercice ?: ($eng->date_engagement?->format('Y') ?? 0));
            if ($engEx !== $ex) {
                continue;
            }

            $amount = max(0.0, (float) ($eng->montant_global ?? 0));
            $gKey = null;
            if ($c?->notification_ligne_id && isset($ligneIdToGroup[$c->notification_ligne_id])) {
                $gKey = $ligneIdToGroup[$c->notification_ligne_id];
            } elseif ($c) {
                $candidate = self::imputationKey($c->type_budget, $c->budget?->art, $c->budget?->par, $c->budget?->lig);
                if (isset($groups[$candidate])) {
                    $gKey = $candidate;
                }
            }

            if ($gKey !== null) {
                $groups[$gKey]['engage_neuf'] += $amount;
            } else {
                $orphanEngageNeuf += $amount;
            }
        }

        foreach ($groups as &$group) {
            $group['engage_neuf'] = round($group['engage_neuf'], 2);
            $group['total_engaged'] = round($group['fixed_engaged'] + $group['engage_neuf'], 2);
            $group['available_neuf'] = round(max(0.0, $group['net_credits_neufs'] - $group['engage_neuf']), 2);
            $group['available_total'] = round(max(0.0, $group['total_net'] - $group['total_engaged']), 2);
        }
        unset($group);

        // 3. Aggregate Ordonnancement & Payment totals
        $ordQuery = Ordonnancement::with([
            'ordres',
            'consultation.budget',
            'consultation.notificationLigne',
            'marche.notificationLigne',
            'notificationLigne',
        ])->where('exercice', (string) $ex);

        if ($lockForUpdate) {
            $ordQuery->lockForUpdate();
        }

        $orphanOrdonnanced = 0.0;
        $orphanPaid = 0.0;

        foreach ($ordQuery->get() as $ord) {
            if ($excludeOrdonnancementId !== null && (int) $ord->id === $excludeOrdonnancementId) {
                continue;
            }

            $amounts = self::computeOrdonnancementAmounts($ord);
            $ordAmt = $amounts['total_ordonnance'];
            $paidAmt = $amounts['total_paiements'];

            if ($ordAmt <= 0 && $paidAmt <= 0) {
                continue;
            }

            $lid = $ord->notification_ligne_id
                ?: $ord->notificationLigne?->id
                ?: $ord->consultation?->notification_ligne_id
                ?: $ord->marche?->notification_ligne_id;

            $gKey = ($lid && isset($ligneIdToGroup[$lid])) ? $ligneIdToGroup[$lid] : null;

            if ($gKey === null) {
                $domRaw = $ord->budget_type ?: $ord->domaine ?: $ord->consultation?->type_budget ?: $ord->marche?->type_budget;
                $art = $ord->article ?: $ord->art ?: $ord->consultation?->budget?->art ?: $ord->marche?->article_budget;
                $par = $ord->paragraphe ?: $ord->par ?: $ord->consultation?->budget?->par ?: $ord->marche?->paragraphe_budget;
                $lig = $ord->ligne ?: $ord->ligne_budgetaire ?: $ord->lig ?: $ord->consultation?->budget?->lig ?: $ord->marche?->ligne_budget;
                if ($art && $par && $lig) {
                    $candidate = self::imputationKey($domRaw, $art, $par, $lig);
                    if (isset($groups[$candidate])) {
                        $gKey = $candidate;
                    }
                }
            }

            if ($gKey !== null) {
                $groups[$gKey]['total_ordonnanced'] = round($groups[$gKey]['total_ordonnanced'] + $ordAmt, 2);
                $groups[$gKey]['total_paid'] = round($groups[$gKey]['total_paid'] + $paidAmt, 2);
            } else {
                $orphanOrdonnanced += $ordAmt;
                $orphanPaid += $paidAmt;
            }
        }

        $totalCredits = 0.0;
        $totalNet = 0.0;
        $netCreditsNeufs = 0.0;
        $fixedEngaged = 0.0;
        $engageNeuf = $orphanEngageNeuf;
        $totalEngaged = $orphanEngageNeuf;
        $totalOrdonnanced = $orphanOrdonnanced;
        $totalPaid = $orphanPaid;

        foreach ($groups as $g) {
            $totalCredits += $g['total_credits'];
            $totalNet += $g['total_net'];
            $netCreditsNeufs += $g['net_credits_neufs'];
            $fixedEngaged += $g['fixed_engaged'];
            $engageNeuf += $g['engage_neuf'];
            $totalEngaged += $g['total_engaged'];
            $totalOrdonnanced += $g['total_ordonnanced'];
            $totalPaid += $g['total_paid'];
        }

        return [
            'exercice' => $ex,
            'has_notifications' => $lignes->isNotEmpty(),
            'groups' => $groups,
            'ligne_id_to_group' => $ligneIdToGroup,
            'total_credits' => round($totalCredits, 2),
            'total_net' => round($totalNet, 2),
            'net_credits_neufs' => round($netCreditsNeufs, 2),
            'fixed_engaged' => round($fixedEngaged, 2),
            'engage_neuf' => round($engageNeuf, 2),
            'total_engaged' => round($totalEngaged, 2),
            'available_neuf' => round(max(0.0, $netCreditsNeufs - $engageNeuf), 2),
            'available_total' => round(max(0.0, $totalNet - $totalEngaged), 2),
            'total_ordonnanced' => round($totalOrdonnanced, 2),
            'total_paid' => round($totalPaid, 2),
            'orphan_engage_neuf' => round($orphanEngageNeuf, 2),
            'orphan_ordonnanced' => round($orphanOrdonnanced, 2),
            'orphan_paid' => round($orphanPaid, 2),
        ];
    }

    public static function fmt(float $amount): string
    {
        return number_format($amount, 2, ',', ' ');
    }

    public static function assertEngagementWithinNotification(array $params): void
    {
        $exercice = (int) ($params['exercice'] ?? date('Y'));
        $domaine = self::normalizeDomaine($params['domaine'] ?? 'INVESTISSEMENT');
        $art = trim((string) ($params['art'] ?? ''));
        $par = trim((string) ($params['par'] ?? ''));
        $lig = trim((string) ($params['lig'] ?? ''));
        $notificationLigneId = isset($params['notification_ligne_id']) ? (int) $params['notification_ligne_id'] : null;
        $proposed = round((float) ($params['proposed_engage_neuf'] ?? 0), 2);
        $creditOuvertCp = isset($params['credit_ouvert_cp']) && $params['credit_ouvert_cp'] !== null
            ? (float) $params['credit_ouvert_cp']
            : null;
        $depensesAnterieuresCp = (float) ($params['depenses_anterieures_cp'] ?? 0);
        $existingActiveOrdonnanced = round((float) ($params['existing_active_ordonnanced'] ?? 0), 2);
        $exclude = $params['exclude'] ?? [];
        $errorField = $params['error_field'] ?? 'montant_engager_neuf';

        if ($proposed < -self::EPSILON) {
            throw ValidationException::withMessages([
                $errorField => ["Le montant de l'engagement ne peut pas être négatif."],
            ]);
        }

        if ($existingActiveOrdonnanced > 0 && ($proposed + self::EPSILON) < $existingActiveOrdonnanced) {
            throw ValidationException::withMessages([
                $errorField => [
                    "Règle budgétaire (Ordonnancement <= Engagement) : impossible de fixer l'engagement à "
                    . self::fmt($proposed) . " DH en dessous du montant déjà ordonnancé sur ce dossier ("
                    . self::fmt($existingActiveOrdonnanced) . " DH).",
                ],
            ]);
        }

        $snapshot = self::computeExerciceSnapshot($exercice, true, $exclude);

        if (!$snapshot['has_notifications']) {
            if ($creditOuvertCp !== null) {
                $cap = round(max(0.0, $creditOuvertCp - $depensesAnterieuresCp), 2);
                if ($proposed > ($cap + self::EPSILON)) {
                    throw ValidationException::withMessages([
                        $errorField => [
                            "Règle budgétaire (Engagement <= Notification) : le montant à engager ("
                            . self::fmt($proposed) . " DH) dépasse le crédit disponible déclaré ("
                            . self::fmt($cap) . " DH).",
                        ],
                    ]);
                }
            }
            return;
        }

        $groupKey = null;
        $computedKey = ($art !== '' && $par !== '' && $lig !== '')
            ? self::imputationKey($domaine, $art, $par, $lig)
            : null;

        if ($notificationLigneId && isset($snapshot['ligne_id_to_group'][$notificationLigneId])) {
            $linkedKey = $snapshot['ligne_id_to_group'][$notificationLigneId];
            if ($computedKey === null || $linkedKey === $computedKey) {
                $groupKey = $linkedKey;
            }
        }
        if ($groupKey === null && $computedKey !== null && isset($snapshot['groups'][$computedKey])) {
            $groupKey = $computedKey;
        }

        if ($groupKey === null || !isset($snapshot['groups'][$groupKey])) {
            $impLabel = ($art !== '' || $par !== '' || $lig !== '') ? "{$art}/{$par}/{$lig}" : 'non renseignée';
            throw ValidationException::withMessages([
                $errorField => [
                    "Règle budgétaire (Engagement <= Notification) : aucune ligne budgétaire notifiée ({$domaine} · {$impLabel}) n'existe pour l'exercice {$exercice}.",
                ],
            ]);
        }

        $group = $snapshot['groups'][$groupKey];
        $groupMaxNew = min(
            max(0.0, $group['net_credits_neufs'] - $group['engage_neuf']),
            max(0.0, min($group['total_net'], $group['total_credits']) - $group['total_engaged'])
        );

        if ($proposed > ($groupMaxNew + self::EPSILON)) {
            throw ValidationException::withMessages([
                $errorField => [
                    "Règle budgétaire (Engagement <= Notification) : le montant à engager ("
                    . self::fmt($proposed) . " DH) dépasse le crédit disponible de la ligne {$group['imputation']} ("
                    . self::fmt($groupMaxNew) . " DH disponible sur " . self::fmt($group['total_net']) . " DH notifié).",
                ],
            ]);
        }

        if (($group['total_engaged'] + $proposed + self::EPSILON) < $group['total_ordonnanced']) {
            throw ValidationException::withMessages([
                $errorField => [
                    "Règle budgétaire (Ordonnancement <= Engagement) : les engagements de la ligne {$group['imputation']} ne peuvent pas être inférieurs aux ordonnancements existants ("
                    . self::fmt($group['total_ordonnanced']) . " DH).",
                ],
            ]);
        }

        $exMaxNew = min(
            max(0.0, $snapshot['available_neuf']),
            max(0.0, min($snapshot['total_net'], $snapshot['total_credits']) - $snapshot['total_engaged'])
        );

        if ($proposed > ($exMaxNew + self::EPSILON)) {
            throw ValidationException::withMessages([
                $errorField => [
                    "Règle budgétaire (Engagement <= Notification) : le cumul des engagements de l'exercice {$exercice} ne peut pas dépasser les crédits notifiés ("
                    . self::fmt($exMaxNew) . " DH disponible).",
                ],
            ]);
        }
    }

    public static function assertOrdonnancementWithinEngagement(array $params): void
    {
        $exercice = (int) ($params['exercice'] ?? date('Y'));
        $domaine = self::normalizeDomaine($params['domaine'] ?? 'INVESTISSEMENT');
        $art = trim((string) ($params['art'] ?? ''));
        $par = trim((string) ($params['par'] ?? ''));
        $lig = trim((string) ($params['lig'] ?? ''));
        $notificationLigneId = isset($params['notification_ligne_id']) ? (int) $params['notification_ligne_id'] : null;
        $dossierEngaged = $params['dossier_engaged'] !== null ? round((float) $params['dossier_engaged'], 2) : null;
        $dossierAlreadyOrd = round((float) ($params['dossier_already_ordonnanced'] ?? 0), 2);
        $dossierLabel = $params['dossier_label'] ?? 'ce dossier';
        $proposedOrd = round((float) ($params['proposed_ordonnance'] ?? 0), 2);
        $excludeOrdId = isset($params['exclude_ordonnancement_id']) ? (int) $params['exclude_ordonnancement_id'] : null;
        $errorField = $params['error_field'] ?? 'ordres';

        if ($proposedOrd < -self::EPSILON) {
            throw ValidationException::withMessages([
                $errorField => ["Le montant de l'ordonnancement ne peut pas être négatif."],
            ]);
        }

        if ($dossierEngaged !== null) {
            if ($dossierEngaged <= self::EPSILON && $proposedOrd > self::EPSILON) {
                throw ValidationException::withMessages([
                    $errorField => [
                        "Règle budgétaire (Ordonnancement <= Engagement) : impossible d'ordonnancer sans engagement préalable validé (> 0 DH) pour {$dossierLabel}.",
                    ],
                ]);
            }

            if (($dossierAlreadyOrd + $proposedOrd) > ($dossierEngaged + self::EPSILON)) {
                $resteDossier = max(0.0, $dossierEngaged - $dossierAlreadyOrd);
                throw ValidationException::withMessages([
                    $errorField => [
                        "Règle budgétaire (Ordonnancement <= Engagement) : le cumul ordonnancé ("
                        . self::fmt($dossierAlreadyOrd + $proposedOrd) . " DH) dépasse le montant engagé ("
                        . self::fmt($dossierEngaged) . " DH) pour {$dossierLabel}. Reste autorisé : "
                        . self::fmt($resteDossier) . " DH.",
                    ],
                ]);
            }
        }

        $snapshot = self::computeExerciceSnapshot($exercice, true, [
            'exclude_ordonnancement_id' => $excludeOrdId,
        ]);

        $groupKey = null;
        if ($notificationLigneId && isset($snapshot['ligne_id_to_group'][$notificationLigneId])) {
            $groupKey = $snapshot['ligne_id_to_group'][$notificationLigneId];
        } elseif ($art !== '' && $par !== '' && $lig !== '') {
            $candidate = self::imputationKey($domaine, $art, $par, $lig);
            if (isset($snapshot['groups'][$candidate])) {
                $groupKey = $candidate;
            }
        }

        if ($groupKey !== null && isset($snapshot['groups'][$groupKey])) {
            $group = $snapshot['groups'][$groupKey];
            $groupCap = max(0.0, $group['total_engaged'] - $group['total_ordonnanced']);
            if ($proposedOrd > ($groupCap + self::EPSILON)) {
                throw ValidationException::withMessages([
                    $errorField => [
                        "Règle budgétaire (Ordonnancement <= Engagement) : l'ordonnancement ("
                        . self::fmt($proposedOrd) . " DH) dépasse le solde engagé disponible sur la ligne {$group['imputation']} ("
                        . self::fmt($groupCap) . " DH disponible sur " . self::fmt($group['total_engaged']) . " DH engagé).",
                    ],
                ]);
            }
            if (($group['total_paid'] - self::EPSILON) > ($group['total_ordonnanced'] + $proposedOrd)) {
                throw ValidationException::withMessages([
                    $errorField => [
                        "Règle budgétaire (Paiement <= Ordonnancement) : le total ordonnancé ne peut pas devenir inférieur au total déjà payé sur la ligne {$group['imputation']}.",
                    ],
                ]);
            }
        } elseif ($snapshot['has_notifications'] && $proposedOrd > self::EPSILON && ($dossierEngaged === null || $dossierEngaged <= self::EPSILON)) {
            throw ValidationException::withMessages([
                $errorField => [
                    "Règle budgétaire (Ordonnancement <= Engagement) : aucune ligne budgétaire engagée correspondante n'a été trouvée pour cet ordonnancement.",
                ],
            ]);
        }

        if ($snapshot['has_notifications'] || $snapshot['total_engaged'] > self::EPSILON) {
            $exCap = max(0.0, $snapshot['total_engaged'] - $snapshot['total_ordonnanced']);
            if ($proposedOrd > ($exCap + self::EPSILON)) {
                throw ValidationException::withMessages([
                    $errorField => [
                        "Règle budgétaire (Ordonnancement <= Engagement) : le total ordonnancé de l'exercice {$exercice} ne peut pas dépasser le total des engagements ("
                        . self::fmt($snapshot['total_engaged']) . " DH). Disponible : " . self::fmt($exCap) . " DH.",
                    ],
                ]);
            }
        }

        if (($snapshot['total_paid'] - self::EPSILON) > ($snapshot['total_ordonnanced'] + $proposedOrd)) {
            throw ValidationException::withMessages([
                $errorField => [
                    "Règle budgétaire (Paiement <= Ordonnancement) : le total ordonnancé ne peut pas devenir inférieur aux paiements déjà effectués.",
                ],
            ]);
        }
    }

    public static function assertPaiementWithinOrdonnancement(array $params): void
    {
        /** @var Ordonnancement $ord */
        $ord = $params['ordonnancement'];
        $proposedPaid = round((float) ($params['proposed_paid'] ?? 0), 2);
        $proposedActiveOrd = round((float) ($params['proposed_active_ordonnanced'] ?? 0), 2);
        $dossierStatut = $params['dossier_statut'] ?? $ord->statut;
        $errorField = $params['error_field'] ?? 'statut';

        if ($proposedPaid > self::EPSILON) {
            if (self::isOrdonnancementCancelledOrRejected($dossierStatut)) {
                throw ValidationException::withMessages([
                    $errorField => [
                        "Règle budgétaire (Paiement <= Ordonnancement) : un ordonnancement rejeté ou annulé ne peut pas être payé.",
                    ],
                ]);
            }

            if ($proposedActiveOrd <= self::EPSILON) {
                throw ValidationException::withMessages([
                    $errorField => [
                        "Règle budgétaire (Paiement <= Ordonnancement) : impossible d'enregistrer un paiement lorsque le montant ordonnancé actif est nul.",
                    ],
                ]);
            }

            if ($proposedPaid > ($proposedActiveOrd + self::EPSILON)) {
                throw ValidationException::withMessages([
                    $errorField => [
                        "Règle budgétaire (Paiement <= Ordonnancement) : le montant payé ("
                        . self::fmt($proposedPaid) . " DH) ne peut pas dépasser le montant ordonnancé ("
                        . self::fmt($proposedActiveOrd) . " DH).",
                    ],
                ]);
            }
        }

        $exercice = (int) ($ord->exercice ?: date('Y'));
        $snapshot = self::computeExerciceSnapshot($exercice, true, [
            'exclude_ordonnancement_id' => $ord->id,
        ]);

        $newExOrd = $snapshot['total_ordonnanced'] + $proposedActiveOrd;
        $newExPaid = $snapshot['total_paid'] + $proposedPaid;

        if ($newExPaid > ($newExOrd + self::EPSILON)) {
            throw ValidationException::withMessages([
                $errorField => [
                    "Règle budgétaire (Paiement <= Ordonnancement) : le total des paiements de l'exercice {$exercice} ("
                    . self::fmt($newExPaid) . " DH) dépasse le total ordonnancé ("
                    . self::fmt($newExOrd) . " DH).",
                ],
            ]);
        }

        if (($snapshot['has_notifications'] || $snapshot['total_engaged'] > self::EPSILON) && $newExOrd > ($snapshot['total_engaged'] + self::EPSILON)) {
            throw ValidationException::withMessages([
                $errorField => [
                    "Règle budgétaire (Ordonnancement <= Engagement) : le total ordonnancé de l'exercice {$exercice} ("
                    . self::fmt($newExOrd) . " DH) dépasse le total engagé ("
                    . self::fmt($snapshot['total_engaged']) . " DH).",
                ],
            ]);
        }

        $lid = $ord->notification_ligne_id
            ?: $ord->consultation?->notification_ligne_id
            ?: $ord->marche?->notification_ligne_id;
        $gKey = ($lid && isset($snapshot['ligne_id_to_group'][$lid])) ? $snapshot['ligne_id_to_group'][$lid] : null;
        if ($gKey === null && $ord->article && $ord->paragraphe && $ord->ligne) {
            $candidate = self::imputationKey($ord->budget_type ?: $ord->domaine, $ord->article, $ord->paragraphe, $ord->ligne);
            if (isset($snapshot['groups'][$candidate])) {
                $gKey = $candidate;
            }
        }

        if ($gKey !== null && isset($snapshot['groups'][$gKey])) {
            $group = $snapshot['groups'][$gKey];
            $newGroupOrd = $group['total_ordonnanced'] + $proposedActiveOrd;
            $newGroupPaid = $group['total_paid'] + $proposedPaid;

            if ($newGroupPaid > ($newGroupOrd + self::EPSILON)) {
                throw ValidationException::withMessages([
                    $errorField => [
                        "Règle budgétaire (Paiement <= Ordonnancement) : les paiements de la ligne {$group['imputation']} dépassent le montant ordonnancé.",
                    ],
                ]);
            }

            if ($newGroupOrd > ($group['total_engaged'] + self::EPSILON)) {
                throw ValidationException::withMessages([
                    $errorField => [
                        "Règle budgétaire (Ordonnancement <= Engagement) : les ordonnancements de la ligne {$group['imputation']} dépassent le montant engagé.",
                    ],
                ]);
            }
        }
    }

    public static function assertNotificationLigneUpdateValid(NotificationLigne $ligne, array $validated): void
    {
        $ligne->loadMissing('notification');
        $exercice = (int) ($ligne->notification?->exercice ?: date('Y'));

        $snapshot = self::computeExerciceSnapshot($exercice, true, [
            'override_ligne_id' => $ligne->id,
            'override_ligne_data' => $validated,
        ]);

        if ($snapshot['orphan_engage_neuf'] > self::EPSILON || $snapshot['orphan_ordonnanced'] > self::EPSILON || $snapshot['orphan_paid'] > self::EPSILON) {
            throw ValidationException::withMessages([
                'credits_neufs' => [
                    "Modification refusée : cette ligne porte des engagements ou ordonnancements qui ne peuvent pas être rattachés à une imputation valide.",
                ],
            ]);
        }

        foreach ($snapshot['groups'] as $group) {
            if ($group['net_reports_raw'] < -self::EPSILON || $group['net_credits_neufs_raw'] < -self::EPSILON || $group['net_credits_engagements_raw'] < -self::EPSILON) {
                throw ValidationException::withMessages([
                    'credits_neufs' => [
                        "Les diminutions de la ligne {$group['imputation']} ne peuvent pas dépasser les crédits ouverts.",
                    ],
                ]);
            }

            if ($group['engage_neuf'] > ($group['net_credits_neufs'] + self::EPSILON)) {
                throw ValidationException::withMessages([
                    'credits_neufs' => [
                        "Règle budgétaire (Engagement <= Notification) : la modification réduirait les crédits neufs nets de {$group['imputation']} ("
                        . self::fmt($group['net_credits_neufs']) . " DH) en dessous des engagements neufs existants ("
                        . self::fmt($group['engage_neuf']) . " DH).",
                    ],
                ]);
            }

            $netCap = min($group['total_net'], $group['total_credits']);
            if ($group['total_engaged'] > ($netCap + self::EPSILON)) {
                throw ValidationException::withMessages([
                    'credits_neufs' => [
                        "Règle budgétaire (Engagement <= Notification) : les crédits de {$group['imputation']} ne peuvent pas être inférieurs au total engagé ("
                        . self::fmt($group['total_engaged']) . " DH).",
                    ],
                ]);
            }

            if ($group['total_ordonnanced'] > ($group['total_engaged'] + self::EPSILON)) {
                throw ValidationException::withMessages([
                    'credits_neufs' => [
                        "Règle budgétaire (Ordonnancement <= Engagement) : le total engagé de {$group['imputation']} ne peut pas devenir inférieur au total ordonnancé.",
                    ],
                ]);
            }

            if ($group['total_paid'] > ($group['total_ordonnanced'] + self::EPSILON)) {
                throw ValidationException::withMessages([
                    'credits_neufs' => [
                        "Règle budgétaire (Paiement <= Ordonnancement) : le total ordonnancé de {$group['imputation']} ne peut pas devenir inférieur au total payé.",
                    ],
                ]);
            }
        }

        $exNetCap = min($snapshot['total_net'], $snapshot['total_credits']);
        if ($snapshot['total_engaged'] > ($exNetCap + self::EPSILON)) {
            throw ValidationException::withMessages([
                'credits_neufs' => [
                    "Règle budgétaire (Engagement <= Notification) : le total notifié de l'exercice {$exercice} ne peut pas devenir inférieur aux engagements existants ("
                    . self::fmt($snapshot['total_engaged']) . " DH).",
                ],
            ]);
        }
    }

    public static function assertNotificationDeletableAndRelink(Notification $notification): void
    {
        $notification->loadMissing('lignes');
        $exercice = (int) $notification->exercice;

        $snapshotAfter = self::computeExerciceSnapshot($exercice, true, [
            'exclude_notification_id' => $notification->id,
        ]);

        if (
            $snapshotAfter['orphan_engage_neuf'] > self::EPSILON
            || $snapshotAfter['orphan_ordonnanced'] > self::EPSILON
            || $snapshotAfter['orphan_paid'] > self::EPSILON
        ) {
            throw ValidationException::withMessages([
                'notification' => [
                    "Suppression impossible : cette notification couvre des lignes déjà consommées par des engagements, ordonnancements ou paiements existants.",
                ],
            ]);
        }

        foreach ($snapshotAfter['groups'] as $group) {
            $netCap = min($group['total_net'], $group['total_credits']);
            if ($group['engage_neuf'] > ($group['net_credits_neufs'] + self::EPSILON) || $group['total_engaged'] > ($netCap + self::EPSILON)) {
                throw ValidationException::withMessages([
                    'notification' => [
                        "Suppression impossible : la ligne {$group['imputation']} n'aurait plus assez de crédits notifiés pour couvrir les engagements existants ("
                        . self::fmt($group['total_engaged']) . " DH).",
                    ],
                ]);
            }
            if ($group['total_ordonnanced'] > ($group['total_engaged'] + self::EPSILON)) {
                throw ValidationException::withMessages([
                    'notification' => [
                        "Suppression impossible : les ordonnancements de la ligne {$group['imputation']} dépasseraient les crédits restants.",
                    ],
                ]);
            }
        }

        $exNetCap = min($snapshotAfter['total_net'], $snapshotAfter['total_credits']);
        if ($snapshotAfter['total_engaged'] > ($exNetCap + self::EPSILON)) {
            throw ValidationException::withMessages([
                'notification' => [
                    "Suppression impossible : le total notifié restant pour l'exercice {$exercice} serait inférieur aux engagements existants.",
                ],
            ]);
        }

        // Safely relink foreign keys from deleted lines onto surviving lines in the exact same group
        foreach ($notification->lignes as $deletedLigne) {
            $dom = self::normalizeDomaine($deletedLigne->domaine ?: $notification->domaine);
            $gKey = self::imputationKey($dom, $deletedLigne->article, $deletedLigne->paragraphe, $deletedLigne->ligne_budgetaire);
            $survivingId = $snapshotAfter['groups'][$gKey]['ligne_ids'][0] ?? null;

            if ($survivingId !== null) {
                Consultation::where('notification_ligne_id', $deletedLigne->id)->update(['notification_ligne_id' => $survivingId]);
                Marche::where('notification_ligne_id', $deletedLigne->id)->update(['notification_ligne_id' => $survivingId]);
                Aoo::where('notification_ligne_id', $deletedLigne->id)->update(['notification_ligne_id' => $survivingId]);
                Lot::where('notification_ligne_id', $deletedLigne->id)->update(['notification_ligne_id' => $survivingId]);
                Ordonnancement::where('notification_ligne_id', $deletedLigne->id)->update(['notification_ligne_id' => $survivingId]);
            }
        }
    }
}
