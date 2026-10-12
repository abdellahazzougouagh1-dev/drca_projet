<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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

        $engagement = $consultation->engagement;
        if (!$engagement) {
            return response()->json(['message' => 'Aucun engagement trouvé pour cette consultation.'], 400);
        }

        if ($validated['montant_a_payer'] > $engagement->montant_engagement) {
            return response()->json(['message' => 'Le montant à payer ('.$validated['montant_a_payer'].') ne peut pas être supérieur au montant engagé ('.$engagement->montant_engagement.').'], 400);
        }

        $liquidation = $consultation->liquidation()->updateOrCreate(
            ['consultation_id' => $consultationId],
            $validated
        );

        $consultation->update(['statut_dossier' => 'PAYÉ']);

        return response()->json([
            'message' => 'Liquidation financière validée avec succès.',
            'liquidation' => $liquidation,
            'statut_dossier' => $consultation->statut_dossier
        ]);
    }
}
