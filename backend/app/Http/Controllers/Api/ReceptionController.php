<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReceptionController extends Controller
{
    public function getReceptions($consultationId)
    {
        $consultation = \App\Models\Consultation::findOrFail($consultationId);
        return response()->json([
            'receptions' => $consultation->receptions()->orderBy('created_at', 'desc')->get()
        ]);
    }

    public function store(Request $request, $consultationId)
    {
        $consultation = \App\Models\Consultation::findOrFail($consultationId);

        $validated = $request->validate([
            'type_reception' => 'required|in:Provisoire,Définitive',
            'date_reunion' => 'required|date',
            'commission_reception' => 'nullable|array',
            'conformite' => 'required|in:Oui,Non,Avec réserves',
            'reserves_observations' => 'nullable|string'
        ]);

        $reception = $consultation->receptions()->create($validated);

        if ($validated['type_reception'] === 'Définitive' && $validated['conformite'] === 'Oui') {
            $consultation->update(['statut_dossier' => 'CLÔTURÉ']);
        }

        return response()->json([
            'message' => 'Réception enregistrée avec succès.',
            'reception' => $reception,
            'statut_dossier' => $consultation->statut_dossier
        ]);
    }
}
