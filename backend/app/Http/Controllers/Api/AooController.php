<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Aoo;
use App\Models\ConcurrentLotDecision;
use App\Models\Fournisseur;
use App\Models\Lot;
use App\Models\Marche;
use App\Services\FicheSuiviBuilder;
use App\Services\LettreNotificationBuilder;
use App\Services\LotEstimationBuilder;
use App\Support\AooDocumentHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AooController extends Controller
{
    public function index()
    {
        return response()->json(
            Aoo::with(['concurrents.fournisseur', 'lots.items', 'lots.attributaire', 'lots.decisions.fournisseur'])
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|exists:aoos,id',
            'num_aoo' => 'required|string|unique:aoos,num_aoo,' . $request->id,
            'objet' => 'required|string',
            'journal_fr' => 'nullable|string',
            'journal_ar' => 'nullable|string',
            'date_ouverture' => 'nullable|date',
            'heure_ouverture' => 'nullable|string',
            'nombre_lots' => 'nullable|integer|min:1',
            'budget' => 'nullable|numeric',
            'art' => 'nullable|string',
            'par' => 'nullable|string',
            'lig' => 'nullable|string',
            'statut' => 'nullable|string',
            'president_commission' => 'nullable|string',
            'membres_commission' => 'nullable|array',
            'etat_avancement' => 'nullable|string',
            'num_decision_nomination' => 'nullable|string',
            'date_lettre' => 'nullable|date',
            'lieu_ouverture' => 'nullable|string',
            'num_aoo_interne' => 'nullable|string',
            'lots_details' => 'nullable|array',
            'lots_details.*.id' => 'nullable|exists:lots,id',
            'lots_details.*.num_lot' => 'nullable|string',
            'lots_details.*.objet_lot' => 'nullable|string',
            'lots_details.*.estimation' => 'nullable|numeric',
            'lots_details.*.items' => 'nullable|array',
            'lots_details.*.items.*.id' => 'nullable|exists:lot_items,id',
            'lots_details.*.items.*.designation' => 'nullable|string|max:500',
            'lots_details.*.items.*.unite' => 'nullable|string|max:50',
            'lots_details.*.items.*.quantite' => 'nullable|numeric|min:0',
            'lots_details.*.items.*.prix_unitaire_ht' => 'nullable|numeric|min:0',
        ]);

        $lotsDetails = $data['lots_details'] ?? null;
        unset($data['lots_details']);

        $aoo = DB::transaction(function () use ($data, $lotsDetails) {
            if (!empty($data['id'])) {
                $aoo = Aoo::findOrFail($data['id']);
                $aoo->update($data);
            } else {
                $aoo = Aoo::create($data);
            }

            if (is_array($lotsDetails)) {
                $receivedIds = [];

                foreach ($lotsDetails as $index => $lotData) {
                    $items = $lotData['items'] ?? [];
                    unset($lotData['items']);

                    $lot = $aoo->lots()->updateOrCreate(
                        ['id' => $lotData['id'] ?? null],
                        [
                            'num_lot' => $lotData['num_lot'] ?? 'LOT ' . ($index + 1),
                            'objet_lot' => $lotData['objet_lot'] ?? null,
                            'estimation' => $lotData['estimation'] ?? null,
                        ]
                    );
                    $receivedIds[] = $lot->id;

                    $receivedItemIds = [];
                    foreach ($items as $itemIndex => $itemData) {
                        if (empty($itemData['designation']) && empty($itemData['quantite']) && empty($itemData['prix_unitaire_ht'])) {
                            continue;
                        }

                        $quantite = (float) ($itemData['quantite'] ?? 0);
                        $puHt = (float) ($itemData['prix_unitaire_ht'] ?? 0);
                        $item = $lot->items()->updateOrCreate(
                            ['id' => $itemData['id'] ?? null],
                            [
                                'numero' => $itemIndex + 1,
                                'designation' => $itemData['designation'] ?? '',
                                'unite' => $itemData['unite'] ?? null,
                                'quantite' => $quantite,
                                'prix_unitaire_ht' => $puHt,
                                'montant_ht' => round($quantite * $puHt, 2),
                            ]
                        );
                        $receivedItemIds[] = $item->id;
                    }
                    $lot->items()->whereNotIn('id', $receivedItemIds)->delete();
                }

                $aoo->lots()->whereNotIn('id', $receivedIds)->delete();
            } elseif ((int) ($data['nombre_lots'] ?? 1) === 1 && $aoo->lots()->count() === 0) {
                $aoo->lots()->create([
                    'num_lot' => 'LOT 1',
                    'objet_lot' => $aoo->objet,
                    'estimation' => $aoo->budget,
                ]);
            }

            return $aoo->load(['concurrents.fournisseur', 'lots.items']);
        });

        return response()->json([
            'message' => 'Dossier AOO enregistre avec succes',
            'data' => $aoo,
        ], 200);
    }

    public function show($id)
    {
        return response()->json(
            Aoo::with([
                'marches.fournisseur',
                'marches.lot',
                'concurrents.fournisseur',
                'lots.items',
                'lots.attributaire',
                'lots.decisions.fournisseur',
            ])->findOrFail($id)
        );
    }

    public function update(Request $request, $id)
    {
        $request->merge(['id' => $id]);
        return $this->store($request);
    }

    public function destroy($id)
    {
        Aoo::findOrFail($id)->delete();
        return response()->json(['message' => 'Dossier supprime'], 200);
    }

    public function saveOuverturePlis(Request $request, Aoo $aoo)
    {
        $request->validate([
            'concurrents' => 'present|array',
            'concurrents.*.id' => 'nullable|exists:ouverture_plis_concurrents,id',
            'concurrents.*.fournisseur_id' => 'required|exists:fournisseurs,id',
        ]);

        $concurrents = collect($request->input('concurrents', []))
            ->filter(fn ($c) => !empty($c['fournisseur_id']))
            ->unique('fournisseur_id')
            ->values();

        $saved = DB::transaction(function () use ($aoo, $concurrents) {
            $fournisseurIds = [];

            foreach ($concurrents as $concurrentData) {
                $fournisseur = Fournisseur::findOrFail($concurrentData['fournisseur_id']);
                $fournisseurIds[] = $fournisseur->id;

                $aoo->concurrents()->updateOrCreate(
                    [
                        'aoo_id' => $aoo->id,
                        'fournisseur_id' => $fournisseur->id,
                    ],
                    [
                        'nom_soumissionnaire' => $fournisseur->raison_sociale,
                        'dh' => (bool) ($concurrentData['dh'] ?? false),
                        'cp' => (bool) ($concurrentData['cp'] ?? false),
                        'rc' => (bool) ($concurrentData['rc'] ?? false),
                        'cps' => (bool) ($concurrentData['cps'] ?? false),
                        'm_hum' => (bool) ($concurrentData['m_hum'] ?? false),
                        'montant_engagement' => isset($concurrentData['montant_engagement']) && $concurrentData['montant_engagement'] !== ''
                            ? $concurrentData['montant_engagement']
                            : null,
                        'observations' => $concurrentData['observations'] ?? '',
                        'ref_courrier' => $concurrentData['ref_courrier'] ?? null,
                        'signataire_titre' => $concurrentData['signataire_titre'] ?? null,
                        'gerant_nom' => !empty($concurrentData['gerant_nom'])
                            ? $concurrentData['gerant_nom']
                            : $fournisseur->representant,
                        'statut_analyse' => $concurrentData['statut_analyse'] ?? null,
                        'motif_ecartement' => $concurrentData['motif_ecartement'] ?? null,
                    ]
                );
            }

            $aoo->concurrents()->whereNotIn('fournisseur_id', $fournisseurIds)->delete();

            return $aoo->concurrents()->with('fournisseur')->get();
        });

        return response()->json([
            'message' => 'Ouverture des plis enregistree avec succes',
            'concurrents' => $saved,
        ], 200);
    }

    public function downloadDocument($id, $documentType)
    {
        $aoo = Aoo::with('lots.items')->findOrFail($id);
        $validDocuments = [
            'decision-lancement',
            'estimation',
            'decision-nomination',
            'avis-aoo',
            'lettre-commission',
            'lettre-controleur-etat',
            'convocation-membres',
            'liste-presence',
            'pv-ouverture',
            'tableau-ouverture',
            'rapport-analyse',
            'lettres-analyse',
            'resultat-aoo',
            'decision-attribution',
            'fiche-suivi',
        ];

        if (!in_array($documentType, $validDocuments, true)) {
            return response()->json(['error' => 'Document non reconnu'], 404);
        }

        if ($documentType === 'estimation') {
            return $this->downloadEstimationPdf($aoo);
        }

        if ($documentType === 'lettre-commission') {
            return $this->downloadLettreCommissionPdf($aoo);
        }

        if ($documentType === 'lettre-controleur-etat') {
            return $this->downloadLettreControleurEtatPdf($aoo);
        }

        if ($documentType === 'liste-presence') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.aoo.liste_presence', ['aoo' => $aoo]);
            return $pdf->download('Liste_Presence_AOO_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf');
        }

        if ($documentType === 'decision-nomination') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.aoo.decision_commission', ['aoo' => $aoo]);
            return $pdf->download('Decision_Nomination_AOO_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf');
        }

        if ($documentType === 'tableau-ouverture') {
            ini_set('max_execution_time', 120);
            try {
                return Excel::download(
                    new \App\Exports\OuverturePlisExport($aoo->load('concurrents.fournisseur')),
                    'Tableau_Ouverture_Plis_' . str_replace('/', '_', $aoo->num_aoo) . '.xlsx'
                );
            } catch (\Exception $e) {
                \Log::error('Erreur Export Excel : ' . $e->getMessage());
                return response()->json(['error' => 'Erreur lors de la generation : ' . $e->getMessage()], 500);
            }
        }

        if ($documentType === 'rapport-analyse' || $documentType === 'lettres-analyse') {
            return $this->telechargerLettresAnalyse($id);
        }

        if ($documentType === 'resultat-aoo') {
            return $this->telechargerResultatAoo($id);
        }

        if ($documentType === 'fiche-suivi') {
            return $this->downloadFicheSuiviPdf($aoo);
        }

        return response()->json([
            'message' => "Generation du PDF pour '{$documentType}' du dossier {$aoo->num_aoo} (En construction)",
            'url' => null,
        ], 200);
    }

    public function telechargerLettresAnalyse($id)
    {
        $aoo = Aoo::with(['lots', 'concurrents.fournisseur'])->findOrFail($id);
        $lettres = LettreNotificationBuilder::collectForAoo($aoo);

        if (empty($lettres)) {
            return response()->json(['error' => "Aucune decision d'analyse n'a ete enregistree pour les concurrents."], 404);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.aoo.lettres_notification', [
            'aoo' => $aoo,
            'lettres' => $lettres,
        ])->setPaper('A4', 'portrait');

        return $pdf->download('Lettres_Notification_AOO_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf');
    }

    public function downloadLettreNotification($aooId, $fournisseurId, $lotId)
    {
        $aoo = Aoo::with(['lots', 'concurrents.fournisseur'])->findOrFail($aooId);

        try {
            $lettre = LettreNotificationBuilder::build($aoo, (int) $fournisseurId, (int) $lotId);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Soumissionnaire ou lot introuvable pour cet AOO.'], 404);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.aoo.lettre_notification', [
            'aoo' => $aoo,
            'lettre' => $lettre,
        ])->setPaper('A4', 'portrait');

        $filename = preg_replace(
            '/[^\w\-]+/u',
            '_',
            'Lettre_' . ($lettre['admis'] ? 'Admission' : 'Ecartement') . '_' . $lettre['societe'] . '_Lot_' . $lettre['lot_numero']
        );

        return $pdf->download($filename . '.pdf');
    }

    public function telechargerResultatAoo($id)
    {
        $aoo = Aoo::with('concurrents.fournisseur')->findOrFail($id);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.aoo.resultat', compact('aoo'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('resultat_aoo_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf');
    }

    public function saveAnalyseMultiLots(Request $request, $id)
    {
        $request->validate([
            'decisions' => 'required|array',
            'decisions.*.fournisseur_id' => 'required|exists:fournisseurs,id',
            'decisions.*.lot_id' => 'required|exists:lots,id',
            'decisions.*.statut' => 'nullable|string|in:Retenu,Ecarte',
            'decisions.*.montant_propose' => 'nullable|numeric',
            'decisions.*.motif_ecartement' => 'nullable|string',
            'decisions.*.motif' => 'nullable|string',
        ]);

        try {
            $aoo = Aoo::with('lots')->findOrFail($id);

            DB::transaction(function () use ($aoo, $request) {
                ConcurrentLotDecision::where('aoo_id', $aoo->id)->delete();

                foreach ($request->decisions as $decision) {
                    if (!$aoo->lots->contains('id', (int) $decision['lot_id'])) {
                        continue;
                    }

                    ConcurrentLotDecision::create([
                        'aoo_id' => $aoo->id,
                        'lot_id' => $decision['lot_id'],
                        'fournisseur_id' => $decision['fournisseur_id'],
                        'montant_propose' => $decision['montant_propose'] ?? null,
                        'statut' => $decision['statut'] ?? null,
                        'motif_ecartement' => $decision['motif_ecartement'] ?? $decision['motif'] ?? null,
                    ]);
                }
            });

            return response()->json(['message' => 'Decisions multi-lots enregistrees avec succes.'], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function attribuerLots(Request $request, $id)
    {
        $request->validate([
            'attributions' => 'required|array',
            'attributions.*.lot_id' => 'required|exists:lots,id',
            'attributions.*.fournisseur_id' => 'required|exists:fournisseurs,id',
        ]);

        $aoo = Aoo::with(['lots.decisions.fournisseur'])->findOrFail($id);
        $createdMarches = [];

        foreach ($request->attributions as $attribution) {
            $lot = $aoo->lots->firstWhere('id', (int) $attribution['lot_id']);
            if (!$lot) {
                continue;
            }

            $decision = ConcurrentLotDecision::where('aoo_id', $aoo->id)
                ->where('lot_id', $lot->id)
                ->where('fournisseur_id', $attribution['fournisseur_id'])
                ->where('statut', 'Retenu')
                ->firstOrFail();

            $fournisseur = Fournisseur::findOrFail($attribution['fournisseur_id']);
            $lot->update(['attributaire_fournisseur_id' => $fournisseur->id]);

            $marche = Marche::updateOrCreate(
                ['aoo_id' => $aoo->id, 'lot_id' => $lot->id],
                [
                    'num_marche' => 'M-' . str_replace('/', '-', $aoo->num_aoo) . '-' . $lot->num_lot,
                    'fournisseur_id' => $fournisseur->id,
                    'lot' => $lot->num_lot,
                    'titulaire' => $fournisseur->raison_sociale,
                    'montant' => $decision->montant_propose ?? 0,
                    'statut' => 'en_creation',
                ]
            );
            $createdMarches[] = $marche->id;
        }

        $aoo->update(['statut' => 'attribue']);

        return response()->json([
            'message' => 'Attribution enregistree. ' . count($createdMarches) . ' marche(s) cree(s).',
            'marche_ids' => $createdMarches,
        ], 200);
    }

    public function cloturerAoo($id)
    {
        $aoo = Aoo::with(['concurrents.fournisseur', 'lots.attributaire', 'lots.decisions'])->findOrFail($id);

        if ($aoo->statut === 'attribue') {
            return response()->json(['error' => 'Cet AOO est deja cloture.'], 400);
        }

        if ($aoo->lots->count() > 1) {
            if ($aoo->lots->whereNull('attributaire_fournisseur_id')->count() > 0) {
                return response()->json(['error' => 'Veuillez designer un attributaire pour chaque lot avant de cloturer.'], 400);
            }

            $aoo->update(['statut' => 'attribue']);
            return response()->json([
                'message' => 'AOO multi-lots cloture avec succes. Les marches ont ete generes.',
                'marche_id' => null,
            ], 200);
        }

        $attributaire = $aoo->concurrents->where('statut_analyse', 'retenu')->first();

        if (!$attributaire) {
            return response()->json(['error' => "Impossible de cloturer : aucun concurrent n'a ete retenu lors de la phase d'analyse."], 400);
        }

        $aoo->update(['statut' => 'attribue']);
        $lot = $aoo->lots->first();

        $marche = Marche::firstOrCreate(
            ['aoo_id' => $aoo->id, 'lot_id' => $lot?->id],
            [
                'num_marche' => 'M-' . str_replace('/', '-', $aoo->num_aoo),
                'fournisseur_id' => $attributaire->fournisseur_id,
                'lot' => $lot?->num_lot,
                'titulaire' => $attributaire->fournisseur?->raison_sociale ?? $attributaire->nom_soumissionnaire,
                'montant' => $attributaire->montant_engagement ?? 0,
                'statut' => 'en_creation',
            ]
        );

        return response()->json([
            'message' => "L'AOO a ete cloture et le marche a ete initialise avec succes.",
            'marche_id' => $marche->id,
        ], 200);
    }

    private function downloadEstimationPdf(Aoo $aoo): \Symfony\Component\HttpFoundation\Response
    {
        $lotsData = LotEstimationBuilder::buildForAoo($aoo);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.aoo.estimation', [
            'aoo' => $aoo,
            'lotsData' => $lotsData,
        ]);

        return $pdf->download('Estimation_AOO_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf');
    }

    private function downloadLettreCommissionPdf(Aoo $aoo): \Symfony\Component\HttpFoundation\Response
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.aoo.lettre_commission', [
            'aoo' => $aoo,
            'doc' => AooDocumentHelper::presentation($aoo),
        ]);

        return $pdf->download('Lettre_Commission_AOO_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf');
    }

    private function downloadLettreControleurEtatPdf(Aoo $aoo): \Symfony\Component\HttpFoundation\Response
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.aoo.lettre_controleur_etat', [
            'aoo' => $aoo,
            'doc' => AooDocumentHelper::presentation($aoo),
        ]);

        return $pdf->download('Lettre_Controleur_Etat_AOO_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf');
    }

    private function downloadFicheSuiviPdf(Aoo $aoo): \Symfony\Component\HttpFoundation\Response
    {
        $aoo->load(['lots.attributaire', 'lots.decisions.fournisseur', 'concurrents.fournisseur', 'lots.items']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.aoo.fiche_suivi', [
            'aoo' => $aoo,
            'fiche' => FicheSuiviBuilder::build($aoo),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Fiche_Suivi_AOO_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf');
    }
}
