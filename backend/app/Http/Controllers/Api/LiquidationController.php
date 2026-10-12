<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Marche;
use App\Models\Liquidation;
use App\Models\LiquidationPiece;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LiquidationController extends Controller
{
    /**
     * Get marches eligible for liquidation.
     */
    public function marches()
    {
        // Eligibility: At least ENGAGEMENT_VALIDE or later phases
        $marches = Marche::with(['fournisseur', 'liquidations'])->get();

        $marches = $marches->filter(function ($marche) {
            return in_array($marche->statut, [
                'engagement_validee',
                'liquidation_en_cours',
                'liquidation_validee',
                'ordonnancement_en_cours',
                'ordonnancement_validee',
                'cloture'
            ]);
        })->values();

        $data = $marches->map(function ($m) {
            $totalLiquide = $m->liquidations->whereIn('statut', ['VALIDÉE', 'TRANSMISE À L\'ORDONNANCEMENT', 'ORDONNANCÉE'])->sum('montant_ttc');
            return [
                'id' => $m->id,
                'num_marche' => $m->num_marche,
                'objet_marche' => $m->objet_marche,
                'titulaire' => $m->fournisseur->raison_sociale ?? $m->titulaire,
                'montant' => $m->montant,
                'total_liquide' => $totalLiquide,
                'reste_a_liquider' => round($m->montant - $totalLiquide, 2),
                'statut' => $m->statut,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function show($marcheId)
    {
        $marche = Marche::with(['aoo', 'lot.items', 'fournisseur', 'bordereauItems.lotItem', 'liquidations.pieces'])
            ->findOrFail($marcheId);

        $totalLiquide = $marche->liquidations->whereIn('statut', ['VALIDÉE', 'TRANSMISE À L\'ORDONNANCEMENT', 'ORDONNANCÉE'])->sum('montant_ttc');

        return response()->json([
            'marche' => array_merge($marche->toArray(), [
                'workflow' => $marche->workflow,
            ]),
            'liquidations' => $marche->liquidations,
            'finances' => [
                'montant_marche' => $marche->montant,
                'total_liquide' => $totalLiquide,
                'reste_a_liquider' => round($marche->montant - $totalLiquide, 2),
            ]
        ]);
    }

    public function store(Request $request, $marcheId)
    {
        $marche = Marche::findOrFail($marcheId);
        $data = $this->validateLiquidation($request);

        $totalLiquide = $marche->liquidations->whereIn('statut', ['VALIDÉE', 'TRANSMISE À L\'ORDONNANCEMENT', 'ORDONNANCÉE'])->sum('montant_ttc');
        $reste = round($marche->montant - $totalLiquide, 2);

        if ($data['montant_ttc'] > $reste + 0.02) {
            throw ValidationException::withMessages([
                'montant_ttc' => ['Le montant saisi dépasse le montant restant à liquider (' . $reste . ' DH).']
            ]);
        }

        $data['marche_id'] = $marche->id;
        $lignes = $data['lignes'] ?? [];
        unset($data['lignes']);
        
        $liquidation = Liquidation::create($data);

        foreach ($lignes as $ligne) {
            $liquidation->lignes()->create($ligne);
        }

        // Mettre à jour le statut du marché pour indiquer que la phase de liquidation a démarré
        if ($marche->statut === 'engagement_validee') {
            $marche->statut = 'liquidation_en_cours';
            $marche->save();
        }

        return response()->json(['message' => 'Liquidation créée avec succès', 'data' => $liquidation->load('lignes')], 201);
    }

    public function update(Request $request, $marcheId, $liquidationId)
    {
        $marche = Marche::findOrFail($marcheId);
        $liquidation = Liquidation::where('marche_id', $marcheId)->findOrFail($liquidationId);

        if (in_array($liquidation->statut, ['VALIDÉE', 'TRANSMISE À L\'ORDONNANCEMENT', 'ORDONNANCÉE'])) {
            return response()->json(['error' => 'Cette liquidation est validée et ne peut plus être modifiée.'], 403);
        }

        $data = $this->validateLiquidation($request);

        $totalLiquide = $marche->liquidations
            ->whereIn('statut', ['VALIDÉE', 'TRANSMISE À L\'ORDONNANCEMENT', 'ORDONNANCÉE'])
            ->where('id', '!=', $liquidation->id)
            ->sum('montant_ttc');
            
        $reste = round($marche->montant - $totalLiquide, 2);

        if ($data['montant_ttc'] > $reste + 0.02) {
            throw ValidationException::withMessages([
                'montant_ttc' => ['Le montant saisi dépasse le montant restant à liquider (' . $reste . ' DH).']
            ]);
        }

        $lignes = $data['lignes'] ?? [];
        unset($data['lignes']);
        
        $liquidation->update($data);

        $liquidation->lignes()->delete();
        foreach ($lignes as $ligne) {
            $liquidation->lignes()->create($ligne);
        }

        return response()->json(['message' => 'Liquidation mise à jour avec succès', 'data' => $liquidation->load('lignes')]);
    }

    public function updateStatus(Request $request, $marcheId, $liquidationId)
    {
        $request->validate([
            'statut' => 'required|string|in:BROUILLON,EN ATTENTE DE PIÈCES,À CONTRÔLER,VALIDÉE,TRANSMISE À L\'ORDONNANCEMENT,ORDONNANCÉE,REJETÉE',
            'motif_rejet' => 'nullable|string'
        ]);

        $liquidation = Liquidation::where('marche_id', $marcheId)->findOrFail($liquidationId);
        
        if ($request->statut === 'VALIDÉE') {
            $marche = Marche::findOrFail($marcheId);
            $totalLiquide = $marche->liquidations
                ->whereIn('statut', ['VALIDÉE', 'TRANSMISE À L\'ORDONNANCEMENT', 'ORDONNANCÉE'])
                ->where('id', '!=', $liquidation->id)
                ->sum('montant_ttc');
            $reste = round($marche->montant - $totalLiquide, 2);

            if ($liquidation->montant_ttc > $reste + 0.02) {
                return response()->json(['error' => 'Impossible de valider : le montant dépasse le reste à liquider.'], 422);
            }
        }

        $liquidation->statut = $request->statut;
        if ($request->statut === 'REJETÉE') {
            $liquidation->motif_rejet = $request->motif_rejet;
        }
        $liquidation->save();

        if ($request->statut === 'TRANSMISE À L\'ORDONNANCEMENT') {
            $marche = Marche::findOrFail($marcheId);
            if ($marche->statut === 'engagement_validee' || $marche->statut === 'liquidation_en_cours') {
                $marche->statut = 'ordonnancement_en_cours';
                $marche->save();
            }
        }

        return response()->json(['message' => 'Statut mis à jour', 'data' => $liquidation]);
    }

    public function uploadPiece(Request $request, $marcheId, $liquidationId)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'nom' => 'required|string',
            'type_document' => 'required|string'
        ]);

        $liquidation = Liquidation::where('marche_id', $marcheId)->findOrFail($liquidationId);

        $path = $request->file('file')->store('liquidations/' . $marcheId . '/' . $liquidationId);

        $piece = $liquidation->pieces()->create([
            'nom' => $request->nom,
            'type_document' => $request->type_document,
            'chemin_fichier' => $path,
            'statut_piece' => 'valide'
        ]);

        return response()->json(['message' => 'Pièce ajoutée', 'data' => $piece]);
    }
    
    public function downloadPiece($marcheId, $liquidationId, $pieceId)
    {
        $piece = LiquidationPiece::where('liquidation_id', $liquidationId)->findOrFail($pieceId);
        return Storage::download($piece->chemin_fichier, $piece->nom);
    }
    
    public function destroyPiece($marcheId, $liquidationId, $pieceId)
    {
        $piece = LiquidationPiece::where('liquidation_id', $liquidationId)->findOrFail($pieceId);
        Storage::delete($piece->chemin_fichier);
        $piece->delete();
        return response()->json(['message' => 'Pièce supprimée']);
    }

    private function validateLiquidation(Request $request)
    {
        $data = $request->validate([
            'num_liquidation' => 'nullable|string',
            'exercice_budgetaire' => 'nullable|string',
            'objet_liquidation' => 'nullable|string',
            'type_execution' => 'required|in:totale,partielle',
            'reference_service_fait' => 'nullable|string',
            'date_service_fait' => 'nullable|date',
            'date_debut_prestations' => 'nullable|date',
            'date_fin_prestations' => 'nullable|date',
            'pourcentage_execution' => 'nullable|numeric|min:0|max:100',
            'agent_responsable' => 'nullable|string',
            'fonction_agent' => 'nullable|string',
            'service_agent' => 'nullable|string',
            
            'type_reception' => 'nullable|string',
            'num_decision' => 'nullable|string',
            'date_decision' => 'nullable|date',
            'date_reunion_commission' => 'nullable|date',
            'heure_reunion_commission' => 'nullable|string',
            'reference_pv_reception' => 'nullable|string',
            'date_reception' => 'nullable|date',
            'commission_reception' => 'nullable|array',
            'commission_reception.*.nom' => 'nullable|string',
            'commission_reception.*.fonction' => 'nullable|string',
            'commission_reception.*.qualite' => 'nullable|string',
            'president_commission' => 'nullable|string',
            'membres_commission' => 'nullable|string',
            'resultat_reception' => 'nullable|string',
            'reserves_reception' => 'nullable|string',
            'date_levee_reserves' => 'nullable|date',
            
            'num_facture' => 'nullable|string',
            'date_facture' => 'nullable|date',
            'date_reception_facture' => 'nullable|date',
            'objet_facture' => 'nullable|string',
            'periode_facture' => 'nullable|string',
            'echeance_facture' => 'nullable|date',
            'reference_facture_fournisseur' => 'nullable|string',
            
            'num_decompte' => 'nullable|string',
            'date_decompte' => 'nullable|date',
            'type_decompte' => 'nullable|in:provisoire,definitif,situation',
            'periode_du' => 'nullable|date',
            'periode_au' => 'nullable|date',
            
            'montant_brut_ht' => 'nullable|numeric|min:0',
            'montant_brut_ttc' => 'nullable|numeric|min:0',
            'retenue_garantie' => 'nullable|numeric|min:0',
            'penalites_retard' => 'nullable|numeric|min:0',
            'avances_a_recuperer' => 'nullable|numeric|min:0',
            'autres_retenues' => 'nullable|numeric|min:0',
            'autres_deductions' => 'nullable|numeric|min:0',
            
            'montant_ht' => 'required|numeric|min:0',
            'taux_tva' => 'required|numeric|min:0',
            'montant_tva' => 'required|numeric|min:0',
            'montant_ttc' => 'required|numeric|min:0',
            'retenues' => 'nullable|numeric|min:0',
            'net_a_payer' => 'nullable|numeric|min:0',
            'observations' => 'nullable|string',
            
            'lignes' => 'nullable|array',
            'lignes.*.marche_bordereau_item_id' => 'nullable|integer|exists:marche_bordereau_items,id',
            'lignes.*.designation' => 'required_with:lignes|string',
            'lignes.*.unite' => 'nullable|string',
            'lignes.*.quantite_prevue' => 'nullable|numeric|min:0',
            'lignes.*.quantite_executee' => 'required_with:lignes|numeric|min:0',
            'lignes.*.prix_unitaire_ht' => 'required_with:lignes|numeric|min:0',
            'lignes.*.montant_ht' => 'required_with:lignes|numeric|min:0',
            'lignes.*.taux_tva' => 'nullable|numeric|min:0',
            'lignes.*.montant_tva' => 'nullable|numeric|min:0',
            'lignes.*.montant_ttc' => 'required_with:lignes|numeric|min:0',
            'lignes.*.observations' => 'nullable|string',
        ]);
        
        $data['retenues'] = ($data['retenue_garantie'] ?? 0) + ($data['penalites_retard'] ?? 0) + ($data['avances_a_recuperer'] ?? 0) + ($data['autres_retenues'] ?? 0) + ($data['autres_deductions'] ?? 0);
        $data['net_a_payer'] = $data['montant_ttc'] - $data['retenues'];
        
        return $data;
    }
}
