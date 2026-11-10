<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BudgetHierarchyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LiquidationFinanciereController extends Controller
{
    public function getLiquidation($consultationId)
    {
        $consultation = \App\Models\Consultation::findOrFail($consultationId);
        return response()->json([
            'liquidation' => $consultation->liquidation
        ]);
    }

    public function store(Request $request, $consultationId)
    {
        $consultation = \App\Models\Consultation::findOrFail($consultationId);

        $validated = $request->validate([
            'montant_a_payer' => 'required|numeric|min:0',
            'reference_facture' => 'nullable|string',
            'date_facture' => 'nullable|date',
            'ordre_imputation' => 'nullable|string',
            'ordre_paiement' => 'nullable|string',
            'ordre_virement' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($consultationId, $validated) {
            $consultation = \App\Models\Consultation::with(['engagement', 'registreEngagement', 'liquidation'])
                ->lockForUpdate()
                ->findOrFail($consultationId);

            $engaged = BudgetHierarchyService::resolveConsultationEngagedAmount($consultation);
            if ($engaged <= BudgetHierarchyService::EPSILON) {
                throw ValidationException::withMessages([
                    'consultation_id' => [
                        "Règle budgétaire (Ordonnancement <= Engagement) : aucun engagement actif (> 0 DH) trouvé pour cette consultation.",
                    ],
                ]);
            }

            $montantAPayer = round((float) $validated['montant_a_payer'], 2);
            if ($montantAPayer > ($engaged + BudgetHierarchyService::EPSILON)) {
                throw ValidationException::withMessages([
                    'montant_a_payer' => [
                        "Règle budgétaire (Ordonnancement <= Engagement) : le montant liquidé ("
                        . BudgetHierarchyService::fmt($montantAPayer) . " DH) ne peut pas dépasser le montant engagé ("
                        . BudgetHierarchyService::fmt($engaged) . " DH).",
                    ],
                ]);
            }

            $activeOrd = BudgetHierarchyService::getActiveOrdonnancedForConsultation($consultation->id);
            if ($montantAPayer + BudgetHierarchyService::EPSILON < $activeOrd) {
                throw ValidationException::withMessages([
                    'montant_a_payer' => [
                        "Règle budgétaire (Ordonnancement <= Engagement) : le montant liquidé ("
                        . BudgetHierarchyService::fmt($montantAPayer) . " DH) ne peut pas être inférieur aux ordonnancements déjà émis ("
                        . BudgetHierarchyService::fmt($activeOrd) . " DH).",
                    ],
                ]);
            }

            $liquidation = $consultation->liquidation()->updateOrCreate(
                ['consultation_id' => $consultationId],
                $validated
            );

            if (!in_array(strtoupper((string) $consultation->statut_dossier), ['ORDONNANCÉ', 'ORDONNANCE', 'PAYÉ', 'PAYE', 'CLÔTURÉ', 'CLOTURE'], true)) {
                $consultation->update(['statut_dossier' => 'LIQUIDÉ']);
            }

            return response()->json([
                'message' => 'Liquidation financière validée avec succès.',
                'liquidation' => $liquidation,
                'statut_dossier' => $consultation->statut_dossier,
            ]);
        });
    }
}
