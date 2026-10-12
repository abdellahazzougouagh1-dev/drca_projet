<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SuiviExecutionController extends Controller
{
    public function getSuivi($consultationId)
    {
        $consultation = \App\Models\Consultation::findOrFail($consultationId);
        $suivi = $consultation->suiviExecution()->with(['evenements' => function($q) {
            $q->orderBy('date_evenement', 'desc')->orderBy('id', 'desc');
        }])->first();

        if (!$suivi) {
            // First time accessing, we can create an empty record or return defaults. Let's return defaults or null and let frontend handle it.
            // Better to auto-create it if it doesn't exist
            $suivi = \App\Models\SuiviExecution::create([
                'consultation_id' => $consultation->id,
                'avancement_pourcentage' => 0,
                'statut_execution' => 'Non démarré'
            ]);
            $suivi->load('evenements');
        }

        return response()->json([
            'suivi' => $suivi
        ]);
    }

    public function updateSuivi(Request $request, $consultationId)
    {
        $consultation = \App\Models\Consultation::findOrFail($consultationId);
        $suivi = $consultation->suiviExecution()->firstOrFail();

        $validated = $request->validate([
            'date_debut' => 'nullable|date',
            'date_fin_previsionnelle' => 'nullable|date',
            'avancement_pourcentage' => 'required|integer|min:0|max:100',
            'statut_execution' => 'required|in:Non démarré,En cours,Bloqué,Terminé',
            'observations' => 'nullable|string',
        ]);

        $suivi->update($validated);

        return response()->json([
            'message' => 'Suivi mis à jour avec succès.',
            'suivi' => $suivi
        ]);
    }

    public function addEvenement(Request $request, $consultationId)
    {
        $consultation = \App\Models\Consultation::findOrFail($consultationId);
        $suivi = $consultation->suiviExecution()->firstOrFail();

        $validated = $request->validate([
            'type_evenement' => 'required|in:Jalon,Incident,Note',
            'description' => 'required|string',
            'date_evenement' => 'required|date',
        ]);

        $evenement = $suivi->evenements()->create($validated);

        return response()->json([
            'message' => 'Événement ajouté avec succès.',
            'evenement' => $evenement
        ]);
    }
}
