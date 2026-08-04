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
        return response()->json(Consultation::with(['fournisseur', 'budget'])->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Infos Générales
            'annee' => 'required|integer|min:2000',
            'date_consultation' => 'required|date',
            'objet_consultation' => 'required|string|max:255',
            'description_detaillee' => 'nullable|string',
            'categorie' => 'required|string|max:255',
            'type_prestation' => 'required|string|max:255',
            'mode_engagement' => 'required|in:BC,Convention',
            'type_budget' => 'required|in:Investissement,Fonctionnement',
            'delai_execution' => 'required|integer|min:1',
            'statut_dossier' => 'required|string|max:255',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
            // Infos Budgétaires
            'art' => 'required|string|max:255',
            'par' => 'required|string|max:255',
            'lig' => 'required|string|max:255',
            'code_imputation' => 'required|string|max:255',
            'exercice_budgetaire' => 'required|integer',
            'montant_estimatif_ht' => 'required|numeric|min:0',
            'tva' => 'required|numeric|min:0',
        ]);

        try {
            $consultation = DB::transaction(function () use ($validated) {
                // Auto-génération du numéro
                $annee = $validated['annee'];
                // Générer un ID unique temporaire pour le format
                $randomId = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $numero_consultation = "CONS-{$annee}-{$randomId}";

                // Création de la Consultation
                $consultation = Consultation::create([
                    'numero_consultation' => $numero_consultation,
                    'annee' => $validated['annee'],
                    'date_consultation' => $validated['date_consultation'],
                    'objet_consultation' => $validated['objet_consultation'],
                    'description_detaillee' => $validated['description_detaillee'],
                    'categorie' => $validated['categorie'],
                    'type_prestation' => $validated['type_prestation'],
                    'mode_engagement' => $validated['mode_engagement'],
                    'type_budget' => $validated['type_budget'],
                    'delai_execution' => $validated['delai_execution'],
                    'statut_dossier' => $validated['statut_dossier'],
                    'fournisseur_id' => $validated['fournisseur_id'] ?? null,
                ]);

                // Création du Budget associé
                $budget = Budget::create([
                    'consultation_id' => $consultation->id,
                    'art' => $validated['art'],
                    'par' => $validated['par'],
                    'lig' => $validated['lig'],
                    'code_imputation' => $validated['code_imputation'],
                    'exercice_budgetaire' => $validated['exercice_budgetaire'],
                    'montant_estimatif_ht' => $validated['montant_estimatif_ht'],
                    'tva' => $validated['tva'],
                    // montant_ttc est calculé automatiquement dans le modèle Budget
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
        return response()->json($consultation->load(['fournisseur', 'budget', 'prestations', 'engagement', 'offres']));
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
}
