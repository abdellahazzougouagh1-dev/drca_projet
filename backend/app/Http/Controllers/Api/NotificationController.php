<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationLigne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::with('lignes')->orderBy('date_notification', 'desc')->get();
        return response()->json($notifications);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero' => 'required|string|unique:notifications,numero',
            'exercice' => 'required|integer',
            'date_notification' => 'required|date',
            'montant' => 'nullable|numeric|min:0',
            'objet' => 'nullable|string',
            'reference' => 'nullable|string',
            'observations' => 'nullable|string',
            'lignes' => 'required|array|min:1',
            'lignes.*.article' => 'required|string',
            'lignes.*.paragraphe' => 'required|string',
            'lignes.*.ligne_budgetaire' => 'required|string',
            'lignes.*.libelle' => 'required|string',
            'lignes.*.reports' => 'numeric',
            'lignes.*.diminution_report' => 'numeric',
            'lignes.*.credits_neufs' => 'numeric',
            'lignes.*.diminution_credit_neuf' => 'numeric',
            'lignes.*.credits_engagements' => 'numeric',
        ]);

        DB::beginTransaction();
        try {
            $notification = Notification::create([
                'numero' => $validated['numero'],
                'exercice' => $validated['exercice'],
                'date_notification' => $validated['date_notification'],
                'montant' => $validated['montant'] ?? null,
                'objet' => $validated['objet'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'observations' => $validated['observations'] ?? null,
            ]);

            foreach ($validated['lignes'] as $ligne) {
                $notification->lignes()->create($ligne);
            }

            DB::commit();
            return response()->json($notification->load('lignes'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de la création de la notification', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $notification = Notification::with(['lignes.consultations', 'lignes.marches'])->findOrFail($id);
        
        $notification->lignes->each(function($ligne) {
            $ligne->append(['credits_engages', 'credits_disponibles']);
        });

        return response()->json($notification);
    }

    public function getLignes(Request $request)
    {
        $exercice = $request->query('exercice');
        $query = NotificationLigne::with(['notification', 'marches.aoo', 'consultations.engagement', 'aoos']);
        
        if ($exercice) {
            $query->whereHas('notification', function($q) use ($exercice) {
                $q->where('exercice', $exercice);
            });
        }
        
        $lignes = $query->get();
        $lignes->each->append(['credits_engages', 'credits_disponibles']);
        
        return response()->json($lignes);
    }

    public function updateLigne(Request $request, $id)
    {
        $ligne = NotificationLigne::findOrFail($id);
        $ligne->update($request->all());
        return response()->json($ligne);
    }

    public function destroy($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();
        return response()->json(['message' => 'Notification supprimée avec succès']);
    }

    public function getDashboardStats(Request $request)
    {
        $exercice = $request->query('exercice');
        $query = NotificationLigne::with('notification');
        
        if ($exercice) {
            $query->whereHas('notification', function($q) use ($exercice) {
                $q->where('exercice', $exercice);
            });
        }
        
        $lignes = $query->get();
        
        $totalNotifie = $lignes->sum('total_credits');
        
        $totalEngage = 0;
        foreach ($lignes as $ligne) {
            $totalEngage += $ligne->credits_engages;
        }
        
        $totalDisponible = $totalNotifie - $totalEngage;

        // Approximations pour Liquidation et Ordonnancement
        // En l'absence de module budgétaire précis, on met à 0 pour éviter une erreur SQL
        $totalLiquide = 0;
        
        $tauxConsommation = $totalNotifie > 0 ? round(($totalEngage / $totalNotifie) * 100, 2) : 0;

        return response()->json([
            'total_notifie' => $totalNotifie,
            'total_engage' => $totalEngage,
            'total_disponible' => $totalDisponible,
            'total_liquide' => $totalLiquide, // Placeholder for liquidations
            'total_ordonnance' => 0, // Placeholder
            'taux_consommation' => $tauxConsommation
        ]);
    }
}
