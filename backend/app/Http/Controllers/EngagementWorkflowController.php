<?php

namespace App\Http\Controllers;

use App\Models\Engagement;
use App\Models\TypeEngagement;
use App\Models\EtapeWorkflow;
use App\Models\EtapeInstance;
use App\Models\HistoriqueStatut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EngagementWorkflowController extends Controller
{
    /**
     * Initialiser un engagement et démarrer son workflow.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type_engagement_code' => 'required|string|exists:type_engagements,code',
            'numero' => 'nullable|string',
            'objet' => 'nullable|string',
            'montant_global' => 'nullable|numeric',
            'data_specifique' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            $type = TypeEngagement::where('code', $request->type_engagement_code)->firstOrFail();

            // 1. Création de l'engagement
            $engagement = Engagement::create([
                'type_engagement_id' => $type->id,
                'numero' => $request->numero,
                'objet' => $request->objet,
                'montant_global' => $request->montant_global,
                'statut_actuel' => 'en_cours',
                'data_specifique' => $request->data_specifique ?? [],
            ]);

            // 2. Trouver la première étape du workflow pour ce type
            $premiereEtape = EtapeWorkflow::where('type_engagement_id', $type->id)
                                          ->orderBy('ordre', 'asc')
                                          ->first();

            if ($premiereEtape) {
                // 3. Créer l'instance de l'étape
                $etapeInstance = EtapeInstance::create([
                    'engagement_id' => $engagement->id,
                    'etape_workflow_id' => $premiereEtape->id,
                    'statut' => 'en_cours',
                ]);

                // 4. Historiser le statut
                HistoriqueStatut::create([
                    'engagement_id' => $engagement->id,
                    'statut_precedent' => null,
                    'statut_suivant' => 'creation_dossier',
                    'commentaire' => 'Initialisation du dossier - ' . $premiereEtape->libelle,
                    'user_id' => $request->user() ? $request->user()->id : null,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Engagement initialisé avec succès',
                'engagement' => $engagement->load('etapeInstances.etapeWorkflow')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de l\'initialisation de l\'engagement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer la timeline d'un dossier.
     */
    public function getTimeline($id)
    {
        $engagement = Engagement::findOrFail($id);

        // Récupération du schéma théorique du workflow avec les modèles de documents
        $etapesTheoriques = EtapeWorkflow::where('type_engagement_id', $engagement->type_engagement_id)
            ->with('documentModeles')
            ->orderBy('ordre', 'asc')
            ->get();

        // Récupération des instances d'étapes déjà créées pour cet engagement
        $instancesReelles = EtapeInstance::where('engagement_id', $engagement->id)->get()->keyBy('etape_workflow_id');

        $timeline = $etapesTheoriques->map(function ($etape) use ($instancesReelles) {
            $instance = $instancesReelles->get($etape->id);
            
            return [
                'id_theorique' => $etape->id,
                'id_instance' => $instance ? $instance->id : null,
                'ordre' => $etape->ordre,
                'code' => $etape->code,
                'libelle' => $etape->libelle,
                'est_bloquante' => $etape->est_bloquante,
                'statut' => $instance ? $instance->statut : 'non_commence',
                'date_realisation' => $instance ? $instance->date_realisation : null,
                'documents' => $etape->documentModeles->map(function ($doc) {
                    return [
                        'code' => $doc->code,
                        'libelle' => $doc->libelle,
                        'chemin_blade' => $doc->chemin_blade,
                    ];
                }),
            ];
        });

        return response()->json([
            'engagement' => [
                'id' => $engagement->id,
                'numero' => $engagement->numero,
                'objet' => $engagement->objet,
                'statut_actuel' => $engagement->statut_actuel,
            ],
            'timeline' => $timeline
        ]);
    }
    /**
     * Faire transiter un dossier à l'étape suivante du workflow.
     */
    public function transitionToNextStep(Request $request)
    {
        $request->validate([
            'engagement_id' => 'required|exists:engagements,id',
            'statut' => 'required|in:valide,bloque',
            'commentaire' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $engagement = Engagement::findOrFail($request->engagement_id);
            $statutGlobalPrecedent = $engagement->statut_actuel;

            // 1. Trouver l'instance de l'étape actuellement en cours
            $etapeActuelleInstance = EtapeInstance::where('engagement_id', $engagement->id)
                ->where('statut', 'en_cours')
                ->with('etapeWorkflow')
                ->first();

            if (!$etapeActuelleInstance) {
                return response()->json(['message' => 'Aucune étape en cours trouvée pour ce dossier.'], 400);
            }

            // 2. Mettre à jour l'étape actuelle
            $etapeActuelleInstance->update([
                'statut' => $request->statut,
                'date_realisation' => now(),
            ]);

            $etapeWorkflowActuelle = $etapeActuelleInstance->etapeWorkflow;
            $nouveauStatutGlobal = $statutGlobalPrecedent;
            $message = 'Étape ' . $etapeWorkflowActuelle->libelle . ' marquée comme ' . $request->statut;

            // 3. Logique d'avancement
            if ($request->statut === 'valide') {
                $etapeSuivante = EtapeWorkflow::where('type_engagement_id', $engagement->type_engagement_id)
                    ->where('ordre', '>', $etapeWorkflowActuelle->ordre)
                    ->orderBy('ordre', 'asc')
                    ->first();

                if ($etapeSuivante) {
                    // Créer la nouvelle étape
                    EtapeInstance::create([
                        'engagement_id' => $engagement->id,
                        'etape_workflow_id' => $etapeSuivante->id,
                        'statut' => 'en_cours',
                    ]);
                    
                    $nouveauStatutGlobal = 'en_cours'; 
                    $engagement->update(['statut_actuel' => $nouveauStatutGlobal]);
                    $message .= '. Passage à l\'étape : ' . $etapeSuivante->libelle;
                } else {
                    // Fin du workflow
                    $nouveauStatutGlobal = 'termine';
                    $engagement->update(['statut_actuel' => $nouveauStatutGlobal]);
                    $message .= '. Fin du workflow, dossier clôturé.';
                }
            } else {
                // Si bloqué, on met à jour le statut global du dossier
                $nouveauStatutGlobal = 'bloque';
                $engagement->update(['statut_actuel' => $nouveauStatutGlobal]);
            }

            // 4. Historiser
            HistoriqueStatut::create([
                'engagement_id' => $engagement->id,
                'statut_precedent' => $statutGlobalPrecedent,
                'statut_suivant' => $nouveauStatutGlobal,
                'commentaire' => $request->commentaire ?? $message,
                'user_id' => $request->user() ? $request->user()->id : null,
            ]);

            DB::commit();

            return response()->json([
                'message' => $message,
                'engagement' => $engagement->fresh()->load('etapeInstances.etapeWorkflow')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la transition d\'étape.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
