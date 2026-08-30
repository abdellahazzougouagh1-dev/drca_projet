<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommissionMembre;
use Illuminate\Http\Request;

class CommissionMembreController extends Controller
{
    public function index(Request $request)
    {
        $query = CommissionMembre::query();

        if ($request->filled('nom_prenom')) {
            $query->where('nom_prenom', 'like', '%' . $request->nom_prenom . '%');
        }
        if ($request->filled('fonction')) {
            $query->where('fonction', 'like', '%' . $request->fonction . '%');
        }

        return response()->json($query->orderBy('nom_prenom')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_prenom' => 'required|string|max:255',
            'fonction' => 'nullable|string|max:255',
        ]);

        if (empty($validated['fonction'])) {
            $validated['fonction'] = 'Membre de commission';
        }

        $membre = CommissionMembre::create($validated);

        return response()->json($membre, 201);
    }

    public function show(CommissionMembre $commissionMembre)
    {
        return response()->json($commissionMembre);
    }

    public function update(Request $request, CommissionMembre $commissionMembre)
    {
        $validated = $request->validate([
            'nom_prenom' => 'required|string|max:255',
            'fonction' => 'required|string|max:255',
        ]);

        $commissionMembre->update($validated);

        return response()->json($commissionMembre);
    }

    public function destroy(CommissionMembre $commissionMembre)
    {
        $commissionMembre->delete();

        return response()->json(null, 204);
    }
}
