<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LigneBudgetaire;
use Illuminate\Http\Request;

class LigneBudgetaireController extends Controller
{
    /**
     * Liste des lignes de la nomenclature budgétaire
     */
    public function index(Request $request)
    {
        $query = LigneBudgetaire::query();

        if ($request->filled('type_budget')) {
            $type = $request->query('type_budget');
            $query->where('type_budget', 'like', '%' . $type . '%');
        }

        if ($request->boolean('selectable_only')) {
            $query->where(function ($q) {
                $q->whereNotNull('ligne')
                  ->orWhere('niveau', 'ligne');
            });
        }

        if ($request->filled('q')) {
            $search = $request->query('q');
            $query->where(function ($q) use ($search) {
                $q->where('intitule', 'like', "%{$search}%")
                  ->orWhere('code_imputation', 'like', "%{$search}%")
                  ->orWhere('article', 'like', "%{$search}%")
                  ->orWhere('paragraphe', 'like', "%{$search}%")
                  ->orWhere('ligne', 'like', "%{$search}%");
            });
        }

        $lignes = $query->orderBy('id', 'asc')->get();

        return response()->json($lignes);
    }

    /**
     * Récupérer une ligne spécifique
     */
    public function show(LigneBudgetaire $ligneBudgetaire)
    {
        return response()->json($ligneBudgetaire);
    }
}
