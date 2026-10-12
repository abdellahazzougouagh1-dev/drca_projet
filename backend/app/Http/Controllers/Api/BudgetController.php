<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index()
    {
        return response()->json(Budget::with('consultation')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'consultation_id' => 'required|exists:consultations,id',
            'art' => 'required|string|max:255',
            'par' => 'required|string|max:255',
            'lig' => 'required|string|max:255',
            'code_imputation' => 'required|string|max:255',
            'exercice' => 'required|integer',
            'montant_ht' => 'required|numeric',
            'tva' => 'required|numeric',
            // montant_ttc est calculé automatiquement dans le Model
        ]);

        $budget = Budget::create($validated);

        return response()->json($budget, 201);
    }

    public function show(Budget $budget)
    {
        return response()->json($budget->load('consultation'));
    }
}
