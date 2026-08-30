<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EngagementController extends Controller
{
    public function getEngagementData($consultationId)
    {
        $consultation = \App\Models\Consultation::findOrFail($consultationId);
        
        $offreRetenue = $consultation->offres()
            ->with('fournisseur')
            ->where('retenu', true)
            ->first();

        if (!$offreRetenue) {
            return response()->json([
                'message' => 'Aucun prestataire retenu pour cette consultation.'
            ], 404);
        }

        $ligneBudgetaire = $consultation->notificationLigne;
        $creditDisponible = $ligneBudgetaire ? $ligneBudgetaire->credits_disponibles : null;

        return response()->json([
            'consultation' => $consultation,
            'fournisseur' => $offreRetenue->fournisseur,
            'montant_valide' => $offreRetenue->montant_apres_verification ?? $offreRetenue->montant_propose,
            'type_engagement' => $consultation->mode_engagement === 'Bon de commande' ? 'BC' : 'Convention',
            'ligne_budgetaire' => $ligneBudgetaire,
            'credit_disponible' => $creditDisponible,
        ]);
    }

    public function store(Request $request, $consultationId)
    {
        $consultation = \App\Models\Consultation::findOrFail($consultationId);

        $validated = $request->validate([
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'montant_engagement' => 'required|numeric|min:0',
            'type_engagement' => 'required|in:BC,Convention',
            'date_engagement' => 'required|date',
            'delai_execution' => 'required|integer|min:1',
            'date_notification' => 'nullable|date',
            'date_commencement' => 'nullable|date',
        ]);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $ligneBudgetaire = $consultation->notificationLigne;
            
            if ($ligneBudgetaire) {
                // Verrouiller la ligne pour éviter les race conditions si possible, 
                // ou simplement recalculer avec lockForUpdate() si applicable.
                $ligne = \App\Models\NotificationLigne::lockForUpdate()->find($ligneBudgetaire->id);
                
                $disponible = $ligne->credits_disponibles;
                
                if ($validated['montant_engagement'] > $disponible) {
                    \Illuminate\Support\Facades\DB::rollBack();
                    return response()->json([
                        'message' => "Crédit budgétaire insuffisant.\nCrédit disponible : " . number_format($disponible, 2, ',', ' ') . " MAD\nMontant de l'engagement : " . number_format($validated['montant_engagement'], 2, ',', ' ') . " MAD\nDépassement : " . number_format($validated['montant_engagement'] - $disponible, 2, ',', ' ') . " MAD"
                    ], 422);
                }
            }

            $numero_engagement = 'ENG-' . date('Y') . '-' . $consultation->id;

            $engagement = \App\Models\Engagement::create(array_merge($validated, [
                'consultation_id' => $consultation->id,
                'numero_engagement' => $numero_engagement,
            ]));

            $consultation->update([
                'statut_dossier' => 'ENGAGÉ'
            ]);

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'message' => 'Engagement enregistré avec succès.',
                'engagement' => $engagement
            ]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'message' => 'Une erreur est survenue lors de la création de l\'engagement.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
