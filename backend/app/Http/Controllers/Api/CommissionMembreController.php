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

    public static function storeRules(): array
    {
        return [
            'nom_prenom' => ['required', 'string', 'max:255', 'not_regex:/\A\s*\z/u'],
            'fonction' => ['required', 'string', 'max:255', 'not_regex:/\A\s*\z/u'],
        ];
    }

    public static function updateRules(): array
    {
        return self::storeRules();
    }

    public function store(Request $request)
    {
        $validated = $request->validate(self::storeRules());
        $validated['nom_prenom'] = trim($validated['nom_prenom']);
        $validated['fonction'] = trim($validated['fonction']);

        $membre = CommissionMembre::create($validated);

        return response()->json($membre, 201);
    }

    public function show(CommissionMembre $commissionMembre)
    {
        return response()->json($commissionMembre);
    }

    public function update(Request $request, CommissionMembre $commissionMembre)
    {
        $validated = $request->validate(self::updateRules());
        $validated['nom_prenom'] = trim($validated['nom_prenom']);
        $validated['fonction'] = trim($validated['fonction']);

        $commissionMembre->update($validated);

        return response()->json($commissionMembre);
    }

    public function destroy(CommissionMembre $commissionMembre)
    {
        $commissionMembre->delete();

        return response()->json(null, 204);
    }
}
