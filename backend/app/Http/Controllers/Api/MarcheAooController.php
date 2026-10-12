<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarcheAoo;
use Illuminate\Http\Request;

class MarcheAooController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $marches = MarcheAoo::orderBy('created_at', 'desc')->get();
        return response()->json($marches);
    }

    /**
     * Store a newly created resource in storage or update if ID is provided.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|exists:marches_aoo,id',
            'num_aoo' => 'required|string',
            'objet' => 'required|string',
            'date_ouverture' => 'required|date',
            'heure_ouverture' => 'required|string',
            'nombre_lots' => 'required|integer',
            'journal_francais' => 'nullable|string',
            'journal_arabe' => 'nullable|string',
            'budget_type' => 'nullable|string',
            'code_budget' => 'nullable|string',
            'art' => 'nullable|string',
            'par' => 'nullable|string',
            'lig' => 'nullable|string',
            'caution_provisoire' => 'nullable|numeric',
            'estimation_administrative' => 'nullable|numeric',
            'aoo_president' => 'nullable|string',
            'aoo_membre1' => 'nullable|string',
            'aoo_membre2' => 'nullable|string',
            'num_marche' => 'nullable|string',
            'titulaire' => 'nullable|string',
            'montant_max' => 'nullable|numeric',
            'delai_execution' => 'nullable|string',
            'date_reception' => 'nullable|date',
            'membre_reception_1' => 'nullable|string',
        ]);

        if (isset($data['id']) && $data['id']) {
            $marche = MarcheAoo::findOrFail($data['id']);
            $marche->update($data);
        } else {
            $marche = MarcheAoo::create($data);
        }

        return response()->json([
            'message' => 'Dossier AOO enregistré avec succès',
            'data' => $marche
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $marche = MarcheAoo::findOrFail($id);
        return response()->json($marche);
    }
}
