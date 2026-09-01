<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConcurrentLotDecision;
use App\Models\Fournisseur;
use App\Models\LotItem;
use App\Models\Marche;
use App\Models\MarcheBordereauItem;
use App\Services\MarcheDocumentBuilder;
use App\Services\RapportPresentationBuilder;
use App\Services\MarcheArchiveBuilder;
use App\Services\MarcheWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException; 

class MarcheController extends Controller
{
    public function index()
    {
        return response()->json(
            Marche::with(['aoo', 'lot', 'fournisseur'])
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $request->merge([
            'lot_id' => $request->input('lot_id') ?: null,
            'fournisseur_id' => $request->input('fournisseur_id') ?: null,
            'date_signature' => $request->input('date_signature') ?: null,
            'date_approbation' => $request->input('date_approbation') ?: null,
            'date_notification_marche' => $request->input('date_notification_marche') ?: null,
            'os_numero' => $request->input('os_numero') ?: null,
            'os_date_signature' => $request->input('os_date_signature') ?: null,
            'os_date_effet' => $request->input('os_date_effet') ?: null,
            'os_arret_numero' => $request->input('os_arret_numero') ?: null,
            'os_arret_date_signature' => $request->input('os_arret_date_signature') ?: null,
            'os_arret_date_effet' => $request->input('os_arret_date_effet') ?: null,
            'os_arret_motif' => $request->input('os_arret_motif') ?: null,
            'os_reprise_numero' => $request->input('os_reprise_numero') ?: null,
            'os_reprise_date_signature' => $request->input('os_reprise_date_signature') ?: null,
            'os_reprise_date_effet' => $request->input('os_reprise_date_effet') ?: null,
            'date_reception_finale' => $request->input('date_reception_finale') ?: null,
            'objet_marche' => $request->input('objet_marche') ?: null,
            'qualite_gerant' => $request->input('qualite_gerant') ?: null,
            'exercice' => $request->input('exercice') ?: null,
            'type_budget' => $request->input('type_budget') ?: null,
            'code_budget' => $request->input('code_budget') ?: null,
            'intitule_budget' => $request->input('intitule_budget') ?: null,
            'agent_suivi' => $request->input('agent_suivi') ?: null,
            'num_engagement' => $request->input('num_engagement') ?: null,
            'reference_engagement' => $request->input('reference_engagement') ?: null,
            'forme_engagement' => $request->input('forme_engagement') ?: null,
            'date_engagement' => $request->input('date_engagement') ?: null,
            'article_budget' => $request->input('article_budget') ?: null,
            'paragraphe_budget' => $request->input('paragraphe_budget') ?: null,
            'ligne_budget' => $request->input('ligne_budget') ?: null,
            'credit_budget_cp' => $request->filled('credit_budget_cp') ? $request->input('credit_budget_cp') : null,
            'credit_budget_ce' => $request->filled('credit_budget_ce') ? $request->input('credit_budget_ce') : null,
            'depenses_engagees_cp' => $request->filled('depenses_engagees_cp') ? $request->input('depenses_engagees_cp') : null,
            'depenses_engagees_ce' => $request->filled('depenses_engagees_ce') ? $request->input('depenses_engagees_ce') : null,
            'disponible_cp' => $request->filled('disponible_cp') ? $request->input('disponible_cp') : null,
            'disponible_ce' => $request->filled('disponible_ce') ? $request->input('disponible_ce') : null,
            'engagement_propose_cp' => $request->filled('engagement_propose_cp') ? $request->input('engagement_propose_cp') : null,
            'engagement_propose_ce' => $request->filled('engagement_propose_ce') ? $request->input('engagement_propose_ce') : null,
            'pieces_jointes' => $request->input('pieces_jointes') ?: null,
            'lot' => is_array($request->input('lot'))
                ? ($request->input('lot')['num_lot'] ?? null)
                : $request->input('lot'),
        ]);

        if ($request->filled('aoo_id')) {
            $aoo = \App\Models\Aoo::find($request->input('aoo_id'));
            if ($aoo && $aoo->notification_ligne_id) {
                $request->merge([
                    'notification_ligne_id' => $aoo->notification_ligne_id
                ]);
            }
        }

        $data = $request->validate([
            'id' => 'nullable|exists:marches,id',
            'num_marche' => 'required|string|max:255|unique:marches,num_marche,' . $request->input('id'),
            'aoo_id' => 'required|exists:aoos,id',
            'notification_ligne_id' => 'nullable|exists:notification_lignes,id',
            'lot_id' => 'nullable|exists:lots,id',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
            'lot' => 'nullable|string|max:255',
            'titulaire' => 'required|string|max:255',
            'objet_marche' => 'nullable|string',
            'qualite_gerant' => 'nullable|string|max:255',
            'exercice' => 'nullable|string|max:4',
            'type_budget' => 'nullable|string|max:255',
            'code_budget' => 'nullable|string|max:255',
            'intitule_budget' => 'nullable|string',
            'montant' => 'required|numeric|min:0',
            'date_signature' => 'nullable|date',
            'date_approbation' => 'nullable|date',
            'date_notification_marche' => 'nullable|date',
            'os_numero' => 'nullable|string|max:255',
            'os_date_signature' => 'nullable|date',
            'os_date_effet' => 'nullable|date',
            'os_arret_numero' => 'nullable|string|max:255',
            'os_arret_date_signature' => 'nullable|date',
            'os_arret_date_effet' => 'nullable|date',
            'os_arret_motif' => 'nullable|string|max:255',
            'os_reprise_numero' => 'nullable|string|max:255',
            'os_reprise_date_signature' => 'nullable|date',
            'os_reprise_date_effet' => 'nullable|date',
            'num_decision' => 'nullable|string|max:255',
            'date_decision' => 'nullable|date',
            'date_reunion_commission' => 'nullable|date',
            'heure_reunion_commission' => 'nullable',
            'lieu_reunion_commission' => 'nullable|string|max:255',
            'statut' => 'nullable|string|max:255',
            'agent_suivi' => 'nullable|string|max:255',
            'num_engagement' => 'nullable|string|max:255',
            'reference_engagement' => 'nullable|string|max:255',
            'forme_engagement' => 'nullable|string|max:255',
            'date_engagement' => 'nullable|date',
            'article_budget' => 'nullable|string|max:255',
            'paragraphe_budget' => 'nullable|string|max:255',
            'ligne_budget' => 'nullable|string|max:255',
            'credit_budget_cp' => 'nullable|numeric',
            'credit_budget_ce' => 'nullable|numeric',
            'depenses_engagees_cp' => 'nullable|numeric',
            'depenses_engagees_ce' => 'nullable|numeric',
            'disponible_cp' => 'nullable|numeric',
            'disponible_ce' => 'nullable|numeric',
            'engagement_propose_cp' => 'nullable|numeric',
            'engagement_propose_ce' => 'nullable|numeric',
            'pieces_jointes' => 'nullable|string',
            'date_reception_finale' => 'nullable|date',
            'commission_reception' => 'nullable|array',
            'bordereau_items' => 'nullable|array',
            'bordereau_items.*.lot_item_id' => 'required_with:bordereau_items|exists:lot_items,id',
            'bordereau_items.*.prix_unitaire_ht' => 'required_with:bordereau_items|numeric|min:0',
            'delai_execution' => 'nullable|numeric|min:0',
            'taux_tva' => 'nullable|numeric|min:0',
            'date_debut_prevue' => 'nullable|date',
            'date_fin_prevue' => 'nullable|date',
            'observations' => 'nullable|string',
            'fournisseur_data' => 'nullable|array',
        ]);

        $bordereauItems = $request->input('bordereau_items', []);

        if (!empty($data['fournisseur_id']) && empty($data['titulaire'])) {
            $data['titulaire'] = Fournisseur::find($data['fournisseur_id'])?->raison_sociale;
        }

        if (!empty($data['fournisseur_id']) && empty($data['qualite_gerant'])) {
            $data['qualite_gerant'] = Fournisseur::find($data['fournisseur_id'])?->qualite_representant ?: 'Gérant';
        }

        if (empty($data['qualite_gerant'])) {
            $data['qualite_gerant'] = 'Gérant';
        }

        if (empty($data['type_budget'])) {
            $data['type_budget'] = 'Investissement';
        }

        if (empty($data['exercice']) && !empty($data['num_marche'])) {
            if (preg_match('/\/(\d{4})\//', $data['num_marche'], $matches)) {
                $data['exercice'] = $matches[1];
            }
        }

        if (empty($data['exercice'])) {
            $data['exercice'] = date('Y');
        }

        if (empty($data['forme_engagement'])) {
            $data['forme_engagement'] = 'Marché';
        }

        if (!empty($data['lot_id']) && empty($data['montant'])) {
            $decision = ConcurrentLotDecision::where('aoo_id', $data['aoo_id'])
                ->where('lot_id', $data['lot_id'])
                ->where('fournisseur_id', $data['fournisseur_id'])
                ->where('statut', 'Retenu')
                ->first();
            $data['montant'] = $decision?->montant_propose ?? 0;
        }

        if (!empty($data['id'])) {
            $marche = Marche::findOrFail($data['id']);
            $marche->update($data);
        } else {
            $marche = Marche::create($data);
        }

        if ($marche->fournisseur_id && $request->filled('fournisseur_data')) {
            $fournisseur = Fournisseur::find($marche->fournisseur_id);
            if ($fournisseur) {
                $fData = $request->input('fournisseur_data', []);
                $allowed = [
                    'raison_sociale', 'ice', 'if', 'rc', 'forme_juridique', 'capital',
                    'patente', 'cnss', 'adresse', 'ville', 'telephone', 'fax',
                    'email', 'representant', 'banque', 'agence_bancaire', 'rib'
                ];
                $updateData = [];
                foreach ($allowed as $fKey) {
                    if (array_key_exists($fKey, $fData)) {
                        $updateData[$fKey] = $fData[$fKey];
                    }
                }
                if (!empty($updateData)) {
                    $fournisseur->update($updateData);
                }
            }
        }

        if (!empty($data['lot_id']) && !empty($bordereauItems)) {
            $this->syncBordereauItems($marche, $bordereauItems, (int) $data['lot_id']);
        }

        return response()->json([
            'message' => 'Dossier Marche enregistre avec succes',
            'data' => $marche->load(['aoo.notificationLigne', 'lot', 'fournisseur', 'bordereauItems.lotItem', 'notificationLigne']),
        ], 200);
    }

    public function show($id)
    {
        $marche = Marche::with(['aoo.lots.items', 'aoo.notificationLigne', 'lot.items', 'fournisseur', 'bordereauItems.lotItem', 'notificationLigne'])
            ->findOrFail($id);

        return response()->json(array_merge($marche->toArray(), [
            'workflow' => $marche->workflow,
        ]));
    }

    public function update(Request $request, $id)
    {
        $request->merge(['id' => $id]);
        return $this->store($request);
    }

    public function destroy($id)
    {
        Marche::findOrFail($id)->delete();
        return response()->json(['message' => 'Marche supprime'], 200);
    }

    public function workflowState($id)
    {
        $marche = Marche::findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $marche->id,
                'statut' => $marche->statut,
                'workflow' => $marche->workflow,
            ],
        ]);
    }

    public function transitionPhase(Request $request, $id)
    {
        $marche = Marche::findOrFail($id);
        $phase = $request->input('phase');
        $validated = (bool) $request->input('validated', false);

        $allowedPhases = ['consultation', 'engagement', 'liquidation', 'ordonnancement'];
        if (!in_array($phase, $allowedPhases, true)) {
            return response()->json(['error' => 'Phase non valide'], 422);
        }

        $marche->statut = MarcheWorkflowService::resolveStatus($phase, $validated);
        $marche->save();

        return response()->json([
            'message' => 'Phase mise à jour',
            'data' => [
                'statut' => $marche->statut,
                'workflow' => $marche->workflow,
            ],
        ]);
    }

    public function uploadCps(Request $request, $id)
    {
        $request->validate([
            'fichier_cps' => 'required|file|mimes:docx,doc,pdf|max:10240',
        ]);

        $marche = Marche::findOrFail($id);
        
        $file = $request->file('fichier_cps');
        $filename = 'cps_' . $marche->id . '_' . time() . '.' . $file->getClientOriginalExtension();
        
        $path = $file->storeAs('documents/marches/' . $marche->id . '/cps', $filename);
        
        $marche->chemin_cps = $path;
        $marche->save();

        return response()->json([
            'message' => 'Fichier CPS importé avec succès',
            'path' => $path
        ]);
    }

    public function downloadDocument($id, $documentType)
    {
        $validDocuments = [
            'page-garde',
            'premiere-derniere-page',
            'bordereau-prix',
            'contrat-marche',
            'rapport-presentation-marche',
            'premiere-page-marche',
            'derniere-page-marche',
            'os-approbation',
            'os-commencement',
            'designation-agent-suivi',
            'os-arret',
            'os-reprise',
            'pv-reception-provisoire',
            'pv-reception-definitive',
            'attestation-bonne-execution',
            'decision-nomination',
        ];

        if (!in_array($documentType, $validDocuments, true)) {
            return response()->json(['error' => 'Document non reconnu'], 404);
        }

        if ($documentType === 'rapport-presentation-marche') {
            return $this->downloadRapportPresentationPdf((int) $id);
        }

        $marche = Marche::with(['aoo', 'fournisseur'])->findOrFail($id);

        if ($documentType === 'page-garde') {
            return $this->downloadPageGardePdf($marche);
        }

        if ($documentType === 'premiere-derniere-page') {
            return $this->downloadPremiereDernierePagePdf($marche);
        }

        if ($documentType === 'bordereau-prix') {
            return $this->downloadBordereauPrixPdf($marche);
        }

        if ($documentType === 'contrat-marche') {
            return $this->downloadContratMarchePdf($marche);
        }

        return $this->downloadStandardDocumentPdf($marche, $documentType);
    }

    public function exportPdf($id)
    {
        return $this->downloadPageGardePdf(
            Marche::with(['aoo', 'fournisseur'])->findOrFail($id)
        );
    }

    /**
     * Download a ZIP archive containing the marche documents (robust to missing files).
     */
    public function downloadArchive($id)
    {
        $marche = Marche::with(['aoo', 'fournisseur', 'bordereauItems.lotItem'])->findOrFail($id);

        try {
            $zipPath = MarcheArchiveBuilder::build($marche);

            if (!file_exists($zipPath)) {
                return response()->json(['error' => 'Archive introuvable'], 404);
            }

            return response()->download($zipPath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la génération de l\'archive marche: ' . $e->getMessage());
            return response()->json(['error' => 'Impossible de générer l\'archive'], 500);
        }
    }

    private function downloadRapportPresentationPdf(int $id): \Symfony\Component\HttpFoundation\Response
    {
        $marche = Marche::query()->findOrFail($id);
        $doc = RapportPresentationBuilder::build($marche);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.marche.rapport_presentation', [
            'marche' => $marche,
            'doc' => $doc,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Rapport_Presentation_Marche_' . str_replace('/', '_', $marche->num_marche) . '.pdf');
    }

    private function downloadPageGardePdf(Marche $marche): \Symfony\Component\HttpFoundation\Response
    {
        $doc = MarcheDocumentBuilder::pageGarde($marche);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.marche.page_garde', [
            'marche' => $marche,
            'doc' => $doc,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Page_Garde_Marche_' . str_replace('/', '_', $marche->num_marche) . '.pdf');
    }

    private function downloadPremiereDernierePagePdf(Marche $marche): \Symfony\Component\HttpFoundation\Response
    {
        $doc = MarcheDocumentBuilder::presentation($marche);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.marche.premiere_derniere_page', [
            'marche' => $marche,
            'doc' => $doc,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Premiere_Derniere_Page_Marche_' . str_replace('/', '_', $marche->num_marche) . '.pdf');
    }

    private function downloadBordereauPrixPdf(Marche $marche): \Symfony\Component\HttpFoundation\Response
    {
        $marche->loadMissing(['bordereauItems.lotItem', 'aoo', 'fournisseur', 'lot']);

        if ($marche->bordereauItems->isEmpty()) {
            abort(422, 'Aucun bordereau des prix enregistré pour ce marché.');
        }

        $doc = MarcheDocumentBuilder::bordereau($marche);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.marche.bordereau_prix', [
            'marche' => $marche,
            'doc' => $doc,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Bordereau_Prix_Marche_' . str_replace('/', '_', $marche->num_marche) . '.pdf');
    }

    private function downloadContratMarchePdf(Marche $marche): \Symfony\Component\HttpFoundation\Response
    {
        $marche->loadMissing(['bordereauItems.lotItem', 'aoo', 'fournisseur', 'lot']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.marche.contrat_marche', [
            'marche' => $marche,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Marche_Final_' . str_replace('/', '_', $marche->num_marche) . '.pdf');
    }

    private function validateBordereauTotals(array $bordereauItems, float $montantAttribution, int $lotId): void
    {
        $lotItemIds = LotItem::where('lot_id', $lotId)->pluck('id')->all();
        $submittedIds = collect($bordereauItems)->pluck('lot_item_id')->map(fn ($id) => (int) $id)->all();

        if (count(array_diff($lotItemIds, $submittedIds)) > 0) {
            throw ValidationException::withMessages([
                'bordereau_items' => ['Tous les articles du lot doivent avoir un prix unitaire saisi.'],
            ]);
        }

        $totalTtc = 0.0;
        foreach ($bordereauItems as $item) {
            $lotItem = LotItem::find($item['lot_item_id']);
            if (!$lotItem || (int) $lotItem->lot_id !== $lotId) {
                throw ValidationException::withMessages([
                    'bordereau_items' => ['Un article du bordereau n\'appartient pas au lot sélectionné.'],
                ]);
            }

            $puHt = (float) ($item['prix_unitaire_ht'] ?? 0);
            $tauxTva = (float) ($item['taux_tva'] ?? 20);
            $montantHt = round((float) $lotItem->quantite * $puHt, 2);
            $montantTtc = round($montantHt * (1 + $tauxTva / 100), 2);
            
            $totalTtc += $montantTtc;
        }

        $totalTtc = round($totalTtc, 2);

        if (abs($totalTtc - round($montantAttribution, 2)) > 0.02) {
            throw ValidationException::withMessages([
                'bordereau_items' => [
                    'Le Total TTC du bordereau (' . number_format($totalTtc, 2, ',', ' ') . ' MAD) doit être égal au montant d\'attribution du lot (' . number_format($montantAttribution, 2, ',', ' ') . ' MAD).',
                ],
            ]);
        }
    }

    private function syncBordereauItems(Marche $marche, array $bordereauItems, int $lotId): void
    {
        $receivedIds = [];

        foreach ($bordereauItems as $itemData) {
            $lotItem = LotItem::find($itemData['lot_item_id']);
            if (!$lotItem || (int) $lotItem->lot_id !== $lotId) {
                continue;
            }

            $puHt = (float) ($itemData['prix_unitaire_ht'] ?? 0);
            $tauxTva = (float) ($itemData['taux_tva'] ?? 20);
            $montantHt = round((float) $lotItem->quantite * $puHt, 2);

            $row = MarcheBordereauItem::updateOrCreate(
                [
                    'marche_id' => $marche->id,
                    'lot_item_id' => $lotItem->id,
                ],
                [
                    'prix_unitaire_attributaire' => $puHt,
                    'taux_tva' => $tauxTva,
                    'montant_ht' => $montantHt,
                ]
            );

            $receivedIds[] = $row->id;
        }

        $marche->bordereauItems()->whereNotIn('id', $receivedIds)->delete();
    }

    public function genererOsCommencement(Request $request, $id)
    {
        $marche = Marche::with(['aoo', 'fournisseur'])->findOrFail($id);

        // Validation des champs requis pour l'OS (accepte les données de la requête ou de la base)
        $osNumero = $request->input('os_numero') ?? $marche->os_numero;
        $osDateSignature = $request->input('os_date_signature') ?? $marche->os_date_signature;
        $osDateEffet = $request->input('os_date_effet') ?? $marche->os_date_effet;
        $dateNotificationMarche = $request->input('date_notification_marche') ?? $marche->date_notification_marche;

        if (empty($osNumero)) {
            return response()->json(['error' => 'Le numéro de l\'Ordre de Service est requis'], 422);
        }
        if (empty($osDateSignature)) {
            return response()->json(['error' => 'La date de signature de l\'OS est requise'], 422);
        }
        if (empty($osDateEffet)) {
            return response()->json(['error' => 'La date d\'effet est requise'], 422);
        }
        if (empty($dateNotificationMarche)) {
            return response()->json(['error' => 'La date de notification du marché est requise'], 422);
        }

        // Récupération des données du fournisseur
        $fournisseur = $marche->fournisseur;
        $gerantNom = $fournisseur ? $fournisseur->representant : '';
        $gerantQualite = $fournisseur ? ($fournisseur->qualite_representant ?: 'Gérant') : 'Gérant';
        $societe = $fournisseur ? $fournisseur->raison_sociale : $marche->titulaire;
        $adresse = $fournisseur ? $fournisseur->adresse : '';

        // Formatage des dates
        $dateSignatureFr = \Carbon\Carbon::parse($osDateSignature)->locale('fr')->translatedFormat('d F Y');
        $dateEffetFr = \Carbon\Carbon::parse($osDateEffet)->locale('fr')->translatedFormat('d F Y');
        $dateNotificationFr = \Carbon\Carbon::parse($dateNotificationMarche)->locale('fr')->translatedFormat('d F Y');

        // Génération du PDF avec DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.marche.os_commencement', [
            'marche' => $marche,
            'societe' => $societe,
            'gerantNom' => $gerantNom,
            'gerantQualite' => $gerantQualite,
            'adresse' => $adresse,
            'dateSignatureFr' => $dateSignatureFr,
            'dateEffetFr' => $dateEffetFr,
            'dateNotificationFr' => $dateNotificationFr,
        ])->setPaper('a4', 'portrait');

        $fileName = 'OS_Commencement_' . str_replace(['/', '\\'], '_', $marche->num_marche) . '.pdf';

        $this->storeGeneratedPdf($marche, 'os-commencement', $pdf->output());

        return $pdf->download($fileName);
    }

    public function genererDecisionNomination(Request $request, $id)
    {
        $marche = Marche::with(['aoo', 'fournisseur'])->findOrFail($id);

        // Validation des champs requis pour la décision
        $numDecision = $request->input('num_decision') ?? $marche->num_decision;
        $dateDecision = $request->input('date_decision') ?? $marche->date_decision;
        $dateReunionCommission = $request->input('date_reunion_commission') ?? $marche->date_reunion_commission;
        $heureReunionCommission = $request->input('heure_reunion_commission') ?? $marche->heure_reunion_commission;
        $lieuReunionCommission = $request->input('lieu_reunion_commission') ?? $marche->lieu_reunion_commission;
        $membresCommissionIds = $request->input('membres_commission_ids') ?? [];

        if (empty($numDecision)) {
            return response()->json(['error' => 'Le numéro de la décision est requis'], 422);
        }
        if (empty($dateDecision)) {
            return response()->json(['error' => 'La date de la décision est requise'], 422);
        }
        if (empty($dateReunionCommission)) {
            return response()->json(['error' => 'La date de réunion de la commission est requise'], 422);
        }
        if (empty($heureReunionCommission)) {
            return response()->json(['error' => 'L\'heure de réunion de la commission est requise'], 422);
        }
        if (empty($membresCommissionIds) || !is_array($membresCommissionIds) || count($membresCommissionIds) === 0) {
            return response()->json(['error' => 'Les membres de la commission sont requis'], 422);
        }

        // Récupération des membres de la commission depuis la table commission_membres
        $membresCommission = \App\Models\CommissionMembre::whereIn('id', $membresCommissionIds)->get()->map(function ($membre) {
            return [
                'nom' => $membre->nom_prenom,
                'fonction' => $membre->fonction,
                'qualite' => 'Membre' // Par défaut, peut être personnalisé si nécessaire
            ];
        })->toArray();

        // Formatage des dates
        $dateDecisionFr = \Carbon\Carbon::parse($dateDecision)->locale('fr')->translatedFormat('d F Y');
        $dateReunionFr = \Carbon\Carbon::parse($dateReunionCommission)->locale('fr')->translatedFormat('d F Y');
        $heureReunion = $heureReunionCommission;
        $lieuReunion = $lieuReunionCommission ?: 'au siège de la DRCA-RSK';

        // Génération du PDF avec DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.marche.decision_nomination', [
            'marche' => $marche,
            'numDecision' => $numDecision,
            'dateDecisionFr' => $dateDecisionFr,
            'dateReunionFr' => $dateReunionFr,
            'heureReunion' => $heureReunion,
            'lieuReunion' => $lieuReunion,
            'membresCommission' => $membresCommission,
        ])->setPaper('a4', 'portrait');

        $fileName = 'Decision_Nomination_' . str_replace(['/', '\\'], '_', $marche->num_marche) . '.pdf';

        $this->storeGeneratedPdf($marche, 'decision-nomination', $pdf->output());

        return $pdf->download($fileName);
    }

    private function downloadStandardDocumentPdf(Marche $marche, string $documentType): \Symfony\Component\HttpFoundation\Response
    {
        $labels = [
            'designation-agent-suivi' => "Désignation de l'agent chargé du suivi",
            'os-arret' => "Ordre de service d'arrêt",
            'os-reprise' => 'Ordre de service de reprise',
            'pv-reception-provisoire' => 'Procès-verbal de réception provisoire',
            'pv-reception-definitive' => 'Procès-verbal de réception définitive',
            'attestation-bonne-execution' => "Attestation de bonne exécution",
            'decision-nomination' => 'Décision de nomination',
        ];

        if ($documentType === 'designation-agent-suivi' && empty($marche->agent_suivi)) {
            return response()->json(['error' => "Le nom de l'agent chargé du suivi est requis"], 422);
        }
        if (in_array($documentType, ['pv-reception-definitive', 'attestation-bonne-execution'], true)
            && empty($marche->date_reception_finale)) {
            return response()->json(['error' => 'La date de réception finale est requise'], 422);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.marche.document_standard', [
            'marche' => $marche,
            'title' => $labels[$documentType],
            'documentType' => $documentType,
        ])->setPaper('a4', 'portrait');
        $content = $pdf->output();
        $this->storeGeneratedPdf($marche, $documentType, $content);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $documentType . '_' . str_replace(['/', '\\'], '_', $marche->num_marche) . '.pdf"',
        ]);
    }

    private function storeGeneratedPdf(Marche $marche, string $documentType, string $content): void
    {
        $directory = storage_path('app/documents/marches/' . $marche->id);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        file_put_contents($directory . '/' . $documentType . '.pdf', $content);
    }
}
