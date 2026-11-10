<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Liquidation;
use App\Models\LiquidationFinanciere;
use App\Models\Marche;
use App\Models\NotificationLigne;
use App\Models\Ordonnancement;
use App\Models\OrdonnancementHistorique;
use App\Models\OrdonnancementOrdre;
use App\Services\BudgetHierarchyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrdonnancementController extends Controller
{
    /**
     * Display a listing of the ordonnancements.
     */
    public function index(Request $request)
    {
        $query = Ordonnancement::with(['ordres', 'fournisseur', 'liquidation', 'marche', 'consultation', 'notificationLigne'])
            ->orderBy('id', 'desc');

        if ($request->filled('exercice')) {
            $query->where('exercice', $request->exercice);
        }

        if ($request->filled('statut') && $request->statut !== 'TOUS') {
            $query->where(function ($statusQuery) use ($request) {
                $statusQuery->where('statut', $request->statut)
                    ->orWhereHas('ordres', function ($ordreQuery) use ($request) {
                        $ordreQuery->where('statut', $request->statut);
                    });
            });
        }

        if ($request->filled('type_procedure') && $request->type_procedure !== 'TOUS') {
            $query->where('type_procedure', $request->type_procedure);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('num_ordonnancement', 'like', "%{$s}%")
                  ->orWhere('num_op', 'like', "%{$s}%")
                  ->orWhere('reference', 'like', "%{$s}%")
                  ->orWhere('beneficiaire_nom', 'like', "%{$s}%")
                  ->orWhere('intitule_depense', 'like', "%{$s}%")
                  ->orWhere('code_imputation', 'like', "%{$s}%");
            });
        }

        $ordonnancements = $query->get();

        // Summary stats
        $totalMontantBrut = $ordonnancements->sum('montant_brut');
        $totalNetAPayer = $ordonnancements->sum('net_a_payer');
        $totalRas = $ordonnancements->sum(function ($o) {
            return $o->retenue_tva + $o->retenue_ias + $o->autres_retenues;
        });

        return response()->json([
            'data' => $ordonnancements,
            'meta' => [
                'total' => $ordonnancements->count(),
                'total_montant_brut' => round($totalMontantBrut, 2),
                'total_net_a_payer' => round($totalNetAPayer, 2),
                'total_ras' => round($totalRas, 2),
            ]
        ]);
    }

    /**
     * Get liquidations available for ordonnancement.
     * Only returns liquidations with a remaining balance > 0.
     */
    public function liquidationsDisponibles(Request $request)
    {
        // Liquidations from Marches
        $liquidationsMarches = Liquidation::with([
            'marche.fournisseur',
            'marche.notificationLigne',
            'marche.registreEngagement',
            'marche.aoo',
            'marche.lot',
            'lignes',
            'ordonnancements.ordres'
        ])
        ->orderBy('id', 'desc')
        ->get();

        $disponibles = [];

        foreach ($liquidationsMarches as $liq) {
            if (!BudgetHierarchyService::isMarcheLiquidationValidated($liq->statut)) {
                continue;
            }

            $marche = $liq->marche;
            $fournisseur = $marche?->fournisseur ?? null;
            $notifLigne = $marche?->notificationLigne ?? null;

            $montantLiquidation = (float) ($liq->montant_brut_ttc ?: $liq->montant_ttc ?: $liq->net_a_payer ?: 0);
            if ($marche) {
                $engaged = BudgetHierarchyService::resolveMarcheEngagedAmount($marche);
                if ($engaged <= BudgetHierarchyService::EPSILON) {
                    continue;
                }
                $montantLiquidation = min($montantLiquidation, $engaged);
            }
            $montantHt = (float) ($liq->montant_brut_ht ?: $liq->montant_ht ?: 0);
            $montantTva = (float) ($liq->montant_tva ?: ($montantLiquidation - $montantHt));

            // Calculate amount already ordonnanced across all existing active ordonnancements for this liquidation
            $dejaOrdonnance = BudgetHierarchyService::getActiveOrdonnancedForLiquidation($liq->id);
            if ($marche) {
                $marcheAlreadyOrd = BudgetHierarchyService::getActiveOrdonnancedForMarche($marche->id);
                $engagedRemaining = max(0.0, BudgetHierarchyService::resolveMarcheEngagedAmount($marche) - $marcheAlreadyOrd);
                $resteLiquidation = max(0.0, $montantLiquidation - $dejaOrdonnance);
                $resteDisponible = round(min($resteLiquidation, $engagedRemaining), 2);
            } else {
                $resteDisponible = round(max(0, $montantLiquidation - $dejaOrdonnance), 2);
            }

            // If totally ordonnanced (reste <= 0.01), DO NOT include in available liquidations
            if ($resteDisponible <= 0.01) {
                continue;
            }

            // Default suggested retenues if user decides to apply them
            $suggestedTva = round($montantTva > 0 ? $montantTva : ($resteDisponible * 0.20 / 1.20), 2);
            $suggestedIas = round(($liq->autres_deductions ?? $liq->retenues ?? 0) > 0 ? ($liq->autres_deductions ?? $liq->retenues) : ($montantHt * 0.05), 2);

            // Extract budget imputation
            $art = $marche?->article_budget ?? $notifLigne?->article ?? '415';
            $par = $marche?->paragraphe_budget ?? $notifLigne?->paragraphe ?? '20';
            $lig = $marche?->ligne_budget ?? $notifLigne?->ligne_budgetaire ?? '13';
            $sLig = '0';
            $code = $marche?->code_budget ?? '225320';

            // Procedure detection
            $typeProcedure = 'Marché';
            $ref = $marche?->num_marche ?? ($liq->num_decompte ?: ($liq->num_facture ?: 'Marché'));
            if (stripos((string)$ref, 'BC') !== false || stripos((string)$ref, 'Bon') !== false) {
                $typeProcedure = 'Bon de commande';
            } elseif (stripos((string)$ref, 'CONV') !== false) {
                $typeProcedure = 'Convention';
            }

            $numLiq = $liq->num_liquidation ?: ($liq->num_decompte ?: ($liq->num_facture ?: "LIQ-".($liq->exercice_budgetaire ?: date('Y'))."-".str_pad((string)$liq->id, 3, '0', STR_PAD_LEFT)));
            $dateLiq = $liq->date_decompte ?: $liq->date_service_fait ?: ($liq->date_facture ?: ($liq->created_at ? $liq->created_at->format('Y-m-d') : date('Y-m-d')));

            $disponibles[] = [
                'type_source' => 'marche_liquidation',
                'liquidation_id' => $liq->id,
                'marche_id' => $marche?->id ?? null,
                'consultation_id' => null,
                'fournisseur_id' => $fournisseur?->id ?? null,
                'notification_ligne_id' => $notifLigne?->id ?? null,
                'num_liquidation' => $numLiq,
                'date_liquidation' => $dateLiq,
                'reference' => $ref,
                'type_procedure' => $typeProcedure,
                'beneficiaire' => $fournisseur?->raison_sociale ?? $marche?->titulaire ?? ($liq->agent_responsable ?: 'Fournisseur non spécifié'),
                'rib' => $fournisseur?->rib ?? $fournisseur?->compte_bancaire ?? '',
                'banque' => $fournisseur?->banque ?? '',
                'budget_type' => $marche?->type_budget ?? $notifLigne?->type_budget ?? 'Investissement',
                'creance' => 'Reste à payer',
                'code_imputation' => $code,
                'article' => $art,
                'paragraphe' => $par,
                'ligne' => $lig,
                'sous_ligne' => $sLig,
                'intitule_depense' => $liq->objet_liquidation ?: ($marche?->objet_marche ?: ($notifLigne?->libelle ?? ($liq->reference_service_fait ?: 'Dépense d\'investissement'))),
                'montant_brut' => $montantLiquidation,
                'montant_ht' => $montantHt,
                'deja_ordonnance' => $dejaOrdonnance,
                'reste_disponible' => $resteDisponible,
                'suggested_tva' => $suggestedTva,
                'suggested_ias' => $suggestedIas,
                'credit_consolide' => (float) ($marche?->depenses_credits_consolides ?? $notifLigne?->reports ?? 0),
                'credit_neuf' => (float) ($marche?->montant_engager_neuf ?? $notifLigne?->credits_neufs ?? 0),
                'statut_liquidation' => $liq->statut,
            ];
        }

        // Bons de commande / Consultations disponibles pour ordonnancement
        $consultationsBc = Consultation::with([
            'fournisseur',
            'budget',
            'notificationLigne',
            'liquidation',
            'registreEngagement',
            'engagement',
            'prestations',
            'receptionCommission',
            'receptions',
            'ordonnancements.ordres',
        ])
            ->orderByDesc('id')
            ->get();

        foreach ($consultationsBc as $consultation) {
            $montantEngagement = BudgetHierarchyService::resolveConsultationEngagedAmount($consultation);
            if ($montantEngagement <= BudgetHierarchyService::EPSILON) {
                continue;
            }

            $liq = $consultation->liquidation;
            $montantLiquidation = 0.0;

            if ($liq && (float) ($liq->montant_a_payer ?? 0) > 0) {
                $montantLiquidation = round(min((float) $liq->montant_a_payer, $montantEngagement), 2);
            } else {
                $isEligible = $consultation->receptionCommission
                    || $consultation->receptions->count() > 0
                    || in_array(strtoupper((string) $consultation->statut_dossier), ['LIQUIDÉ', 'LIQUIDE', 'PAYÉ', 'PAYE', 'RÉCEPTIONNÉ', 'RECEPTIONNE', 'CLÔTURÉ', 'CLOTURE', 'LIQUIDATION', 'ORDONNANCEMENT'])
                    || $montantEngagement > 0;

                if ($isEligible) {
                    $montantLiquidation = $montantEngagement;
                }
            }

            if ($montantLiquidation <= BudgetHierarchyService::EPSILON) {
                continue;
            }

            $dejaOrdonnance = BudgetHierarchyService::getActiveOrdonnancedForConsultation($consultation->id);
            $resteDisponible = round(max(0, $montantLiquidation - $dejaOrdonnance), 2);
            if ($resteDisponible <= 0.01) {
                continue;
            }

            $budget = $consultation->budget;
            $notificationLigne = $consultation->notificationLigne;
            $registre = $consultation->registreEngagement;
            $tauxTva = (float) ($budget?->tva ?? 20);
            $montantHt = $tauxTva > 0
                ? round($resteDisponible / (1 + ($tauxTva / 100)), 2)
                : $resteDisponible;

            $numLiq = $liq?->reference_facture
                ? "LIQ-BC-{$liq->reference_facture}"
                : ('LIQ-BC-' . ($consultation->annee ?: date('Y')) . '-' . str_pad((string) $consultation->id, 3, '0', STR_PAD_LEFT));

            $dateLiq = $liq?->date_facture
                ?: ($consultation->receptionCommission?->date_reception_definitive
                ?: ($consultation->receptionCommission?->date_decision
                ?: ($consultation->date_consultation ?: $consultation->created_at?->format('Y-m-d'))));

            $refBc = $consultation->numero_bc ?: ($consultation->numero_consultation ?: "BC #{$consultation->id}");
            $typeProcBc = (stripos((string)$refBc, 'CONV') !== false || stripos((string)($consultation->type_consultation ?? ''), 'conv') !== false)
                ? 'Convention'
                : 'Bon de commande';

            $disponibles[] = [
                'type_source' => 'consultation_liquidation',
                'liquidation_id' => null,
                'liquidation_financiere_id' => $liq?->id,
                'marche_id' => null,
                'consultation_id' => $consultation->id,
                'fournisseur_id' => $consultation->fournisseur_id,
                'notification_ligne_id' => $consultation->notification_ligne_id,
                'num_liquidation' => $numLiq,
                'date_liquidation' => $dateLiq,
                'reference' => $refBc,
                'type_procedure' => $typeProcBc,
                'beneficiaire' => $consultation->fournisseur?->raison_sociale ?? 'Fournisseur non spécifié',
                'rib' => $consultation->fournisseur?->rib ?? ($consultation->fournisseur?->compte_bancaire ?? ''),
                'banque' => $consultation->fournisseur?->banque ?? '',
                'budget_type' => $consultation->type_budget ?: ($notificationLigne?->type_budget ?? 'Investissement'),
                'creance' => 'Reste à payer',
                'code_imputation' => $budget?->code_imputation ?: ($registre?->code ?: ($consultation->code_imputation ?? '225320')),
                'article' => $budget?->art ?: ($registre?->art ?: ($consultation->article ?: '415')),
                'paragraphe' => $budget?->par ?: ($registre?->par ?: ($consultation->paragraphe ?: '20')),
                'ligne' => $budget?->lig ?: ($registre?->lig ?: ($consultation->ligne ?: '13')),
                'sous_ligne' => $consultation->s_lig ?: ($registre?->s_lig ?: '0'),
                'intitule_depense' => $consultation->intitule ?: ($consultation->objet_consultation ?: 'Dépense Bon de commande'),
                'montant_brut' => $resteDisponible,
                'montant_ht' => $montantHt,
                'deja_ordonnance' => $dejaOrdonnance,
                'reste_disponible' => $resteDisponible,
                'suggested_tva' => round($resteDisponible - $montantHt, 2),
                'suggested_ias' => 0,
                'credit_consolide' => (float) ($consultation->depenses_credits_consolides ?? $registre?->credit_consolide ?? 0),
                'credit_neuf' => (float) ($consultation->montant_engager_neuf ?? $registre?->montant_engager_neuf ?? 0),
                'statut_liquidation' => 'VALIDÉE',
            ];
        }

        // Filter to only include valid liquidations with reste_disponible > 0
        $disponibles = array_values(array_filter($disponibles, function($item) {
            return ($item['reste_disponible'] ?? 0) > 0;
        }));

        if ($request->filled('type_procedure') && $request->type_procedure !== 'TOUS') {
            $filterProc = strtolower($request->type_procedure);
            $disponibles = array_values(array_filter($disponibles, function($item) use ($filterProc) {
                $p = strtolower($item['type_procedure'] ?? '');
                if (str_contains($filterProc, 'march') || str_contains($filterProc, 'aoo')) {
                    return str_contains($p, 'march');
                }
                if (str_contains($filterProc, 'bon') || str_contains($filterProc, 'bc')) {
                    return str_contains($p, 'bon');
                }
                if (str_contains($filterProc, 'conv')) {
                    return str_contains($p, 'conv');
                }
                return true;
            }));
        }

        return response()->json(['data' => $disponibles]);
    }


    /**
     * Store a newly created ordonnancement with multiple payment orders.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'exercice' => 'nullable|string',
            'date_ordonnancement' => 'required|date',
            'liquidation_id' => 'nullable|exists:liquidations,id',
            'marche_id' => 'nullable|exists:marches,id',
            'consultation_id' => 'nullable|exists:consultations,id',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
            'notification_ligne_id' => 'nullable|exists:notification_lignes,id',
            'type_procedure' => 'required|string',
            'reference' => 'required|string',
            'beneficiaire_nom' => 'required|string',
            'budget_type' => 'required|string',
            'creance' => 'nullable|string',
            'code_imputation' => 'nullable|string',
            'article' => 'nullable|string',
            'paragraphe' => 'nullable|string',
            'ligne' => 'nullable|string',
            'sous_ligne' => 'nullable|string',
            'intitule_depense' => 'required|string',
            'montant_brut' => 'required|numeric|min:0',
            'retenue_tva' => 'nullable|numeric|min:0',
            'retenue_ias' => 'nullable|numeric|min:0',
            'autres_retenues' => 'nullable|numeric|min:0',
            'net_a_payer' => 'required|numeric|min:0',
            'credit_consolide' => 'nullable|numeric',
            'credit_neuf' => 'nullable|numeric',
            'statut' => 'nullable|string',
            'observations' => 'nullable|string',
            'ordres' => 'required|array|min:1',
            'ordres.*.num_ordre' => 'nullable|string',
            'ordres.*.type_mouvement' => 'required|string',
            'ordres.*.mode_paiement' => 'nullable|string',
            'ordres.*.beneficiaire' => 'required|string',
            'ordres.*.rib_compte' => 'nullable|string',
            'ordres.*.banque_agence' => 'nullable|string',
            'ordres.*.creance' => 'nullable|string',
            'ordres.*.montant' => 'required|numeric|min:0.01',
            'ordres.*.statut' => 'nullable|string',
            'ordres.*.observations' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $year = $validated['exercice'] ?? Carbon::parse($validated['date_ordonnancement'])->format('Y');

            $liq = null;
            $marche = null;
            $consultation = null;
            $montantLiquidation = (float) $validated['montant_brut'];
            $dejaOrdonnance = 0.0;
            $dossierEngaged = null;
            $dossierAlreadyOrd = 0.0;
            $dossierLabel = $validated['reference'] ?? 'ce dossier';

            $dossierCancelled = BudgetHierarchyService::isOrdonnancementCancelledOrRejected($validated['statut'] ?? null);
            $totalOrdres = round((float) collect($validated['ordres'])
                ->filter(fn ($o) => BudgetHierarchyService::isOrdreActive($o['statut'] ?? 'À payer'))
                ->sum(fn ($o) => (float) ($o['montant'] ?? 0)), 2);
            $effectiveOrdProposed = $dossierCancelled ? 0.0 : $totalOrdres;

            if (!empty($validated['liquidation_id'])) {
                $liq = Liquidation::with(['marche.registreEngagement', 'marche.notificationLigne.notification', 'ordonnancements.ordres'])
                    ->lockForUpdate()
                    ->find($validated['liquidation_id']);
                if ($liq) {
                    if (!BudgetHierarchyService::isMarcheLiquidationValidated($liq->statut)) {
                        throw ValidationException::withMessages([
                            'liquidation_id' => ["La liquidation sélectionnée doit être validée avant d'émettre un ordonnancement."],
                        ]);
                    }

                    $marche = $liq->marche;
                    if ($marche && empty($validated['marche_id'])) {
                        $validated['marche_id'] = $marche->id;
                    }

                    $montantLiquidation = (float) ($liq->montant_brut_ttc ?: $liq->montant_ttc ?: $validated['montant_brut']);
                    $dejaOrdonnance = BudgetHierarchyService::getActiveOrdonnancedForLiquidation($liq->id);
                    $resteDisponible = round(max(0, $montantLiquidation - $dejaOrdonnance), 2);

                    if ($effectiveOrdProposed > ($resteDisponible + 0.05)) {
                        throw ValidationException::withMessages([
                            'ordres' => ["Le montant total des ordres (" . number_format($effectiveOrdProposed, 2, ',', ' ') . " DH) dépasse le montant disponible de la liquidation (" . number_format($resteDisponible, 2, ',', ' ') . " DH)."]
                        ]);
                    }

                    if ($marche) {
                        $dossierEngaged = BudgetHierarchyService::resolveMarcheEngagedAmount($marche);
                        $dossierAlreadyOrd = BudgetHierarchyService::getActiveOrdonnancedForMarche($marche->id);
                        $dossierLabel = "le marché {$marche->num_marche}";
                    }
                }
            } elseif (!empty($validated['consultation_id'])) {
                $consultation = Consultation::with(['budget', 'notificationLigne.notification', 'engagement', 'registreEngagement', 'liquidation'])
                    ->lockForUpdate()
                    ->find($validated['consultation_id']);
                if ($consultation) {
                    $engaged = BudgetHierarchyService::resolveConsultationEngagedAmount($consultation);
                    if ($engaged <= BudgetHierarchyService::EPSILON && $effectiveOrdProposed > 0) {
                        throw ValidationException::withMessages([
                            'ordres' => [
                                "Règle budgétaire (Ordonnancement <= Engagement) : impossible d'ordonnancer la consultation {$consultation->numero_consultation} sans engagement actif (> 0 DH).",
                            ],
                        ]);
                    }

                    $liqBc = $consultation->liquidation;
                    if (!$liqBc) {
                        $liqBc = LiquidationFinanciere::create([
                            'consultation_id' => $consultation->id,
                            'montant_a_payer' => $engaged,
                            'reference_facture' => $consultation->numero_bc ?? $consultation->numero_consultation,
                            'date_facture' => $consultation->date_consultation ?? now()->format('Y-m-d'),
                        ]);
                    }

                    $montantLiquidation = round(min((float) $liqBc->montant_a_payer, $engaged), 2);
                    $dejaOrdonnance = BudgetHierarchyService::getActiveOrdonnancedForConsultation($consultation->id);
                    $resteDisponible = round(max(0, $montantLiquidation - $dejaOrdonnance), 2);

                    if ($effectiveOrdProposed > ($resteDisponible + 0.05)) {
                        throw ValidationException::withMessages([
                            'ordres' => ["Le montant total des ordres dépasse le montant disponible de la liquidation du bon de commande (" . number_format($resteDisponible, 2, ',', ' ') . " DH)."]
                        ]);
                    }

                    $dossierEngaged = $engaged;
                    $dossierAlreadyOrd = $dejaOrdonnance;
                    $dossierLabel = "la consultation {$consultation->numero_consultation}";
                }
            } elseif (!empty($validated['marche_id'])) {
                $marche = Marche::with(['registreEngagement', 'notificationLigne.notification'])
                    ->lockForUpdate()
                    ->find($validated['marche_id']);
                if ($marche) {
                    $dossierEngaged = BudgetHierarchyService::resolveMarcheEngagedAmount($marche);
                    $dossierAlreadyOrd = BudgetHierarchyService::getActiveOrdonnancedForMarche($marche->id);
                    $dossierLabel = "le marché {$marche->num_marche}";
                }
            }

            $resolvedLigne = BudgetHierarchyService::resolveNotificationLigneForDossier(
                $validated['notification_ligne_id'] ?? $marche?->notification_ligne_id ?? $consultation?->notification_ligne_id,
                $validated['article'] ?? $marche?->article_budget ?? $consultation?->budget?->art,
                $validated['paragraphe'] ?? $marche?->paragraphe_budget ?? $consultation?->budget?->par,
                $validated['ligne'] ?? $marche?->ligne_budget ?? $consultation?->budget?->lig,
                $validated['budget_type'] ?? $marche?->type_budget ?? $consultation?->type_budget ?? 'Investissement',
                $year
            );

            if ($resolvedLigne) {
                $validated['notification_ligne_id'] = $resolvedLigne->id;
                $validated['article'] = $validated['article'] ?: $resolvedLigne->article;
                $validated['paragraphe'] = $validated['paragraphe'] ?: $resolvedLigne->paragraphe;
                $validated['ligne'] = $validated['ligne'] ?: $resolvedLigne->ligne_budgetaire;
            }

            if ($effectiveOrdProposed > 0 || ($dossierEngaged !== null && $effectiveOrdProposed > 0)) {
                BudgetHierarchyService::assertOrdonnancementWithinEngagement([
                    'exercice' => $year,
                    'domaine' => $validated['budget_type'] ?? 'Investissement',
                    'art' => $validated['article'] ?? null,
                    'par' => $validated['paragraphe'] ?? null,
                    'lig' => $validated['ligne'] ?? null,
                    'notification_ligne_id' => $validated['notification_ligne_id'] ?? null,
                    'dossier_engaged' => $dossierEngaged,
                    'dossier_already_ordonnanced' => $dossierAlreadyOrd,
                    'dossier_label' => $dossierLabel,
                    'proposed_ordonnance' => $effectiveOrdProposed,
                    'error_field' => 'ordres',
                ]);
            }

            // 2. Generate unique ORD number & OP number
            $maxOrdSeq = 0;
            $existingOrds = Ordonnancement::pluck('num_ordonnancement')->filter();
            foreach ($existingOrds as $existing) {
                if (preg_match('/ORD-(?:' . $year . '-)?(\d+)/', $existing, $matches)) {
                    $val = (int) $matches[1];
                    if ($val > $maxOrdSeq) {
                        $maxOrdSeq = $val;
                    }
                }
            }

            $nextOrdSeq = max($maxOrdSeq + 1, Ordonnancement::count() + 1);
            do {
                $candidateOrd = "ORD-{$year}-" . str_pad((string) $nextOrdSeq, 3, '0', STR_PAD_LEFT);
                $exists = Ordonnancement::where('num_ordonnancement', $candidateOrd)->exists();
                if ($exists) {
                    $nextOrdSeq++;
                }
            } while ($exists);

            $numOrdonnancement = $candidateOrd;

            // 3. Compute Ordonnancement status
            $statutOrdonnance = 'Totalement ordonnancée';
            if ($effectiveOrdProposed <= 0) {
                $statutOrdonnance = 'Non ordonnancée';
            } elseif ($effectiveOrdProposed < ($montantLiquidation - 0.05)) {
                $statutOrdonnance = 'Partiellement ordonnancée';
            }

            $validated['num_ordonnancement'] = $numOrdonnancement;
            $validated['num_op'] = "OP-" . str_pad((string) $nextOrdSeq, 3, '0', STR_PAD_LEFT);
            $validated['exercice'] = $year;
            $validated['statut'] = $validated['statut'] ?? $statutOrdonnance;
            $validated['montant_brut'] = $effectiveOrdProposed; // The total of active orders in this dossier
            $validated['ras_total'] = ($validated['retenue_tva'] ?? 0) + ($validated['retenue_ias'] ?? 0) + ($validated['autres_retenues'] ?? 0);
            $validated['rap_total'] = $validated['net_a_payer'];

            $ordres = $validated['ordres'];
            unset($validated['ordres']);

            $ordonnancement = Ordonnancement::create($validated);

            // 4. Create ordres / mouvements with clean OP-001, OP-002 numbering
            foreach ($ordres as $idx => $ordreData) {
                $orderSeq = str_pad($idx + 1, 3, '0', STR_PAD_LEFT);
                $ordreData['num_ordre'] = !empty($ordreData['num_ordre']) ? $ordreData['num_ordre'] : "OP-{$orderSeq}";
                $ordreData['mode_paiement'] = $ordreData['mode_paiement'] ?? 'Virement';
                $ordreData['statut'] = $ordreData['statut'] ?? 'À payer';
                $ordonnancement->ordres()->create($ordreData);
            }

            $ordonnancement->load(['ordres', 'consultation', 'marche', 'notificationLigne']);
            $amounts = BudgetHierarchyService::computeOrdonnancementAmounts($ordonnancement);
            BudgetHierarchyService::assertPaiementWithinOrdonnancement([
                'ordonnancement' => $ordonnancement,
                'proposed_paid' => $amounts['total_paiements'],
                'proposed_active_ordonnanced' => $amounts['total_ordonnance'],
                'dossier_statut' => $ordonnancement->statut,
                'error_field' => 'ordres',
            ]);

            // 5. Update liquidation status if linked
            if ($liq) {
                $newDejaOrdonnance = $dejaOrdonnance + $effectiveOrdProposed;
                if ($newDejaOrdonnance >= ($montantLiquidation - 0.05)) {
                    $liq->statut = 'TOTALEMENT ORDONNANCÉE';
                } else {
                    $liq->statut = 'PARTIELLEMENT ORDONNANCÉE';
                }
                $liq->save();
            }

            // 6. Update Marche or Consultation status if linked
            if ($marche) {
                $marche->statut = 'ordonnancement_validee';
                $marche->save();
            } elseif ($consultation) {
                $consultation->update(['statut_dossier' => 'ORDONNANCÉ']);
            }

            // 7. Create Historique records
            $currentUser = auth()->user()->name ?? 'admin';
            OrdonnancementHistorique::create([
                'ordonnancement_id' => $ordonnancement->id,
                'action' => 'Dossier créé',
                'auteur' => $currentUser,
                'statut_precedent' => null,
                'statut_nouveau' => $ordonnancement->statut,
                'details' => "Création de l'ordonnancement {$ordonnancement->num_ordonnancement} pour un montant de " . number_format($effectiveOrdProposed, 2, ',', ' ') . " DH (" . count($ordres) . " ordre(s)).",
                'created_at' => now(),
            ]);

            NotificationLigne::clearBudgetCache();

            return response()->json([
                'message' => 'Ordonnancement créé avec succès',
                'data' => $ordonnancement->load(['ordres', 'historiques', 'fournisseur', 'liquidation'])
            ], 201);
        });
    }

    /**
     * Show single ordonnancement dossier.
     */
    public function show($id)
    {
        $ordonnancement = Ordonnancement::with([
            'ordres',
            'historiques',
            'fournisseur',
            'notificationLigne',
            'liquidation.pieces',
            'liquidation.lignes',
            'marche.aoo',
            'marche.lot',
            'marche.bordereauItems',
            'consultation.liquidation'
        ])->findOrFail($id);

        $this->attachBonCommandeLiquidation($ordonnancement);

        return response()->json([
            'data' => $ordonnancement
        ]);
    }

    /**
     * Update ordonnancement.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'reference' => 'nullable|string',
            'beneficiaire_nom' => 'nullable|string',
            'budget_type' => 'nullable|string',
            'code_imputation' => 'nullable|string',
            'article' => 'nullable|string',
            'paragraphe' => 'nullable|string',
            'ligne' => 'nullable|string',
            'sous_ligne' => 'nullable|string',
            'intitule_depense' => 'nullable|string',
            'montant_brut' => 'nullable|numeric|min:0',
            'retenue_tva' => 'nullable|numeric|min:0',
            'retenue_ias' => 'nullable|numeric|min:0',
            'autres_retenues' => 'nullable|numeric|min:0',
            'net_a_payer' => 'nullable|numeric|min:0',
            'observations' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($id, $validated) {
            $ordonnancement = Ordonnancement::with(['ordres', 'consultation', 'marche', 'liquidation', 'notificationLigne'])
                ->lockForUpdate()
                ->findOrFail($id);

            $hasFinancialEdit = array_key_exists('montant_brut', $validated) || array_key_exists('net_a_payer', $validated);
            if ($hasFinancialEdit && BudgetHierarchyService::isOrdonnancementCancelledOrRejected($ordonnancement->statut)) {
                throw ValidationException::withMessages([
                    'montant_brut' => ["Impossible de modifier les montants d'un ordonnancement rejeté ou annulé."],
                ]);
            }

            if ($ordonnancement->ordres->isNotEmpty() && array_key_exists('montant_brut', $validated)) {
                $activeOrdresSum = round((float) $ordonnancement->ordres
                    ->filter(fn ($o) => BudgetHierarchyService::isOrdreActive($o->statut))
                    ->sum('montant'), 2);
                if (abs((float) $validated['montant_brut'] - $activeOrdresSum) > 0.05) {
                    throw ValidationException::withMessages([
                        'montant_brut' => ["Le montant brut doit correspondre au total des ordres actifs (" . BudgetHierarchyService::fmt($activeOrdresSum) . " DH). Modifiez les ordres directement."],
                    ]);
                }
            }

            $ordonnancement->update($validated);
            $ordonnancement->load(['ordres', 'consultation', 'marche', 'liquidation', 'notificationLigne']);

            $amounts = BudgetHierarchyService::computeOrdonnancementAmounts($ordonnancement);
            $this->validateDossierOrdonnancementCapacity($ordonnancement, $amounts['total_ordonnance'], 'montant_brut');
            BudgetHierarchyService::assertPaiementWithinOrdonnancement([
                'ordonnancement' => $ordonnancement,
                'proposed_paid' => $amounts['total_paiements'],
                'proposed_active_ordonnanced' => $amounts['total_ordonnance'],
                'dossier_statut' => $ordonnancement->statut,
                'error_field' => 'montant_brut',
            ]);

            NotificationLigne::clearBudgetCache();

            return response()->json([
                'message' => 'Ordonnancement mis à jour avec succès',
                'data' => $ordonnancement->fresh(['ordres', 'historiques', 'fournisseur'])
            ]);
        });
    }

    /**
     * Add a payment movement/order to ordonnancement.
     */
    public function addOrdre(Request $request, $id)
    {
        $validated = $request->validate([
            'num_ordre' => 'nullable|string',
            'type_mouvement' => 'required|string',
            'mode_paiement' => 'required|string',
            'beneficiaire' => 'required|string',
            'rib_compte' => 'nullable|string',
            'banque_agence' => 'nullable|string',
            'creance' => 'required|string',
            'montant' => 'required|numeric|min:0.01',
            'statut' => 'nullable|string|in:Brouillon,À payer,À vérifier,Transmis au trésorier,Payé,Rejeté,Annulé',
            'observations' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($id, $validated) {
            $ordonnancement = Ordonnancement::with(['ordres', 'liquidation.ordonnancements.ordres', 'consultation', 'marche', 'notificationLigne'])
                ->lockForUpdate()
                ->findOrFail($id);

            if (BudgetHierarchyService::isOrdonnancementCancelledOrRejected($ordonnancement->statut)) {
                throw ValidationException::withMessages([
                    'montant' => ["Impossible d'ajouter un ordre à un ordonnancement rejeté ou annulé."],
                ]);
            }

            $montantNouveau = (float) $validated['montant'];
            $newOrdreStatut = $validated['statut'] ?? 'À payer';

            $existingActiveTotal = (float) $ordonnancement->ordres
                ->filter(fn ($o) => BudgetHierarchyService::isOrdreActive($o->statut))
                ->sum('montant');
            $proposedThisDossierTotal = round(
                $existingActiveTotal + (BudgetHierarchyService::isOrdreActive($newOrdreStatut) ? $montantNouveau : 0.0),
                2
            );

            $this->validateDossierOrdonnancementCapacity($ordonnancement, $proposedThisDossierTotal, 'montant');

            // Auto generate sequential order number OP-001, OP-002...
            $orderCount = $ordonnancement->ordres()->count() + 1;
            $orderSeq = str_pad($orderCount, 3, '0', STR_PAD_LEFT);
            $validated['num_ordre'] = !empty($validated['num_ordre']) ? $validated['num_ordre'] : "OP-{$orderSeq}";
            $validated['statut'] = $newOrdreStatut;

            $ordre = $ordonnancement->ordres()->create($validated);

            // Recalculate ordonnancement total & status
            $totalOrdres = (float) $ordonnancement->ordres()->actifs()->sum('montant');
            $netFournisseur = (float) $ordonnancement->ordres()->actifs()->where('type_mouvement', 'Paiement fournisseur')->sum('montant');
            $ordonnancement->montant_brut = $totalOrdres;
            $ordonnancement->net_a_payer = $netFournisseur > 0 ? $netFournisseur : max(0.0, $totalOrdres - (float) ($ordonnancement->ras_total ?? 0));

            if ($ordonnancement->liquidation) {
                $montantLiq = (float) ($ordonnancement->liquidation->montant_brut_ttc ?: $ordonnancement->liquidation->montant_ttc ?: $totalOrdres);
                $ordonnancement->statut = ($totalOrdres >= ($montantLiq - 0.05)) ? 'Totalement ordonnancée' : 'Partiellement ordonnancée';
            } elseif ($montantLiqBc = $this->bonCommandeLiquidationAmount($ordonnancement)) {
                $ordonnancement->statut = ($totalOrdres >= ($montantLiqBc - 0.05)) ? 'Totalement ordonnancée' : 'Partiellement ordonnancée';
            }
            $ordonnancement->save();

            $ordonnancement->load(['ordres', 'consultation', 'marche', 'notificationLigne']);
            $amounts = BudgetHierarchyService::computeOrdonnancementAmounts($ordonnancement);
            BudgetHierarchyService::assertPaiementWithinOrdonnancement([
                'ordonnancement' => $ordonnancement,
                'proposed_paid' => $amounts['total_paiements'],
                'proposed_active_ordonnanced' => $amounts['total_ordonnance'],
                'dossier_statut' => $ordonnancement->statut,
                'error_field' => 'montant',
            ]);

            $currentUser = auth()->user()->name ?? 'admin';
            OrdonnancementHistorique::create([
                'ordonnancement_id' => $ordonnancement->id,
                'action' => "Ordre {$ordre->num_ordre} ajouté",
                'auteur' => $currentUser,
                'details' => "Ajout de l'ordre {$ordre->num_ordre} ({$ordre->type_mouvement}) au profit de {$ordre->beneficiaire} pour " . number_format($ordre->montant, 2, ',', ' ') . " DH.",
                'created_at' => now(),
            ]);

            NotificationLigne::clearBudgetCache();

            return response()->json([
                'message' => 'Ordre de paiement ajouté avec succès',
                'data' => $ordre,
                'ordonnancement' => $ordonnancement->fresh(['ordres', 'historiques'])
            ], 201);
        });
    }

    /**
     * Update an order.
     */
    public function updateOrdre(Request $request, $id, $ordreId)
    {
        $validated = $request->validate([
            'num_ordre' => 'nullable|string',
            'type_mouvement' => 'required|string',
            'mode_paiement' => 'required|string',
            'beneficiaire' => 'required|string',
            'rib_compte' => 'nullable|string',
            'banque_agence' => 'nullable|string',
            'creance' => 'required|string',
            'montant' => 'required|numeric|min:0.01',
            'statut' => 'nullable|string|in:Brouillon,À payer,À vérifier,Transmis au trésorier,Payé,Rejeté,Annulé',
            'observations' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($id, $ordreId, $validated) {
            $ordonnancement = Ordonnancement::with(['ordres', 'liquidation.ordonnancements.ordres', 'consultation', 'marche', 'notificationLigne'])
                ->lockForUpdate()
                ->findOrFail($id);
            $ordre = $ordonnancement->ordres()->lockForUpdate()->findOrFail($ordreId);

            if (BudgetHierarchyService::isOrdonnancementCancelledOrRejected($ordonnancement->statut)) {
                throw ValidationException::withMessages([
                    'montant' => ["Impossible de modifier un ordre d'un ordonnancement rejeté ou annulé."],
                ]);
            }

            $montantNouveau = (float) $validated['montant'];
            $ancienMontant = (float) $ordre->montant;
            if (BudgetHierarchyService::isOrdrePaid($ordre->statut) && abs($montantNouveau - $ancienMontant) > 0.01) {
                throw ValidationException::withMessages([
                    'montant' => ["Règle budgétaire (Paiement <= Ordonnancement) : impossible de modifier le montant d'un ordre déjà payé."],
                ]);
            }

            $newStatut = $validated['statut'] ?? $ordre->statut;
            $otherActiveTotal = (float) $ordonnancement->ordres
                ->where('id', '!=', $ordre->id)
                ->filter(fn ($o) => BudgetHierarchyService::isOrdreActive($o->statut))
                ->sum('montant');
            $proposedThisDossierTotal = round(
                $otherActiveTotal + (BudgetHierarchyService::isOrdreActive($newStatut) ? $montantNouveau : 0.0),
                2
            );

            $this->validateDossierOrdonnancementCapacity($ordonnancement, $proposedThisDossierTotal, 'montant');

            $ordre->update($validated);

            // Recalculate ordonnancement total & status
            $totalOrdres = (float) $ordonnancement->ordres()->actifs()->sum('montant');
            $netFournisseur = (float) $ordonnancement->ordres()->actifs()->where('type_mouvement', 'Paiement fournisseur')->sum('montant');
            $ordonnancement->montant_brut = $totalOrdres;
            $ordonnancement->net_a_payer = $netFournisseur > 0 ? $netFournisseur : max(0.0, $totalOrdres - (float) ($ordonnancement->ras_total ?? 0));

            if ($ordonnancement->liquidation) {
                $montantLiq = (float) ($ordonnancement->liquidation->montant_brut_ttc ?: $ordonnancement->liquidation->montant_ttc ?: $totalOrdres);
                $ordonnancement->statut = ($totalOrdres >= ($montantLiq - 0.05)) ? 'Totalement ordonnancée' : 'Partiellement ordonnancée';
            } elseif ($montantLiqBc = $this->bonCommandeLiquidationAmount($ordonnancement)) {
                $ordonnancement->statut = ($totalOrdres >= ($montantLiqBc - 0.05)) ? 'Totalement ordonnancée' : 'Partiellement ordonnancée';
            }
            $ordonnancement->save();

            $ordonnancement->load(['ordres', 'consultation', 'marche', 'notificationLigne']);
            $amounts = BudgetHierarchyService::computeOrdonnancementAmounts($ordonnancement);
            BudgetHierarchyService::assertPaiementWithinOrdonnancement([
                'ordonnancement' => $ordonnancement,
                'proposed_paid' => $amounts['total_paiements'],
                'proposed_active_ordonnanced' => $amounts['total_ordonnance'],
                'dossier_statut' => $ordonnancement->statut,
                'error_field' => 'montant',
            ]);

            NotificationLigne::clearBudgetCache();

            return response()->json([
                'message' => 'Ordre mis à jour avec succès',
                'data' => $ordre,
                'ordonnancement' => $ordonnancement->fresh(['ordres', 'historiques'])
            ]);
        });
    }

    /**
     * Delete an order.
     */
    public function deleteOrdre($id, $ordreId)
    {
        return DB::transaction(function () use ($id, $ordreId) {
            $ordonnancement = Ordonnancement::with(['ordres', 'liquidation', 'consultation', 'marche', 'notificationLigne'])
                ->lockForUpdate()
                ->findOrFail($id);
            $ordre = $ordonnancement->ordres()->lockForUpdate()->findOrFail($ordreId);

            if (BudgetHierarchyService::isOrdrePaid($ordre->statut)) {
                throw ValidationException::withMessages([
                    'ordre' => ["Règle budgétaire (Paiement <= Ordonnancement) : impossible de supprimer un ordre déjà payé."],
                ]);
            }

            $desc = "{$ordre->num_ordre} ({$ordre->type_mouvement}) - {$ordre->beneficiaire} (" . number_format($ordre->montant, 2, ',', ' ') . " DH)";
            $ordre->delete();

            // Recalculate ordonnancement total & status
            $totalOrdres = (float) $ordonnancement->ordres()->actifs()->sum('montant');
            $netFournisseur = (float) $ordonnancement->ordres()->actifs()->where('type_mouvement', 'Paiement fournisseur')->sum('montant');
            $ordonnancement->montant_brut = $totalOrdres;
            $ordonnancement->net_a_payer = $netFournisseur > 0 ? $netFournisseur : max(0.0, $totalOrdres - (float) ($ordonnancement->ras_total ?? 0));

            if ($ordonnancement->liquidation) {
                $montantLiq = (float) ($ordonnancement->liquidation->montant_brut_ttc ?: $ordonnancement->liquidation->montant_ttc ?: $totalOrdres);
                $ordonnancement->statut = ($totalOrdres >= ($montantLiq - 0.05)) ? 'Totalement ordonnancée' : ($totalOrdres > 0 ? 'Partiellement ordonnancée' : 'Non ordonnancée');
            } elseif ($montantLiqBc = $this->bonCommandeLiquidationAmount($ordonnancement)) {
                $ordonnancement->statut = ($totalOrdres >= ($montantLiqBc - 0.05)) ? 'Totalement ordonnancée' : ($totalOrdres > 0 ? 'Partiellement ordonnancée' : 'Non ordonnancée');
            }
            $ordonnancement->save();

            $ordonnancement->load(['ordres', 'consultation', 'marche', 'notificationLigne']);
            $amounts = BudgetHierarchyService::computeOrdonnancementAmounts($ordonnancement);
            $this->validateDossierOrdonnancementCapacity($ordonnancement, $amounts['total_ordonnance'], 'ordre');
            BudgetHierarchyService::assertPaiementWithinOrdonnancement([
                'ordonnancement' => $ordonnancement,
                'proposed_paid' => $amounts['total_paiements'],
                'proposed_active_ordonnanced' => $amounts['total_ordonnance'],
                'dossier_statut' => $ordonnancement->statut,
                'error_field' => 'ordre',
            ]);

            $currentUser = auth()->user()->name ?? 'admin';
            OrdonnancementHistorique::create([
                'ordonnancement_id' => $ordonnancement->id,
                'action' => 'Ordre supprimé',
                'auteur' => $currentUser,
                'details' => "Suppression de l'ordre : {$desc}",
                'created_at' => now(),
            ]);

            NotificationLigne::clearBudgetCache();

            return response()->json([
                'message' => 'Ordre supprimé avec succès',
                'ordonnancement' => $ordonnancement->fresh(['ordres', 'historiques'])
            ]);
        });
    }

    /**
     * Update workflow status (Brouillon -> À payer -> Transmis au trésorier -> Payé -> Rejeté).
     */
    public function updateStatut(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|string|in:Brouillon,À payer,À vérifier,Ordonnancé,Transmis au trésorier,Payé,Clôturé,Rejeté,Annulé',
            'motif_rejet' => 'nullable|string',
            'ordre_id' => 'nullable|integer|exists:ordonnancement_ordres,id',
            'date_transmission_tresorier' => 'nullable|date',
            'date_paiement' => 'nullable|date',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $ordonnancement = Ordonnancement::with(['ordres', 'consultation', 'marche', 'liquidation', 'notificationLigne'])
                ->lockForUpdate()
                ->findOrFail($id);
            $oldStatut = $ordonnancement->statut;
            $newStatut = $request->statut;
            $currentUser = auth()->user()?->name ?? 'admin';
            $replacement = null;

            $amountsBefore = BudgetHierarchyService::computeOrdonnancementAmounts($ordonnancement);

            if ($newStatut === 'Annulé' && $amountsBefore['total_paiements'] > BudgetHierarchyService::EPSILON) {
                throw ValidationException::withMessages([
                    'statut' => [
                        "Règle budgétaire (Paiement <= Ordonnancement) : impossible d'annuler un ordonnancement qui comporte déjà des paiements (" . BudgetHierarchyService::fmt($amountsBefore['total_paiements']) . " DH).",
                    ],
                ]);
            }

            if (BudgetHierarchyService::isOrdonnancementPaid($newStatut) && BudgetHierarchyService::isOrdonnancementCancelledOrRejected($oldStatut)) {
                throw ValidationException::withMessages([
                    'statut' => [
                        "Règle budgétaire (Paiement <= Ordonnancement) : un ordonnancement rejeté ou annulé ne peut pas être marqué comme payé.",
                    ],
                ]);
            }

            if ($newStatut === 'Rejeté') {
                $ordre = $request->filled('ordre_id')
                    ? $ordonnancement->ordres->firstWhere('id', (int) $request->ordre_id)
                    : $ordonnancement->ordres->whereNotIn('statut', ['Payé', 'Rejeté', 'Annulé'])->last();

                if (!$ordre) {
                    throw ValidationException::withMessages([
                        'ordre_id' => ['Aucun ordre de paiement actif à rejeter pour cet ordonnancement.'],
                    ]);
                }

                $ordre->update([
                    'statut' => 'Rejeté',
                    'observations' => trim(($ordre->observations ? $ordre->observations . "\n" : '') . ($request->motif_rejet ?: 'Ordre rejeté par le fondé de pouvoir.')),
                ]);

                $existingNumbers = $ordonnancement->ordres
                    ->pluck('num_ordre')
                    ->filter()
                    ->map(fn ($number) => (int) preg_replace('/\D+/', '', $number))
                    ->filter();
                $nextNumber = ($existingNumbers->max() ?: 0) + 1;
                $replacement = $ordonnancement->ordres()->create([
                    'num_ordre' => 'OP-' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT),
                    'type_mouvement' => $ordre->type_mouvement,
                    'mode_paiement' => $ordre->mode_paiement,
                    'beneficiaire' => $ordre->beneficiaire,
                    'rib_compte' => $ordre->rib_compte,
                    'banque_agence' => $ordre->banque_agence,
                    'creance' => $ordre->creance,
                    'montant' => $ordre->montant,
                    'statut' => 'À payer',
                    'observations' => "Remplacement de {$ordre->num_ordre} rejeté.",
                ]);

                // The rejected amount is released; the replacement is the only active amount.
                $ordonnancement->statut = 'À payer';
                $ordonnancement->motif_rejet = $request->motif_rejet;
            } else {
                $ordonnancement->statut = $newStatut;
                if ($newStatut === 'Transmis au trésorier') {
                    $ordonnancement->date_transmission_tresorier = $request->date_transmission_tresorier ?: now()->format('Y-m-d');
                } elseif (BudgetHierarchyService::isOrdonnancementPaid($newStatut)) {
                    $ordonnancement->date_paiement = $request->date_paiement ?: now()->format('Y-m-d');
                    $ordonnancement->ordres()->whereNotIn('statut', ['Rejeté', 'Annulé'])->update(['statut' => 'Payé']);
                }
            }
            $ordonnancement->save();
            $ordonnancement->load('ordres');

            $amountsAfter = BudgetHierarchyService::computeOrdonnancementAmounts($ordonnancement);
            if ($amountsAfter['total_ordonnance'] > BudgetHierarchyService::EPSILON) {
                $this->validateDossierOrdonnancementCapacity($ordonnancement, $amountsAfter['total_ordonnance'], 'statut');
            }
            BudgetHierarchyService::assertPaiementWithinOrdonnancement([
                'ordonnancement' => $ordonnancement,
                'proposed_paid' => $amountsAfter['total_paiements'],
                'proposed_active_ordonnanced' => $amountsAfter['total_ordonnance'],
                'dossier_statut' => $ordonnancement->statut,
                'error_field' => 'statut',
            ]);

            $actionTitle = match ($newStatut) {
                'Transmis au trésorier' => 'Dossier transmis au trésorier',
                'Payé', 'Clôturé' => 'Paiement effectué',
                'Rejeté' => "Ordre {$replacement?->num_ordre} généré après rejet",
                'Annulé' => 'Dossier annulé',
                default => "Statut changé en {$newStatut}",
            };
            OrdonnancementHistorique::create([
                'ordonnancement_id' => $ordonnancement->id,
                'action' => $actionTitle,
                'auteur' => $currentUser,
                'statut_precedent' => $oldStatut,
                'statut_nouveau' => $ordonnancement->statut,
                'details' => $newStatut === 'Rejeté'
                    ? "Ordre rejeté : {$request->motif_rejet}. Nouvel ordre {$replacement->num_ordre} créé avec le même montant."
                    : "Passage du statut {$oldStatut} à {$newStatut}.",
                'created_at' => now(),
            ]);

            NotificationLigne::clearBudgetCache();

            return response()->json([
                'message' => $newStatut === 'Rejeté'
                    ? "Ordre rejeté. Le nouvel ordre {$replacement->num_ordre} est à payer."
                    : "Statut mis à jour : {$newStatut}",
                'data' => $ordonnancement->fresh(['ordres', 'historiques'])
            ]);
        });
    }

    /**
     * Delete an entire ordonnancement.
     */
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $ordonnancement = Ordonnancement::with(['ordres', 'liquidation'])->lockForUpdate()->findOrFail($id);
            $amounts = BudgetHierarchyService::computeOrdonnancementAmounts($ordonnancement);

            if ($amounts['total_paiements'] > BudgetHierarchyService::EPSILON) {
                throw ValidationException::withMessages([
                    'ordonnancement' => [
                        "Suppression impossible : cet ordonnancement comporte des paiements effectués (" . BudgetHierarchyService::fmt($amounts['total_paiements']) . " DH).",
                    ],
                ]);
            }

            $liqId = $ordonnancement->liquidation_id;
            $ordonnancement->delete();

            if ($liqId) {
                $liq = Liquidation::lockForUpdate()->find($liqId);
                if ($liq) {
                    $remainingOrd = BudgetHierarchyService::getActiveOrdonnancedForLiquidation($liq->id);
                    $montantLiq = (float) ($liq->montant_brut_ttc ?: $liq->montant_ttc ?: 0);
                    if ($remainingOrd <= BudgetHierarchyService::EPSILON) {
                        $liq->statut = 'TRANSMISE À L\'ORDONNANCEMENT';
                    } elseif ($remainingOrd < ($montantLiq - 0.05)) {
                        $liq->statut = 'PARTIELLEMENT ORDONNANCÉE';
                    }
                    $liq->save();
                }
            }

            NotificationLigne::clearBudgetCache();

            return response()->json([
                'message' => 'Ordonnancement supprimé avec succès'
            ]);
        });
    }

    private function validateDossierOrdonnancementCapacity(Ordonnancement $ordonnancement, float $proposedThisDossierTotal, string $errorField = 'montant'): void
    {
        $engaged = null;
        $dossierAlreadyOthers = 0.0;
        $dossierLabel = $ordonnancement->reference ?: ($ordonnancement->num_ordonnancement ?: 'ce dossier');

        if ($ordonnancement->liquidation_id) {
            $liq = Liquidation::with('marche.registreEngagement')->lockForUpdate()->find($ordonnancement->liquidation_id);
            if ($liq) {
                $montantLiquidation = (float) ($liq->montant_brut_ttc ?: $liq->montant_ttc ?: 0);
                $dejaOthersLiq = BudgetHierarchyService::getActiveOrdonnancedForLiquidation($liq->id, $ordonnancement->id);
                $resteLiquidation = round(max(0.0, $montantLiquidation - $dejaOthersLiq), 2);

                if ($proposedThisDossierTotal > ($resteLiquidation + 0.05)) {
                    throw ValidationException::withMessages([
                        $errorField => ["Le montant total des ordres dépasse le montant disponible de la liquidation (" . number_format($resteLiquidation, 2, ',', ' ') . " DH)."],
                    ]);
                }

                if ($liq->marche) {
                    $engaged = BudgetHierarchyService::resolveMarcheEngagedAmount($liq->marche);
                    $dossierAlreadyOthers = BudgetHierarchyService::getActiveOrdonnancedForMarche($liq->marche->id, $ordonnancement->id);
                    $dossierLabel = "le marché {$liq->marche->num_marche}";
                }
            }
        } elseif ($ordonnancement->consultation_id) {
            $consultation = Consultation::with(['engagement', 'registreEngagement', 'liquidation'])
                ->lockForUpdate()
                ->find($ordonnancement->consultation_id);
            if ($consultation) {
                $engaged = BudgetHierarchyService::resolveConsultationEngagedAmount($consultation);
                $dossierAlreadyOthers = BudgetHierarchyService::getActiveOrdonnancedForConsultation($consultation->id, $ordonnancement->id);
                $dossierLabel = "la consultation {$consultation->numero_consultation}";

                if ($consultation->liquidation && (float) $consultation->liquidation->montant_a_payer > 0) {
                    $resteLiqBc = round(max(0.0, min((float) $consultation->liquidation->montant_a_payer, $engaged) - $dossierAlreadyOthers), 2);
                    if ($proposedThisDossierTotal > ($resteLiqBc + 0.05)) {
                        throw ValidationException::withMessages([
                            $errorField => ["Le montant total des ordres dépasse le montant disponible de la liquidation du bon de commande (" . number_format($resteLiqBc, 2, ',', ' ') . " DH)."],
                        ]);
                    }
                }
            }
        } elseif ($ordonnancement->marche_id) {
            $marche = Marche::with('registreEngagement')->lockForUpdate()->find($ordonnancement->marche_id);
            if ($marche) {
                $engaged = BudgetHierarchyService::resolveMarcheEngagedAmount($marche);
                $dossierAlreadyOthers = BudgetHierarchyService::getActiveOrdonnancedForMarche($marche->id, $ordonnancement->id);
                $dossierLabel = "le marché {$marche->num_marche}";
            }
        }

        if ($proposedThisDossierTotal > BudgetHierarchyService::EPSILON) {
            BudgetHierarchyService::assertOrdonnancementWithinEngagement([
                'exercice' => $ordonnancement->exercice ?: date('Y'),
                'domaine' => $ordonnancement->budget_type ?: 'Investissement',
                'art' => $ordonnancement->article,
                'par' => $ordonnancement->paragraphe,
                'lig' => $ordonnancement->ligne,
                'notification_ligne_id' => $ordonnancement->notification_ligne_id,
                'dossier_engaged' => $engaged,
                'dossier_already_ordonnanced' => $dossierAlreadyOthers,
                'dossier_label' => $dossierLabel,
                'proposed_ordonnance' => $proposedThisDossierTotal,
                'exclude_ordonnancement_id' => $ordonnancement->id,
                'error_field' => $errorField,
            ]);
        }
    }

    private function bonCommandeLiquidationAmount(Ordonnancement $ordonnancement): ?float
    {
        if (!$ordonnancement->consultation_id) {
            return null;
        }

        $montant = LiquidationFinanciere::where('consultation_id', $ordonnancement->consultation_id)
            ->value('montant_a_payer');

        return $montant === null ? null : (float) $montant;
    }

    private function attachBonCommandeLiquidation(Ordonnancement $ordonnancement): void
    {
        if ($ordonnancement->liquidation || !$ordonnancement->consultation?->liquidation) {
            return;
        }

        $liq = $ordonnancement->consultation->liquidation;
        $liq->setAttribute('num_liquidation', 'LIQ-BC-' . ($ordonnancement->consultation->annee ?: $ordonnancement->exercice) . '-' . str_pad((string) $ordonnancement->consultation_id, 3, '0', STR_PAD_LEFT));
        $liq->setAttribute('montant_brut_ttc', $liq->montant_a_payer);
        $liq->setAttribute('date_decompte', $liq->date_facture);
        $liq->setAttribute('num_facture', $liq->reference_facture);
        $liq->setAttribute('objet_liquidation', $ordonnancement->consultation->objet_consultation);
        $liq->setAttribute('statut', 'ORDONNANCÉE');
        $ordonnancement->setRelation('liquidation', $liq);
    }
}
