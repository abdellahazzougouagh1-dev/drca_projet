<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Liquidation;
use App\Models\Marche;
use App\Models\Ordonnancement;
use App\Models\OrdonnancementHistorique;
use App\Models\OrdonnancementOrdre;
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
            $query->where('statut', $request->statut);
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
            'marche.aoo',
            'marche.lot',
            'lignes',
            'ordonnancements.ordres'
        ])
        ->orderBy('id', 'desc')
        ->get();

        $disponibles = [];

        foreach ($liquidationsMarches as $liq) {
            $marche = $liq->marche;
            $fournisseur = $marche->fournisseur ?? null;
            $notifLigne = $marche->notificationLigne ?? null;

            $montantLiquidation = (float) ($liq->montant_brut_ttc ?: $liq->montant_ttc ?: 0);
            $montantHt = (float) ($liq->montant_brut_ht ?: $liq->montant_ht ?: 0);
            $montantTva = (float) ($liq->montant_tva ?: ($montantLiquidation - $montantHt));

            // Calculate amount already ordonnanced across all existing ordonnancements for this liquidation
            $dejaOrdonnance = 0;
            if ($liq->ordonnancements) {
                foreach ($liq->ordonnancements as $ord) {
                    $dejaOrdonnance += (float) ($ord->ordres->sum('montant') ?: $ord->montant_brut ?: 0);
                }
            }

            $resteDisponible = round(max(0, $montantLiquidation - $dejaOrdonnance), 2);

            // If totally ordonnanced (reste <= 0.01), DO NOT include in available liquidations
            if ($resteDisponible <= 0.01 && $montantLiquidation > 0) {
                continue;
            }

            // Default suggested retenues if user decides to apply them
            $suggestedTva = round($montantTva > 0 ? $montantTva : ($resteDisponible * 0.20 / 1.20), 2);
            $suggestedIas = round($liq->autres_deductions > 0 ? $liq->autres_deductions : ($montantHt * 0.05), 2);

            // Extract budget imputation
            $art = $marche->article_budget ?? $notifLigne->article ?? '415';
            $par = $marche->paragraphe_budget ?? $notifLigne->paragraphe ?? '20';
            $lig = $marche->ligne_budget ?? $notifLigne->ligne_budgetaire ?? '13';
            $sLig = '0';
            $code = $marche->code_budget ?? '225320';

            // Procedure detection
            $typeProcedure = 'Marché';
            $ref = $marche->num_marche ?? 'Marché';
            if (stripos($ref, 'BC') !== false || stripos($ref, 'Bon') !== false) {
                $typeProcedure = 'Bon de commande';
            } elseif (stripos($ref, 'CONV') !== false) {
                $typeProcedure = 'Convention';
            }

            $disponibles[] = [
                'type_source' => 'marche_liquidation',
                'liquidation_id' => $liq->id,
                'marche_id' => $marche->id ?? null,
                'consultation_id' => null,
                'fournisseur_id' => $fournisseur->id ?? null,
                'notification_ligne_id' => $notifLigne->id ?? null,
                'num_liquidation' => $liq->num_liquidation ?: "LIQ-".($liq->exercice_budgetaire ?: date('Y'))."-".str_pad($liq->id, 3, '0', STR_PAD_LEFT),
                'date_liquidation' => $liq->date_decompte ?: $liq->date_service_fait ?: $liq->created_at->format('Y-m-d'),
                'reference' => $ref,
                'type_procedure' => $typeProcedure,
                'beneficiaire' => $fournisseur->raison_sociale ?? $marche->titulaire ?? 'Fournisseur non spécifié',
                'rib' => $fournisseur->rib ?? $fournisseur->compte_bancaire ?? '',
                'banque' => $fournisseur->banque ?? '',
                'budget_type' => $marche->type_budget ?? $notifLigne->type_budget ?? 'Investissement',
                'creance' => 'Reste à payer',
                'code_imputation' => $code,
                'article' => $art,
                'paragraphe' => $par,
                'ligne' => $lig,
                'sous_ligne' => $sLig,
                'intitule_depense' => $liq->objet_liquidation ?: $marche->objet_marche ?: ($notifLigne->libelle ?? 'Dépense d\'investissement'),
                'montant_brut' => $montantLiquidation,
                'montant_ht' => $montantHt,
                'deja_ordonnance' => $dejaOrdonnance,
                'reste_disponible' => $resteDisponible,
                'suggested_tva' => $suggestedTva,
                'suggested_ias' => $suggestedIas,
                'credit_consolide' => (float) ($marche->depenses_credits_consolides ?? $notifLigne->reports ?? 0),
                'credit_neuf' => (float) ($marche->montant_engager_neuf ?? $notifLigne->credits_neufs ?? 0),
                'statut_liquidation' => $liq->statut,
            ];
        }

        // Filter to only include valid liquidations with reste_disponible > 0
        $disponibles = array_values(array_filter($disponibles, function($item) {
            return ($item['reste_disponible'] ?? 0) > 0;
        }));

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

        return DB::transaction(function () use ($validated, $request) {
            $year = $validated['exercice'] ?? Carbon::parse($validated['date_ordonnancement'])->format('Y');

            // 1. Check liquidation remaining balance
            $liq = null;
            $montantLiquidation = (float) $validated['montant_brut'];
            $totalOrdres = collect($validated['ordres'])->sum(function ($o) {
                return (float) ($o['montant'] ?? 0);
            });

            if (!empty($validated['liquidation_id'])) {
                $liq = Liquidation::with('ordonnancements.ordres')->find($validated['liquidation_id']);
                if ($liq) {
                    $montantLiquidation = (float) ($liq->montant_brut_ttc ?: $liq->montant_ttc ?: $validated['montant_brut']);
                    $dejaOrdonnance = 0;
                    foreach ($liq->ordonnancements as $ord) {
                        $dejaOrdonnance += (float) ($ord->ordres->sum('montant') ?: $ord->montant_brut ?: 0);
                    }
                    $resteDisponible = round(max(0, $montantLiquidation - $dejaOrdonnance), 2);

                    if ($totalOrdres > ($resteDisponible + 0.05)) {
                        throw ValidationException::withMessages([
                            'ordres' => ["Le montant total des ordres (" . number_format($totalOrdres, 2, ',', ' ') . " DH) dépasse le montant disponible de la liquidation (" . number_format($resteDisponible, 2, ',', ' ') . " DH)."]
                        ]);
                    }
                }
            }

            // 2. Generate unique ORD number
            $count = Ordonnancement::where('exercice', $year)->count() + 1;
            $numOrdonnancement = "ORD-{$year}-" . str_pad($count, 3, '0', STR_PAD_LEFT);

            // 3. Compute Ordonnancement status
            $statutOrdonnance = 'Totalement ordonnancée';
            if ($totalOrdres <= 0) {
                $statutOrdonnance = 'Non ordonnancée';
            } elseif ($totalOrdres < ($montantLiquidation - 0.05)) {
                $statutOrdonnance = 'Partiellement ordonnancée';
            }

            $validated['num_ordonnancement'] = $numOrdonnancement;
            $validated['num_op'] = "OP-" . str_pad($count, 3, '0', STR_PAD_LEFT);
            $validated['exercice'] = $year;
            $validated['statut'] = $validated['statut'] ?? $statutOrdonnance;
            $validated['montant_brut'] = $totalOrdres; // The total of orders in this dossier
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

            // 5. Update liquidation status if linked
            if ($liq) {
                $newDejaOrdonnance = $dejaOrdonnance + $totalOrdres;
                if ($newDejaOrdonnance >= ($montantLiquidation - 0.05)) {
                    $liq->statut = 'TOTALEMENT ORDONNANCÉE';
                } else {
                    $liq->statut = 'PARTIELLEMENT ORDONNANCÉE';
                }
                $liq->save();
            }

            // 6. Update Marche status if linked
            if (!empty($validated['marche_id'])) {
                $marche = Marche::find($validated['marche_id']);
                if ($marche) {
                    $marche->statut = 'ordonnancement_validee';
                    $marche->save();
                }
            }

            // 7. Create Historique records
            $currentUser = auth()->user()->name ?? 'admin';
            OrdonnancementHistorique::create([
                'ordonnancement_id' => $ordonnancement->id,
                'action' => 'Dossier créé',
                'auteur' => $currentUser,
                'statut_precedent' => null,
                'statut_nouveau' => $ordonnancement->statut,
                'details' => "Création de l'ordonnancement {$ordonnancement->num_ordonnancement} pour un montant de " . number_format($totalOrdres, 2, ',', ' ') . " DH (" . count($ordres) . " ordre(s)).",
                'created_at' => now(),
            ]);

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
            'consultation'
        ])->findOrFail($id);

        return response()->json([
            'data' => $ordonnancement
        ]);
    }

    /**
     * Update ordonnancement.
     */
    public function update(Request $request, $id)
    {
        $ordonnancement = Ordonnancement::findOrFail($id);

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

        $ordonnancement->update($validated);

        return response()->json([
            'message' => 'Ordonnancement mis à jour avec succès',
            'data' => $ordonnancement->fresh(['ordres', 'historiques', 'fournisseur'])
        ]);
    }

    /**
     * Add a payment movement/order to ordonnancement.
     */
    public function addOrdre(Request $request, $id)
    {
        $ordonnancement = Ordonnancement::with(['ordres', 'liquidation.ordonnancements.ordres'])->findOrFail($id);

        $validated = $request->validate([
            'num_ordre' => 'nullable|string',
            'type_mouvement' => 'required|string',
            'mode_paiement' => 'required|string',
            'beneficiaire' => 'required|string',
            'rib_compte' => 'nullable|string',
            'banque_agence' => 'nullable|string',
            'creance' => 'required|string',
            'montant' => 'required|numeric|min:0.01',
            'observations' => 'nullable|string',
        ]);

        $montantNouveau = (float) $validated['montant'];

        // Liquidation remaining balance validation
        if ($ordonnancement->liquidation) {
            $liq = $ordonnancement->liquidation;
            $montantLiquidation = (float) ($liq->montant_brut_ttc ?: $liq->montant_ttc ?: 0);
            
            // Total ordonnanced so far across all ordonnancements for this liquidation
            $dejaOrdonnance = 0;
            foreach ($liq->ordonnancements as $ord) {
                $dejaOrdonnance += (float) ($ord->ordres->sum('montant') ?: $ord->montant_brut ?: 0);
            }
            $resteDisponible = round(max(0, $montantLiquidation - $dejaOrdonnance), 2);

            if ($montantNouveau > ($resteDisponible + 0.05)) {
                throw ValidationException::withMessages([
                    'montant' => ["Le montant total des ordres dépasse le montant disponible de la liquidation (" . number_format($resteDisponible, 2, ',', ' ') . " DH)."]
                ]);
            }
        }

        // Auto generate sequential order number OP-001, OP-002...
        $orderCount = $ordonnancement->ordres()->count() + 1;
        $orderSeq = str_pad($orderCount, 3, '0', STR_PAD_LEFT);
        $validated['num_ordre'] = !empty($validated['num_ordre']) ? $validated['num_ordre'] : "OP-{$orderSeq}";
        $validated['statut'] = $validated['statut'] ?? 'À payer';

        $ordre = $ordonnancement->ordres()->create($validated);

        // Recalculate ordonnancement total & status
        $totalOrdres = (float) $ordonnancement->ordres()->sum('montant');
        $ordonnancement->montant_brut = $totalOrdres;
        $ordonnancement->net_a_payer = (float) $ordonnancement->ordres()->where('type_mouvement', 'Paiement fournisseur')->sum('montant');
        
        if ($ordonnancement->liquidation) {
            $montantLiq = (float) ($ordonnancement->liquidation->montant_brut_ttc ?: $ordonnancement->liquidation->montant_ttc ?: $totalOrdres);
            $ordonnancement->statut = ($totalOrdres >= ($montantLiq - 0.05)) ? 'Totalement ordonnancée' : 'Partiellement ordonnancée';
        }
        $ordonnancement->save();

        $currentUser = auth()->user()->name ?? 'admin';
        OrdonnancementHistorique::create([
            'ordonnancement_id' => $ordonnancement->id,
            'action' => "Ordre {$ordre->num_ordre} ajouté",
            'auteur' => $currentUser,
            'details' => "Ajout de l'ordre {$ordre->num_ordre} ({$ordre->type_mouvement}) au profit de {$ordre->beneficiaire} pour " . number_format($ordre->montant, 2, ',', ' ') . " DH.",
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Ordre de paiement ajouté avec succès',
            'data' => $ordre,
            'ordonnancement' => $ordonnancement->fresh(['ordres', 'historiques'])
        ], 201);
    }

    /**
     * Update an order.
     */
    public function updateOrdre(Request $request, $id, $ordreId)
    {
        $ordonnancement = Ordonnancement::with(['ordres', 'liquidation.ordonnancements.ordres'])->findOrFail($id);
        $ordre = $ordonnancement->ordres()->findOrFail($ordreId);

        $validated = $request->validate([
            'num_ordre' => 'nullable|string',
            'type_mouvement' => 'required|string',
            'mode_paiement' => 'required|string',
            'beneficiaire' => 'required|string',
            'rib_compte' => 'nullable|string',
            'banque_agence' => 'nullable|string',
            'creance' => 'required|string',
            'montant' => 'required|numeric|min:0.01',
            'statut' => 'nullable|string',
            'observations' => 'nullable|string',
        ]);

        $montantNouveau = (float) $validated['montant'];
        $ancienMontant = (float) $ordre->montant;

        // Liquidation remaining balance validation
        if ($ordonnancement->liquidation) {
            $liq = $ordonnancement->liquidation;
            $montantLiquidation = (float) ($liq->montant_brut_ttc ?: $liq->montant_ttc ?: 0);
            
            $dejaOrdonnance = 0;
            foreach ($liq->ordonnancements as $ord) {
                $dejaOrdonnance += (float) ($ord->ordres->sum('montant') ?: $ord->montant_brut ?: 0);
            }
            // Add back current order's old amount to compute actual capacity
            $resteDisponible = round(max(0, $montantLiquidation - ($dejaOrdonnance - $ancienMontant)), 2);

            if ($montantNouveau > ($resteDisponible + 0.05)) {
                throw ValidationException::withMessages([
                    'montant' => ["Le montant total des ordres dépasse le montant disponible de la liquidation (" . number_format($resteDisponible, 2, ',', ' ') . " DH)."]
                ]);
            }
        }

        $ordre->update($validated);

        // Recalculate ordonnancement total & status
        $totalOrdres = (float) $ordonnancement->ordres()->sum('montant');
        $ordonnancement->montant_brut = $totalOrdres;
        $ordonnancement->net_a_payer = (float) $ordonnancement->ordres()->where('type_mouvement', 'Paiement fournisseur')->sum('montant');
        
        if ($ordonnancement->liquidation) {
            $montantLiq = (float) ($ordonnancement->liquidation->montant_brut_ttc ?: $ordonnancement->liquidation->montant_ttc ?: $totalOrdres);
            $ordonnancement->statut = ($totalOrdres >= ($montantLiq - 0.05)) ? 'Totalement ordonnancée' : 'Partiellement ordonnancée';
        }
        $ordonnancement->save();

        return response()->json([
            'message' => 'Ordre mis à jour avec succès',
            'data' => $ordre,
            'ordonnancement' => $ordonnancement->fresh(['ordres', 'historiques'])
        ]);
    }

    /**
     * Delete an order.
     */
    public function deleteOrdre($id, $ordreId)
    {
        $ordonnancement = Ordonnancement::with(['ordres', 'liquidation'])->findOrFail($id);
        $ordre = $ordonnancement->ordres()->findOrFail($ordreId);
        
        $desc = "{$ordre->num_ordre} ({$ordre->type_mouvement}) - {$ordre->beneficiaire} (" . number_format($ordre->montant, 2, ',', ' ') . " DH)";
        $ordre->delete();

        // Recalculate ordonnancement total & status
        $totalOrdres = (float) $ordonnancement->ordres()->sum('montant');
        $ordonnancement->montant_brut = $totalOrdres;
        $ordonnancement->net_a_payer = (float) $ordonnancement->ordres()->where('type_mouvement', 'Paiement fournisseur')->sum('montant');
        
        if ($ordonnancement->liquidation) {
            $montantLiq = (float) ($ordonnancement->liquidation->montant_brut_ttc ?: $ordonnancement->liquidation->montant_ttc ?: $totalOrdres);
            $ordonnancement->statut = ($totalOrdres >= ($montantLiq - 0.05)) ? 'Totalement ordonnancée' : ($totalOrdres > 0 ? 'Partiellement ordonnancée' : 'Non ordonnancée');
        }
        $ordonnancement->save();

        $currentUser = auth()->user()->name ?? 'admin';
        OrdonnancementHistorique::create([
            'ordonnancement_id' => $ordonnancement->id,
            'action' => 'Ordre supprimé',
            'auteur' => $currentUser,
            'details' => "Suppression de l'ordre : {$desc}",
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Ordre supprimé avec succès',
            'ordonnancement' => $ordonnancement->fresh(['ordres', 'historiques'])
        ]);
    }

    /**
     * Update workflow status (Brouillon -> À payer -> Transmis au trésorier -> Payé -> Rejeté).
     */
    public function updateStatut(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|string|in:Brouillon,À payer,À vérifier,Ordonnancé,Transmis au trésorier,Payé,Rejeté,Annulé',
            'motif_rejet' => 'nullable|string',
            'date_transmission_tresorier' => 'nullable|date',
            'date_paiement' => 'nullable|date',
        ]);

        $ordonnancement = Ordonnancement::findOrFail($id);
        $oldStatut = $ordonnancement->statut;
        $newStatut = $request->statut;

        $ordonnancement->statut = $newStatut;
        if ($newStatut === 'Transmis au trésorier') {
            $ordonnancement->date_transmission_tresorier = $request->date_transmission_tresorier ?: now()->format('Y-m-d');
        } elseif ($newStatut === 'Payé') {
            $ordonnancement->date_paiement = $request->date_paiement ?: now()->format('Y-m-d');
            $ordonnancement->ordres()->update(['statut' => 'Payé']);
        } elseif ($newStatut === 'Rejeté') {
            $ordonnancement->motif_rejet = $request->motif_rejet;
        }
        $ordonnancement->save();

        $currentUser = auth()->user()->name ?? 'admin';
        $actionTitle = match ($newStatut) {
            'Transmis au trésorier' => 'Dossier transmis au trésorier',
            'Payé' => 'Paiement effectué',
            'Rejeté' => 'Dossier rejeté',
            'Annulé' => 'Dossier annulé',
            default => "Statut changé en {$newStatut}",
        };

        OrdonnancementHistorique::create([
            'ordonnancement_id' => $ordonnancement->id,
            'action' => $actionTitle,
            'auteur' => $currentUser,
            'statut_precedent' => $oldStatut,
            'statut_nouveau' => $newStatut,
            'details' => $newStatut === 'Rejeté' ? "Motif : {$request->motif_rejet}" : "Passage du statut {$oldStatut} à {$newStatut}.",
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => "Statut mis à jour : {$newStatut}",
            'data' => $ordonnancement->fresh(['ordres', 'historiques'])
        ]);
    }

    /**
     * Delete an entire ordonnancement.
     */
    public function destroy($id)
    {
        $ordonnancement = Ordonnancement::findOrFail($id);
        
        // Revert liquidation status if applicable
        if ($ordonnancement->liquidation_id) {
            $liq = Liquidation::find($ordonnancement->liquidation_id);
            if ($liq && $liq->statut === 'ORDONNANCÉE') {
                $liq->statut = 'TRANSMISE À L\'ORDONNANCEMENT';
                $liq->save();
            }
        }

        $ordonnancement->delete();

        return response()->json([
            'message' => 'Ordonnancement supprimé avec succès'
        ]);
    }
}
