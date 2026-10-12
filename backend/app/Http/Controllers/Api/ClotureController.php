<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Marche;
use App\Models\MarcheCloture;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClotureController extends Controller
{
    public function show($marcheId)
    {
        $marche = Marche::with(['fournisseur', 'aoo', 'cloture'])->findOrFail($marcheId);
        
        return response()->json([
            'marche' => $marche,
            'cloture' => $marche->cloture,
            'workflow' => $marche->workflow
        ]);
    }

    public function store(Request $request, $marcheId)
    {
        $marche = Marche::findOrFail($marcheId);

        // Can only close if ordonnancement is validated, or already in cloture
        if (!in_array($marche->statut, ['ordonnancement_validee', 'cloture_en_cours', 'cloture_validee'])) {
            return response()->json(['error' => 'Le marché doit être ordonnancé avant de pouvoir être clôturé.'], 403);
        }

        $data = $request->validate([
            'type_cautionnement' => 'nullable|string',
            'reference_caution' => 'nullable|string',
            'date_caution' => 'nullable|date',
            'organisme_caution' => 'nullable|string',
            'montant_caution' => 'nullable|numeric|min:0',
            'date_validite_caution' => 'nullable|date',
            
            'motif_mainlevee' => 'nullable|string',
            'conditions_mainlevee' => 'nullable|string',
            'observations_mainlevee' => 'nullable|string',
            'signataire_mainlevee' => 'nullable|string',
            'date_signature_mainlevee' => 'nullable|date',

            'date_reception_provisoire' => 'nullable|date',
            'date_reception_definitive' => 'nullable|date',
            'qualite_execution' => 'nullable|string',
            'respect_delais' => 'boolean',
            'reserves_emises' => 'boolean',
            'reserves_levees' => 'boolean',
            'signataire_certificat' => 'nullable|string',
        ]);

        $cloture = MarcheCloture::updateOrCreate(
            ['marche_id' => $marche->id],
            $data
        );
        
        if ($marche->statut === 'ordonnancement_validee') {
            $marche->statut = 'cloture_en_cours';
            $marche->save();
        }

        return response()->json(['message' => 'Données de clôture enregistrées', 'data' => $cloture]);
    }

    public function valider($marcheId)
    {
        $marche = Marche::with('cloture')->findOrFail($marcheId);

        if (!$marche->cloture) {
            return response()->json(['error' => 'Les données de clôture doivent être renseignées.'], 422);
        }

        $marche->statut = 'cloture_validee';
        $marche->save();

        return response()->json(['message' => 'Marché clôturé définitivement.']);
    }
}
