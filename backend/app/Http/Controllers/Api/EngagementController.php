<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BudgetHierarchyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EngagementController extends Controller
{
    public function getEngagementData($consultationId)
    {
        $consultation = \App\Models\Consultation::findOrFail($consultationId);
        
        $offreRetenue = $consultation->offres()
            ->with('fournisseur')
            ->where('retenu', true)
            ->first();

        if (!$offreRetenue) {
            return response()->json([
                'message' => 'Aucun prestataire retenu pour cette consultation.'
            ], 404);
        }

        $ligneBudgetaire = $consultation->notificationLigne;
        $creditDisponible = $ligneBudgetaire ? $ligneBudgetaire->credits_disponibles : null;

        return response()->json([
            'consultation' => $consultation,
            'fournisseur' => $offreRetenue->fournisseur,
            'montant_valide' => $offreRetenue->montant_apres_verification ?? $offreRetenue->montant_propose,
            'type_engagement' => in_array($consultation->mode_engagement, ['BC', 'Bon de commande'], true) ? 'BC' : 'Convention',
            'ligne_budgetaire' => $ligneBudgetaire,
            'credit_disponible' => $creditDisponible,
        ]);
    }

    public function store(Request $request, $consultationId)
    {
        $consultation = \App\Models\Consultation::findOrFail($consultationId);

        $validated = $request->validate([
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'montant_engagement' => 'required|numeric|min:0',
            'type_engagement' => 'required|in:BC,Convention',
            'date_engagement' => 'required|date',
            'delai_execution' => 'required|integer|min:1',
            'date_notification' => 'nullable|date',
            'date_commencement' => 'nullable|date',
        ]);

        $expectedType = in_array($consultation->mode_engagement, ['BC', 'Bon de commande'], true)
            ? 'BC'
            : (in_array($consultation->mode_engagement, ['Convention', 'Contrat'], true) ? 'Convention' : null);

        if ($expectedType === null || $validated['type_engagement'] !== $expectedType) {
            throw ValidationException::withMessages([
                'type_engagement' => ['Le type d’engagement ne correspond pas au mode de la consultation.'],
            ]);
        }

        $offreRetenue = $consultation->offres()
            ->where('fournisseur_id', $validated['fournisseur_id'])
            ->where('retenu', true)
            ->first();

        if (!$offreRetenue) {
            throw ValidationException::withMessages([
                'fournisseur_id' => ['Le fournisseur doit être celui retenu pour cette consultation.'],
            ]);
        }

        $hasExisting = $consultation->engagement()->exists() || $consultation->registreEngagement()->exists();
        if ($consultation->engagement()->exists()) {
            throw ValidationException::withMessages([
                'consultation' => ['Cette consultation possède déjà un engagement.'],
            ]);
        }

        try {
            DB::beginTransaction();

            $consultation->load(['budget', 'notificationLigne.notification', 'registreEngagement']);
            $exercice = $consultation->budget?->exercice_budgetaire
                ?: ($consultation->annee ?: ($consultation->notificationLigne?->notification?->exercice ?: date('Y')));
            $domaine = $consultation->type_budget ?: 'Investissement';

            $resolvedLigne = BudgetHierarchyService::resolveNotificationLigneForDossier(
                $consultation->notification_ligne_id,
                $consultation->budget?->art,
                $consultation->budget?->par,
                $consultation->budget?->lig,
                $domaine,
                $exercice
            );

            if ($resolvedLigne && (int) $consultation->notification_ligne_id !== (int) $resolvedLigne->id) {
                $consultation->notification_ligne_id = $resolvedLigne->id;
                $consultation->save();
            }

            BudgetHierarchyService::assertEngagementWithinNotification([
                'exercice' => $exercice,
                'domaine' => $domaine,
                'art' => $consultation->budget?->art,
                'par' => $consultation->budget?->par,
                'lig' => $consultation->budget?->lig,
                'notification_ligne_id' => $consultation->notification_ligne_id,
                'proposed_engage_neuf' => (float) $validated['montant_engagement'],
                'credit_ouvert_cp' => $consultation->credit_ouvert_cp,
                'depenses_anterieures_cp' => $consultation->depenses_anterieures_cp,
                'existing_active_ordonnanced' => BudgetHierarchyService::getActiveOrdonnancedForConsultation($consultation->id),
                'exclude' => ['exclude_consultation_id' => $consultation->id],
                'error_field' => 'montant_engagement',
            ]);

            $numero_engagement = 'ENG-' . date('Y') . '-' . $consultation->id;

            $engagement = \App\Models\Engagement::create(array_merge($validated, [
                'consultation_id' => $consultation->id,
                'numero' => $numero_engagement,
                'montant_global' => $validated['montant_engagement'],
            ]));

            $consultation->update([
                'statut_dossier' => 'ENGAGÉ',
                'montant_engager_neuf' => $validated['montant_engagement'],
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Engagement enregistré avec succès.',
                'engagement' => $engagement
            ]);
            
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Une erreur est survenue lors de la création de l\'engagement.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
