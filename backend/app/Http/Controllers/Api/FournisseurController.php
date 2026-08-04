<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fournisseur;
use Illuminate\Http\Request;

class FournisseurController extends Controller
{
    public function index(Request $request)
    {
        $query = Fournisseur::query();

        if ($request->filled('raison_sociale')) {
            $query->where('raison_sociale', 'like', '%' . $request->raison_sociale . '%');
        }
        if ($request->filled('ice')) {
            $query->where('ice', 'like', '%' . $request->ice . '%');
        }
        if ($request->filled('domaine_activite')) {
            $query->where('domaine_activite', 'like', '%' . $request->domaine_activite . '%');
        }

        return response()->json($query->orderBy('id', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'raison_sociale' => 'required|string|max:255',
            'ice' => 'required|string|unique:fournisseurs,ice|max:255',
            'if' => 'required|string|max:255',
            'patente' => 'nullable|string|max:255',
            'rc' => 'nullable|string|max:255',
            'adresse' => 'required|string',
            'ville' => 'required|string|max:255',
            'telephone' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'representant' => 'nullable|string|max:255',
            'qualite_representant' => 'nullable|string|max:255',
            'cnss' => 'nullable|string|max:255',
            'banque' => 'nullable|string|max:255',
            'agence_bancaire' => 'nullable|string|max:255',
            'rib' => 'nullable|string|max:255',
            'titulaire_compte' => 'nullable|string|max:255',
            'domaine_activite' => 'required|string|max:255',
        ]);

        $fournisseur = Fournisseur::create($validated);

        return response()->json($fournisseur, 201);
    }

    public function show(Fournisseur $fournisseur)
    {
        return response()->json($fournisseur->load('consultations'));
    }

    public function update(Request $request, Fournisseur $fournisseur)
    {
        $validated = $request->validate([
            'raison_sociale' => 'required|string|max:255',
            'ice' => 'required|string|max:255|unique:fournisseurs,ice,' . $fournisseur->id,
            'if' => 'required|string|max:255',
            'patente' => 'nullable|string|max:255',
            'rc' => 'nullable|string|max:255',
            'adresse' => 'required|string',
            'ville' => 'required|string|max:255',
            'telephone' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'representant' => 'nullable|string|max:255',
            'qualite_representant' => 'nullable|string|max:255',
            'cnss' => 'nullable|string|max:255',
            'banque' => 'nullable|string|max:255',
            'agence_bancaire' => 'nullable|string|max:255',
            'rib' => 'nullable|string|max:255',
            'titulaire_compte' => 'nullable|string|max:255',
            'domaine_activite' => 'required|string|max:255',
        ]);

        $fournisseur->update($validated);

        return response()->json($fournisseur);
    }

    public function destroy(Fournisseur $fournisseur)
    {
        $fournisseur->delete();
        return response()->json(null, 204);
    }
}
