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

        return response()->json([
            'consultation' => $consultation,
            'fournisseur' => $offreRetenue->fournisseur,
            'montant_valide' => $offreRetenue->montant_apres_verification ?? $offreRetenue->montant_propose,
            'type_engagement' => $consultation->mode_engagement === 'Bon de commande' ? 'BC' : 'Convention',
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

        $numero_engagement = 'ENG-' . date('Y') . '-' . $consultation->id;

        $engagement = \App\Models\Engagement::create(array_merge($validated, [
            'consultation_id' => $consultation->id,
            'numero_engagement' => $numero_engagement,
        ]));

        $consultation->update([
            'statut_dossier' => 'ENGAGÉ'
        ]);

        return response()->json([
            'message' => 'Engagement enregistré avec succès.',
            'engagement' => $engagement
        ]);
    }
}
