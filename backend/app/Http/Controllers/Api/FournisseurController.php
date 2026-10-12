<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fournisseur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    public function bulkStore(Request $request)
    {
        $items = $request->input('fournisseurs', []);

        if (!is_array($items) || count($items) === 0) {
            return response()->json(['message' => 'Aucun fournisseur fourni.'], 422);
        }

        $created = [];
        $errors = [];

        foreach ($items as $index => $data) {
            $validator = Validator::make($data, [
                'raison_sociale' => 'required|string|max:255',
                'ice' => 'required|string|max:255',
                'if' => 'required|string|max:255',
                'adresse' => 'required|string',
                'ville' => 'required|string|max:255',
                'telephone' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'representant' => 'nullable|string|max:255',
                'qualite_representant' => 'nullable|string|max:255',
                'cnss' => 'nullable|string|max:255',
                'banque' => 'nullable|string|max:255',
                'agence_bancaire' => 'nullable|string|max:255',
                'rib' => 'nullable|string|max:255',
                'titulaire_compte' => 'nullable|string|max:255',
                'domaine_activite' => 'nullable|string|max:255',
                'forme_juridique' => 'nullable|string|max:255',
                'capital_social' => 'nullable|string|max:255',
                'taxe_professionnelle' => 'nullable|string|max:255',
                'ville_rc' => 'nullable|string|max:255',
                'fax' => 'nullable|string|max:255',
                'domicile_elu' => 'nullable|string',
                'pays' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                $errors[$index] = $validator->errors()->all();
                continue;
            }

            // If fournisseur with same ICE exists, update it, otherwise create
            $existing = Fournisseur::where('ice', $data['ice'])->first();
            try {
                if ($existing) {
                    $existing->update($data);
                    $created[] = $existing;
                } else {
                    $created[] = Fournisseur::create($data);
                }
            } catch (\Exception $e) {
                $errors[$index] = [$e->getMessage()];
            }
        }

        return response()->json(['created' => $created, 'errors' => $errors]);
    }

    public function store(Request $request)
    {
        if ($request->has('ice') && !empty($request->ice)) {
            $existing = Fournisseur::where('ice', $request->ice)->first();
            if ($existing) {
                return response()->json($existing, 200);
            }
        }

        $validated = $request->validate([
            'raison_sociale' => 'required|string|max:255',
            'ice' => 'required|string|unique:fournisseurs,ice|max:255',
            'if' => 'required|string|max:255',
            'patente' => 'nullable|string|max:255',
            'rc' => 'nullable|string|max:255',
            'adresse' => 'required|string',
            'ville' => 'required|string|max:255',
            'telephone' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'representant' => 'nullable|string|max:255',
            'qualite_representant' => 'nullable|string|max:255',
            'cnss' => 'nullable|string|max:255',
            'banque' => 'nullable|string|max:255',
            'agence_bancaire' => 'nullable|string|max:255',
            'rib' => 'nullable|string|max:255',
            'titulaire_compte' => 'nullable|string|max:255',
            'domaine_activite' => 'nullable|string|max:255',
            'forme_juridique' => 'nullable|string|max:255',
            'capital_social' => 'nullable|string|max:255',
            'taxe_professionnelle' => 'nullable|string|max:255',
            'ville_rc' => 'nullable|string|max:255',
            'fax' => 'nullable|string|max:255',
            'domicile_elu' => 'nullable|string',
            'pays' => 'nullable|string|max:255',
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
            'email' => 'nullable|email|max:255',
            'representant' => 'nullable|string|max:255',
            'qualite_representant' => 'nullable|string|max:255',
            'cnss' => 'nullable|string|max:255',
            'banque' => 'nullable|string|max:255',
            'agence_bancaire' => 'nullable|string|max:255',
            'rib' => 'nullable|string|max:255',
            'titulaire_compte' => 'nullable|string|max:255',
            'domaine_activite' => 'nullable|string|max:255',
            'forme_juridique' => 'nullable|string|max:255',
            'capital_social' => 'nullable|string|max:255',
            'taxe_professionnelle' => 'nullable|string|max:255',
            'ville_rc' => 'nullable|string|max:255',
            'fax' => 'nullable|string|max:255',
            'domicile_elu' => 'nullable|string',
            'pays' => 'nullable|string|max:255',
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
