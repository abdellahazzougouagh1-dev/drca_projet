<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class ConsultationController extends Controller
{
    public function index()
    {
        return response()->json(Consultation::with(['fournisseur', 'budget', 'prestations', 'receptionCommission', 'registreEngagement'])->get());
    }

    public function findByBonCommande(string $numeroBc)
    {
        $consultation = Consultation::with(['fournisseur', 'budget', 'prestations', 'engagement', 'registreEngagement'])
            ->where('numero_bc', $numeroBc)
            ->orWhere('numero_consultation', $numeroBc)
            ->first();

        if (!$consultation) {
            return response()->json(['message' => 'Aucun bon de commande ne correspond à ce numéro.'], 404);
        }

        return response()->json($consultation);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Infos Générales
            'annee' => 'required|integer|min:2000',
            'date_consultation' => 'required|date',
            'objet_consultation' => 'required|string|max:65000',
            'description_detaillee' => 'nullable|string',
            'categorie' => 'required|string|max:255',
            'type_prestation' => 'required|string|max:65000',
            'intitule' => 'nullable|string|max:65000',
            'mode_engagement' => 'required|in:BC,Convention,AO,Bon de commande,Appel d\'offres,Appel d\'offre',
            'type_budget' => 'required|in:Investissement,Fonctionnement',
            'delai_execution' => 'required|integer|min:1',
            'statut_dossier' => 'required|string|max:255',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
            'numero_bc' => 'nullable|string|max:255|unique:consultations,numero_bc',
            // Infos Budgétaires
            'notification_ligne_id' => 'nullable|exists:notification_lignes,id',
            'montant_estimatif_ht' => 'required|numeric|min:0',
            'tva' => 'required|numeric|min:0',
        ]);

        try {
            $consultation = DB::transaction(function () use ($validated, $request) {
                $annee = $validated['annee'];
                $randomId = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $numero_consultation = !empty($request->numero_consultation) ? $request->numero_consultation : "CONS-{$annee}-{$randomId}";

                $notifLigneId = $validated['notification_ligne_id'] ?? null;
                if (!$notifLigneId && $request->filled(['art', 'par', 'lig'])) {
                    $matchedLigne = \App\Models\NotificationLigne::where('article', $request->art)
                        ->where('paragraphe', $request->par)
                        ->where('ligne_budgetaire', $request->lig)
                        ->first();
                    if ($matchedLigne) {
                        $notifLigneId = $matchedLigne->id;
                    }
                }

                // Création de la Consultation
                $consultation = Consultation::create([
                    'numero_consultation' => $numero_consultation,
                    'annee' => $validated['annee'],
                    'date_consultation' => $validated['date_consultation'],
                    'objet_consultation' => $validated['objet_consultation'],
                    'description_detaillee' => $validated['description_detaillee'] ?? null,
                    'categorie' => $validated['categorie'],
                    'type_prestation' => $validated['type_prestation'],
                    'intitule' => $validated['intitule'] ?? ($request->intitule ?? $validated['type_prestation']),
                    'mode_engagement' => $validated['mode_engagement'],
                    'type_budget' => $validated['type_budget'],
                    'delai_execution' => $validated['delai_execution'],
                    'statut_dossier' => $validated['statut_dossier'],
                    'fournisseur_id' => $validated['fournisseur_id'] ?? null,
                    'numero_bc' => $validated['numero_bc'] ?? null,
                    'notification_ligne_id' => $notifLigneId,
                    'date_limite_devis' => $request->date_limite_devis ?? date('Y-m-d', strtotime($validated['date_consultation'] . ' +2 days')),
                    'heure_limite_devis' => $request->heure_limite_devis ?? '10:00',
                    'lieu_execution' => $request->lieu_execution ?? 'REGION DE RABAT SALE KENITRA',
                ]);

                // Récupération de la ligne budgétaire pour auto-remplir le budget
                $ligne = $notifLigneId ? \App\Models\NotificationLigne::with('notification')->find($notifLigneId) : null;

                // Création du Budget associé
                $budget = Budget::create([
                    'consultation_id' => $consultation->id,
                    'art' => $ligne ? $ligne->article : ($request->art ?? ''),
                    'par' => $ligne ? $ligne->paragraphe : ($request->par ?? ''),
                    'lig' => $ligne ? $ligne->ligne_budgetaire : ($request->lig ?? ''),
                    'code_imputation' => $ligne
                        ? "{$ligne->article}/{$ligne->paragraphe}/{$ligne->ligne_budgetaire}"
                        : ($request->code_imputation ?? (($request->art ?? '') . ($request->par ?? '') . ($request->lig ?? ''))),
                    'exercice_budgetaire' => $ligne?->notification?->exercice ?? ($request->exercice_budgetaire ?? $validated['annee']),
                    'montant_estimatif_ht' => $validated['montant_estimatif_ht'],
                    'tva' => $validated['tva'],
                ]);

                return $consultation->load('budget');
            });

            return response()->json($consultation, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la création de la consultation.', 'message' => $e->getMessage()], 500);
        }
    }

    public function show(Consultation $consultation)
    {
        return response()->json($consultation->load(['fournisseur', 'budget', 'prestations', 'engagement', 'registreEngagement', 'offres', 'receptionCommission']));
    }

    public function destroy(Consultation $consultation)
    {
        try {
            DB::transaction(function () use ($consultation) {
                $consultation->budget()?->delete();
                $consultation->prestations()?->delete();
                $consultation->offres()?->delete();
                $consultation->engagement()?->delete();
                $consultation->suiviExecution()?->delete();
                $consultation->receptions()?->delete();
                $consultation->receptionCommission()?->delete();
                $consultation->liquidation()?->delete();

                $consultation->delete();
            });

            return response()->json([
                'message' => 'Consultation supprimée avec succès.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la suppression de la consultation.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Consultation $consultation)
    {
        $validated = $request->validate([
            'objet_consultation' => 'sometimes|required|string|max:65000',
            'objet_consultation_ar' => 'nullable|string|max:65000',
            'intitule' => 'nullable|string|max:65000',
            'type_prestation' => 'nullable|string|max:65000',
            'mode_engagement' => 'sometimes|required|in:BC,Convention,AO,Bon de commande,Appel d\'offres,Appel d\'offre',
            'date_consultation' => 'sometimes|required|date',
            'date_limite_devis' => 'nullable|date',
            'heure_limite_devis' => 'nullable|string|max:50',
            'lieu_execution' => 'nullable|string|max:255',
            'delai_execution' => 'nullable|integer|min:1',
            'lieu_consultation' => 'nullable|string|max:255',
            'lieu_reunion_ar' => 'nullable|string|max:255',
            'cautionnement_provisoire' => 'nullable|numeric|min:0',
            'budget_previsionnel' => 'nullable|numeric|min:0',
            'notes_programmation' => 'nullable|string',
            'numero_bc' => 'nullable|string|max:255|unique:consultations,numero_bc,' . $consultation->id,
            'reference_2' => 'nullable|string|max:255',
            's_lig' => 'nullable|string|max:255',
            'numero_engagement' => 'nullable|string|max:255|unique:consultations,numero_engagement,' . $consultation->id,
            'credit_ouvert_cp' => 'nullable|numeric|min:0',
            'credit_ouvert_ce' => 'nullable|numeric|min:0',
            'depenses_anterieures_ce' => 'nullable|numeric|min:0',
            'depenses_anterieures_cp' => 'nullable|numeric|min:0',
            'depenses_credits_engagement' => 'nullable|numeric|min:0',
            'depenses_credits_consolides' => 'nullable|numeric|min:0',
            'depenses_rap' => 'nullable|numeric|min:0',
            'montant_depense_neuf' => 'nullable|numeric|min:0',
            'interets_moratoires' => 'nullable|numeric|min:0',
            'montant_engager_neuf' => 'nullable|numeric|min:0',
        ]);

        $consultation->update($validated);

        if ($request->hasAny(['art', 'par', 'lig', 'code_imputation', 'exercice_budgetaire'])) {
            $budgetData = array_filter([
                'art' => $request->art,
                'par' => $request->par,
                'lig' => $request->lig,
                'code_imputation' => $request->code_imputation,
                'exercice_budgetaire' => $request->exercice_budgetaire,
            ], fn($v) => $v !== null);

            if ($consultation->budget) {
                $consultation->budget->update($budgetData);
            } else {
                $consultation->budget()->create(array_merge([
                    'art' => $request->art ?? '',
                    'par' => $request->par ?? '',
                    'lig' => $request->lig ?? '',
                    'code_imputation' => $request->code_imputation ?? '',
                    'exercice_budgetaire' => $request->exercice_budgetaire ?? date('Y'),
                    'montant_estimatif_ht' => 0,
                    'tva' => 20,
                ], $budgetData));
            }
        }
        
        if ($request->has('numero_engagement')) {
            $consultation->load(['budget', 'fournisseur', 'engagement', 'notificationLigne']);
            $registre = $consultation->registreEngagement;
            $notificationLigne = $consultation->notificationLigne;
            $creditConsolide = $notificationLigne
                ? (float) ($notificationLigne->reports ?? 0) + (float) ($notificationLigne->credits_neufs ?? 0)
                : null;
            $consultation->registreEngagement()->updateOrCreate(
                ['consultation_id' => $consultation->id],
                [
                    'numero_ordre' => $registre?->numero_ordre
                        ?? ((int) \App\Models\RegistreEngagement::max('numero_ordre') + 1),
                    'engagement_id' => $consultation->engagement?->id,
                    'date_engagement' => $request->date_consultation,
                    'numero_rubrique' => $consultation->numero_engagement,
                    'mode_engagement' => $consultation->mode_engagement,
                    'reference' => $consultation->numero_bc ?: $consultation->numero_consultation,
                    'reference_2' => $consultation->reference_2,
                    'budget' => $consultation->type_budget,
                    'code' => $consultation->budget?->code_imputation,
                    'art' => $consultation->budget?->art,
                    'par' => $consultation->budget?->par,
                    'lig' => $consultation->budget?->lig,
                    's_lig' => $consultation->s_lig,
                    'intitule' => $consultation->intitule,
                    'credit_ouvert_cp' => $consultation->credit_ouvert_cp,
                    'credit_ouvert_ce' => $consultation->credit_ouvert_ce,
                    'credit_consolide' => $creditConsolide,
                    'depenses_anterieures_ce' => $consultation->depenses_anterieures_ce,
                    'depenses_anterieures_cp' => $consultation->depenses_anterieures_cp,
                    'depenses_credits_engagement' => $consultation->depenses_credits_engagement,
                    'depenses_credits_consolides' => $consultation->depenses_credits_consolides,
                    'depenses_rap' => $consultation->depenses_rap,
                    'montant_depense_neuf' => $consultation->montant_depense_neuf,
                    'interets_moratoires' => $consultation->interets_moratoires,
                    'montant_engager_neuf' => $consultation->montant_engager_neuf,
                    'objet' => $consultation->objet_consultation,
                    'beneficiaire' => $consultation->fournisseur?->raison_sociale,
                ]
            );
        }

        return response()->json($consultation->load(['budget', 'registreEngagement']));
    }

    public function syncPrestations(Request $request, Consultation $consultation)
    {
        $validated = $request->validate([
            'prestations' => 'required|array',
            'prestations.*.designation' => 'required|string|max:255',
            'prestations.*.unite' => 'required|string|max:50',
            'prestations.*.quantite' => 'required|numeric|min:0',
            'prestations.*.prix_unitaire_ht' => 'required|numeric|min:0',
            'prestations.*.tva' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($consultation, $validated) {
                // Remove old prestations
                $consultation->prestations()->delete();

                // Create new ones
                foreach ($validated['prestations'] as $prestationData) {
                    $consultation->prestations()->create($prestationData);
                }
            });

            return response()->json([
                'message' => 'Prestations enregistrées avec succès.',
                'consultation' => $consultation->load('prestations')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de l\'enregistrement des prestations.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function generatePdf(Consultation $consultation)
    {
        $consultation->load(['prestations']);

        $totalHT = $consultation->prestations->sum('montant_ht');
        
        $totalTVA = $consultation->prestations->reduce(function ($carry, $prestation) {
            return $carry + ($prestation->montant_ht * ($prestation->tva / 100));
        }, 0);

        $totalTTC = $totalHT + $totalTVA;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.dossier_estimation', [
            'consultation' => $consultation,
            'totalHT' => $totalHT,
            'totalTVA' => $totalTVA,
            'totalTTC' => $totalTTC,
        ]);

        return $pdf->download("Dossier_Estimation_{$consultation->numero_consultation}.pdf");
    }

    // --- MODULE 04: CONSULTATION DES FOURNISSEURS ---

    public function selectFournisseurs(Request $request, Consultation $consultation)
    {
        $validated = $request->validate([
            'fournisseur_ids' => 'required|array',
            'fournisseur_ids.*' => 'exists:fournisseurs,id',
            'date_envoi' => 'nullable|date',
        ]);

        foreach ($validated['fournisseur_ids'] as $fournisseurId) {
            // Utilisation de firstOrCreate pour éviter les doublons si on relance
            $consultation->offres()->firstOrCreate(
                ['fournisseur_id' => $fournisseurId],
                ['date_envoi' => $validated['date_envoi'] ?? now(), 'statut_reponse' => 'En attente']
            );
        }

        return response()->json([
            'message' => 'Fournisseurs associés avec succès.',
            'offres' => $consultation->offres()->with('fournisseur')->get()
        ]);
    }

    public function updateDevis(Request $request, Consultation $consultation, $fournisseurId)
    {
        $validated = $request->validate([
            'montant_propose' => 'nullable|numeric|min:0',
            'statut_reponse' => 'required|in:En attente,Reçu,Hors délai,Refusé',
        ]);

        $offre = $consultation->offres()->where('fournisseur_id', $fournisseurId)->firstOrFail();
        
        $offre->update([
            'montant_propose' => $validated['montant_propose'] ?? $offre->montant_propose,
            'statut_reponse' => $validated['statut_reponse'],
        ]);

        return response()->json([
            'message' => 'Devis mis à jour.',
            'offre' => $offre->load('fournisseur')
        ]);
    }

    public function getComparatif(Consultation $consultation)
    {
        $offres = $consultation->offres()->with('fournisseur')->get();
        return response()->json($offres);
    }

    // --- MODULE 05: COMMISSION ET OUVERTURE DES PLIS ---

    public function saveCommission(Request $request, Consultation $consultation)
    {
        $validated = $request->validate([
            'date_reunion' => 'nullable|date',
            'heure_reunion' => 'nullable|date_format:H:i',
            'lieu_reunion' => 'nullable|string|max:255',
            'president_commission' => 'nullable|string|max:255',
            'membres_commission' => 'nullable|array',
            'observations_commission' => 'nullable|string',
        ]);

        $consultation->update($validated);

        return response()->json([
            'message' => 'Informations de la commission enregistrées.',
            'consultation' => $consultation
        ]);
    }

    public function saveOuverturePlis(Request $request, Consultation $consultation)
    {
        $validated = $request->validate([
            'offres' => 'required|array',
            'offres.*.id' => 'required|exists:offres,id',
            'offres.*.montant_apres_verification' => 'nullable|numeric|min:0',
            'offres.*.retenu' => 'required|boolean',
            'offres.*.observations_offre' => 'nullable|string',
        ]);

        foreach ($validated['offres'] as $offreData) {
            $offre = $consultation->offres()->where('id', $offreData['id'])->first();
            if ($offre) {
                $offre->update([
                    'montant_apres_verification' => $offreData['montant_apres_verification'] ?? $offre->montant_apres_verification,
                    'retenu' => $offreData['retenu'],
                    'observations_offre' => $offreData['observations_offre'] ?? $offre->observations_offre,
                ]);
            }
        }

        return response()->json([
            'message' => 'Résultats de l\'ouverture des plis enregistrés.',
            'offres' => $consultation->offres()->with('fournisseur')->get()
        ]);
    }

    public function saveReceptionCommission(Request $request, Consultation $consultation)
    {
        $validated = $request->validate([
            'numero_bc' => 'nullable|string|max:255',
            'numero_decision' => 'nullable|string|max:255',
            'type_reception' => 'nullable|string|max:255',
            'date_reception_definitive' => 'nullable|date',
            'periode_du' => 'nullable|date',
            'periode_au' => 'nullable|date',
            'prestations_receptionnees' => 'nullable|array',
            'date_decision' => 'nullable|date',
            'date_reunion' => 'nullable|date',
            'heure_reunion' => 'nullable|string|max:50',
            'heure_fin' => 'nullable|string|max:50',
            'lieu_reunion' => 'nullable|string|max:255',
            'membres_commission' => 'nullable|array',
        ]);

        $commission = $consultation->receptionCommission()->updateOrCreate(
            ['consultation_id' => $consultation->id],
            $validated
        );

        return response()->json([
            'message' => 'Commission de réception enregistrée.',
            'reception_commission' => $commission,
            'consultation' => $consultation->load(['receptionCommission', 'budget', 'prestations', 'fournisseur', 'offres'])
        ]);
    }
}
