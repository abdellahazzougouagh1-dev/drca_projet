<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Aoo;
use App\Models\ConcurrentLotDecision;
use App\Models\Fournisseur;
use App\Models\Lot;
use App\Models\Marche;
use App\Models\RegistreEngagement;
use App\Services\FicheSuiviBuilder;
use App\Services\LettreNotificationBuilder;
use App\Services\LotEstimationBuilder;
use App\Support\AooDocumentHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class AooController extends Controller
{
    public function index()
    {
        return response()->json(
            Aoo::with(['notificationLigne', 'concurrents.fournisseur', 'lots.items', 'lots.attributaire', 'lots.decisions.fournisseur', 'lots.notificationLigne'])
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $input = $request->all();

        if (isset($input['id']) && !is_numeric($input['id'])) {
            unset($input['id']);
        }

        if (isset($input['lots_details']) && is_array($input['lots_details'])) {
            foreach ($input['lots_details'] as &$lot) {
                if (isset($lot['id']) && !is_numeric($lot['id'])) {
                    unset($lot['id']);
                }
                if (isset($lot['items']) && is_array($lot['items'])) {
                    foreach ($lot['items'] as &$item) {
                        if (isset($item['id']) && !is_numeric($item['id'])) {
                            unset($item['id']);
                        }
                    }
                }
            }
        }

        if (empty($input['id']) && !empty($input['num_aoo'])) {
            $existingAoo = Aoo::where('num_aoo', $input['num_aoo'])->first();
            if ($existingAoo) {
                $input['id'] = $existingAoo->id;
            }
        }

        $request->replace($input);

        $data = $request->validate([
            'id' => 'nullable|exists:aoos,id',
            'num_aoo' => 'required|string|unique:aoos,num_aoo,' . ($input['id'] ?? 'NULL'),
            'objet' => 'required|string',
            'objet_ar' => 'nullable|string',
            'reference' => 'nullable|string|max:255',
            'date_preparation' => 'nullable|date',
            'journal_fr' => 'nullable|string',
            'reference_publication_fr' => 'nullable|string|max:255',
            'date_publication_fr' => 'nullable|date',
            'journal_ar' => 'nullable|string',
            'reference_publication_ar' => 'nullable|string|max:255',
            'date_publication_ar' => 'nullable|date',
            'publications_journaux' => 'nullable|array',
            'date_publication_portail' => 'nullable|date',
            'ref_publication_portail' => 'nullable|string|max:255',
            'date_mise_en_ligne_portail' => 'nullable|date',
            'mode_passation' => 'nullable|string',
            'prix_reference' => 'nullable|numeric',
            'numero_engagement' => 'nullable|string|max:255',
            'date_engagement' => 'nullable|date',
            'reference_engagement' => 'nullable|string|max:255',
            'credit_ouvert_cp' => 'nullable|numeric',
            'credit_ouvert_ce' => 'nullable|numeric',
            'depenses_anterieures_cp' => 'nullable|numeric',
            'depenses_anterieures_ce' => 'nullable|numeric',
            'depenses_credits_engagement' => 'nullable|numeric',
            'depenses_credits_consolides' => 'nullable|numeric',
            'depenses_rap' => 'nullable|numeric',
            'montant_depense_neuf' => 'nullable|numeric',
            'interets_moratoires' => 'nullable|numeric',
            'montant_engager_neuf' => 'nullable|numeric',
            'pieces_jointes' => 'nullable|string',
            'date_ouverture' => 'required|date',
            'heure_ouverture' => 'required|string',
            'nombre_lots' => 'nullable|integer|min:1',
            'budget' => 'nullable|numeric',
            'type_budget' => 'nullable|in:Investissement,Fonctionnement',
            'notification_ligne_id' => 'nullable|exists:notification_lignes,id',
            'art' => 'nullable|string',
            'par' => 'nullable|string',
            'lig' => 'nullable|string',
            'imputation' => 'nullable|string',
            'statut' => 'nullable|string',
            'president_commission' => 'nullable|string',
            'rapporteur_commission' => 'nullable|string',
            'commission_validee' => 'nullable|boolean',
            'membres_commission' => 'nullable|array',
            'etat_avancement' => 'nullable|string',
            'num_decision_nomination' => 'nullable|string',
            'date_decision_nomination' => 'nullable|date',
            'date_lettre' => 'nullable|date',
            'lieu_ouverture' => 'nullable|string',
            'lieu_ouverture_ar' => 'nullable|string',
            'articles_rc' => 'nullable|string',
            'num_aoo_interne' => 'nullable|string',
            'lots_details' => 'nullable|array',
            'lots_details.*.id' => 'nullable|exists:lots,id',
            'lots_details.*.num_lot' => 'nullable|string',
            'lots_details.*.objet_lot' => 'nullable|string',
            'lots_details.*.objet_lot_ar' => 'nullable|string',
            'lots_details.*.estimation' => 'nullable|numeric',
            'lots_details.*.cautionnement_provisoire' => 'nullable|numeric',
            'lots_details.*.notification_ligne_id' => 'nullable|exists:notification_lignes,id',
            'lots_details.*.art' => 'nullable|string',
            'lots_details.*.par' => 'nullable|string',
            'lots_details.*.lig' => 'nullable|string',
            'lots_details.*.imputation' => 'nullable|string',
            'lots_details.*.items' => 'nullable|array',
            'lots_details.*.items.*.id' => 'nullable|exists:lot_items,id',
            'lots_details.*.items.*.designation' => 'nullable|string|max:500',
            'lots_details.*.items.*.unite' => 'nullable|string|max:50',
            'lots_details.*.items.*.quantite' => 'nullable|numeric|min:0',
            'lots_details.*.items.*.prix_unitaire_ht' => 'nullable|numeric|min:0',
        ]);

        if (!empty($data['notification_ligne_id'])) {
            $ligne = \App\Models\NotificationLigne::find($data['notification_ligne_id']);
            if ($ligne) {
                $data['art'] = $ligne->article;
                $data['par'] = $ligne->paragraphe;
                $data['lig'] = $ligne->ligne_budgetaire;
            }
        }

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

                    if (!empty($lotData['notification_ligne_id'])) {
                        $nl = \App\Models\NotificationLigne::find($lotData['notification_ligne_id']);
                        if ($nl) {
                            $lotData['art'] = $nl->article;
                            $lotData['par'] = $nl->paragraphe;
                            $lotData['lig'] = $nl->ligne_budgetaire;
                        }
                    }

                    $lot = $aoo->lots()->updateOrCreate(
                        ['id' => $lotData['id'] ?? null],
                        [
                            'num_lot' => $lotData['num_lot'] ?? 'LOT ' . ($index + 1),
                            'objet_lot' => $lotData['objet_lot'] ?? null,
                            'objet_lot_ar' => $lotData['objet_lot_ar'] ?? null,
                            'estimation' => $lotData['estimation'] ?? null,
                            'cautionnement_provisoire' => $lotData['cautionnement_provisoire'] ?? null,
                            'notification_ligne_id' => $lotData['notification_ligne_id'] ?? null,
                            'art' => $lotData['art'] ?? null,
                            'par' => $lotData['par'] ?? null,
                            'lig' => $lotData['lig'] ?? null,
                            'imputation' => $lotData['imputation'] ?? null,
                        ]
                    );
                    $receivedIds[] = $lot->id;

                    // Synchroniser l'imputation du 1er lot avec l'AOO global si non renseignée
                    if ($index === 0 && (empty($aoo->art) || empty($aoo->par) || empty($aoo->lig))) {
                        $aoo->update([
                            'art' => $lot->art,
                            'par' => $lot->par,
                            'lig' => $lot->lig,
                            'imputation' => $lot->imputation,
                            'notification_ligne_id' => $lot->notification_ligne_id,
                        ]);
                    }

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
                    'art' => $aoo->art,
                    'par' => $aoo->par,
                    'lig' => $aoo->lig,
                    'imputation' => $aoo->imputation,
                    'notification_ligne_id' => $aoo->notification_ligne_id,
                ]);
            }

            // La fiche est saisie au niveau de l'AOO : elle doit donc alimenter
            // le registre sans attendre la création d'un marché.
            if ($aoo->numero_engagement) {
                $this->syncRegistreEngagement($aoo);
            }

            return $aoo->load(['registreEngagement', 'concurrents.fournisseur', 'lots.items', 'lots.notificationLigne']);
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
                'registreEngagement',
                'marches.fournisseur',
                'marches.registreEngagement',
                'marches.lot',
                'concurrents.fournisseur',
                'lots.items',
                'lots.attributaire',
                'lots.decisions.fournisseur',
                'lots.notificationLigne',
            ])->findOrFail($id)
        );
    }

    private function syncRegistreEngagement(Aoo $aoo): void
    {
        $creditConsolide = $aoo->notificationLigne
            ? (float) ($aoo->notificationLigne->reports ?? 0) + (float) ($aoo->notificationLigne->credits_neufs ?? 0)
            : null;

        $aoo->registreEngagement()->updateOrCreate(
            ['aoo_id' => $aoo->id],
            [
                'consultation_id' => null,
                'marche_id' => null,
                'numero_ordre' => (int) ($aoo->registreEngagement?->numero_ordre
                    ?? (RegistreEngagement::max('numero_ordre') + 1)),
                'date_engagement' => $aoo->date_engagement,
                'numero_rubrique' => $aoo->numero_engagement,
                'mode_engagement' => 'Appel d\'offres',
                'reference' => $aoo->num_aoo,
                'reference_2' => $aoo->reference,
                'budget' => $aoo->type_budget ?: 'Investissement',
                'code' => $aoo->imputation,
                'art' => $aoo->art,
                'par' => $aoo->par,
                'lig' => $aoo->lig,
                'intitule' => $aoo->objet,
                'credit_ouvert_cp' => $aoo->credit_ouvert_cp,
                'credit_ouvert_ce' => $aoo->credit_ouvert_ce,
                'credit_consolide' => $creditConsolide,
                'depenses_anterieures_cp' => $aoo->depenses_anterieures_cp,
                'depenses_anterieures_ce' => $aoo->depenses_anterieures_ce,
                'depenses_credits_engagement' => $aoo->depenses_credits_engagement,
                'depenses_credits_consolides' => $aoo->depenses_credits_consolides,
                'depenses_rap' => $aoo->depenses_rap,
                'montant_depense_neuf' => $aoo->montant_depense_neuf,
                'interets_moratoires' => $aoo->interets_moratoires,
                'montant_engager_neuf' => $aoo->montant_engager_neuf,
                'objet' => $aoo->objet,
            ]
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


    public function passerCommissionOuverture(Request $request, Aoo $aoo)
    {
        $errors = [];

        // 1. Informations générales
        if (!$aoo->num_aoo || !$aoo->objet) {
            $errors[] = "Informations générales incomplètes (Numéro AOO ou Objet manquant).";
        }

        // 2. Lots et estimation
        if ($aoo->lots()->count() === 0) {
            $errors[] = "L'estimation budgétaire est incomplète (aucun lot enregistré).";
        }

        // 3. Membres commission
        $membres = is_string($aoo->membres_commission) ? json_decode($aoo->membres_commission, true) : $aoo->membres_commission;
        if (empty($membres) || count($membres) === 0) {
            $errors[] = "Les membres de la commission ne sont pas renseignés.";
        }

        if (count($errors) > 0) {
            return response()->json(['errors' => $errors], 422);
        }

        // Passage officiel
        $aoo->statut = 'Commission_Ouverture';
        $aoo->save();

        return response()->json([
            'message' => 'Passage à l\'Ouverture des plis réussi.',
            'statut' => $aoo->statut
        ]);
    }

    public function saveOuverturePlis(Request $request, Aoo $aoo)
    {
        \Illuminate\Support\Facades\Log::info('saveOuverturePlis payload:', $request->all());
        try {
            $request->validate([
                'concurrents' => 'present|array',
                'concurrents.*.id' => 'nullable|exists:ouverture_plis_concurrents,id',
                'concurrents.*.fournisseur_id' => 'required|exists:fournisseurs,id',
                'concurrents.*.nom_soumissionnaire' => 'nullable|string',
                'concurrents.*.adresse' => 'nullable|string',
                'concurrents.*.ville' => 'nullable|string',
                'concurrents.*.montant_engagement' => 'nullable|numeric',
                'concurrents.*.observations' => 'nullable|string',
                'concurrents.*.admin_conforme' => 'nullable|boolean',
                'concurrents.*.admin_motif_rejet' => 'nullable|string',
                'concurrents.*.admin_observations' => 'nullable|string',
                'concurrents.*.tech_conforme' => 'nullable|boolean',
                'concurrents.*.tech_note' => 'nullable|numeric|min:0|max:100',
                'concurrents.*.tech_observations' => 'nullable|string',
                'concurrents.*.montant_ht' => 'nullable|numeric',
                'concurrents.*.tva' => 'nullable|numeric',
                'concurrents.*.montant_ttc' => 'nullable|numeric',
                'concurrents.*.classement' => 'nullable|integer|min:1',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('Validation failed in saveOuverturePlis', $e->errors());
            throw $e;
        }

        $concurrents = collect($request->input('concurrents', []))
            ->filter(fn ($c) => !empty($c['fournisseur_id']))
            ->unique('fournisseur_id')
            ->values();

        $saved = DB::transaction(function () use ($aoo, $concurrents) {
            $fournisseurIds = [];

            foreach ($concurrents as $concurrentData) {
                // Determine if fournisseur_id is a valid ID
                $fid = $concurrentData['fournisseur_id'];
                
                $fournisseur = \App\Models\Fournisseur::findOrFail($fid);
                
                $updateData = [];
                if (!empty($concurrentData['adresse']) && ($fournisseur->adresse === 'Non renseignée' || empty($fournisseur->adresse))) {
                    $updateData['adresse'] = $concurrentData['adresse'];
                }
                if (!empty($concurrentData['ville']) && (strtoupper($fournisseur->ville) === 'NON RENSEIGNÉE' || strtoupper($fournisseur->ville) === 'NON RENSEIGNEE' || empty($fournisseur->ville))) {
                    $updateData['ville'] = $concurrentData['ville'];
                }
                if (!empty($updateData)) {
                    $fournisseur->update($updateData);
                }
                
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
                        'admin_conforme' => array_key_exists('admin_conforme', $concurrentData) ? (bool) $concurrentData['admin_conforme'] : null,
                        'admin_motif_rejet' => $concurrentData['admin_motif_rejet'] ?? null,
                        'admin_observations' => $concurrentData['admin_observations'] ?? null,
                        'tech_conforme' => array_key_exists('tech_conforme', $concurrentData) ? (bool) $concurrentData['tech_conforme'] : null,
                        'tech_note' => $concurrentData['tech_note'] ?? null,
                        'tech_observations' => $concurrentData['tech_observations'] ?? null,
                        'montant_ht' => $concurrentData['montant_ht'] ?? null,
                        'tva' => $concurrentData['tva'] ?? null,
                        'montant_ttc' => $concurrentData['montant_ttc'] ?? null,
                        'classement' => $concurrentData['classement'] ?? null,
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
            'bordereau-prix',
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
            'avis-publication-fr',
            'avis-publication-ar',
            'decision-attribution',
            'fiche-suivi',
            'lettre-ecartement',
            'acte-engagement',
            'marche-definitif',
            'decision-approbation',
            'decision-approbation',
            'os-commencement',
            'rapport-presentation',
            'rapport-prestation',
            'resultats-ao',
            'tableau-examen-offres',
            'tableau_examen_offres'
        ];

        if (!in_array($documentType, $validDocuments, true)) {
            return response()->json(['error' => 'Document non reconnu'], 404);
        }

        if ($documentType === 'estimation') {
            $html = view('pdf.aoo.estimation_administrative', ['aoo' => $aoo])->render();
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 10,
                'margin_bottom' => 10,
            ]);
            $mpdf->WriteHTML($html);
            $output = $mpdf->Output('', 'S');
            return response($output, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="Estimation_Budgetaire_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf"',
            ]);
        }

        
        if ($documentType === 'bordereau-prix') {
            $html = view('pdf.aoo.bordereau_prix', ['aoo' => $aoo])->render();
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 10,
                'margin_bottom' => 10,
            ]);
            $mpdf->WriteHTML($html);
            $output = $mpdf->Output('', 'S');
            return response($output, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="Bordereau_Prix_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf"',
            ]);
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

        if ($documentType === 'decision-lancement') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.aoo.decision_lancement', ['aoo' => $aoo]);
            return $pdf->download('Decision_Lancement_AOO_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf');
        }

        if ($documentType === 'avis-aoo') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.aoo.avis_aoo', ['aoo' => $aoo]);
            return $pdf->download('Avis_AOO_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf');
        }

        if ($documentType === 'avis-publication-fr') {
            $html = view('pdf.aoo.avis_publication_fr', ['aoo' => $aoo])->render();
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15,
            ]);
            $mpdf->WriteHTML($html);
            $output = $mpdf->Output('', 'S');
            
            if (ob_get_length()) {
                ob_clean();
            }
            
            return response($output, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Avis_Publication_FR_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf"',
            ]);
        }

        if ($documentType === 'avis-publication-ar') {
            $html = view('pdf.aoo.avis_publication_ar', ['aoo' => $aoo])->render();
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15,
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
            ]);
            $mpdf->WriteHTML($html);
            $output = $mpdf->Output('', 'S');
            
            if (ob_get_length()) {
                ob_clean();
            }
            
            return response($output, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Publication_Arabe_AOO_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf"',
            ]);
        }

        if ($documentType === 'convocation-membres') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.aoo.convocation_membres', ['aoo' => $aoo]);
            return $pdf->download('Convocation_Membres_AOO_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf');
        }

        if ($documentType === 'pv-ouverture') {
            try {
                $data = $this->buildPvOuvertureData($aoo);
                $html = view('pdf.aoo.pv_ouverture', $data)->render();
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'margin_left' => 15,
                    'margin_right' => 15,
                    'margin_top' => 12,
                    'margin_bottom' => 20,
                    'autoScriptToLang' => true,
                    'autoLangToFont' => true,
                ]);
                $mpdf->WriteHTML($html);
                $output = $mpdf->Output('', 'S');
                if (ob_get_length()) {
                    ob_clean();
                }
                $filename = 'PV_Ouverture_' . str_replace(['/', ' '], '_', $aoo->num_aoo) . '_' . date('d-m-Y') . '.pdf';
                return response($output, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $filename . '"',
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
        }
        
        if ($documentType === 'decision-nomination') {
            try {
                $data = $this->buildDecisionNominationData($aoo);
                $html = view('pdf.aoo.decision_nomination', $data)->render();
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'margin_left' => 15,
                    'margin_right' => 15,
                    'margin_top' => 12,
                    'margin_bottom' => 12,
                    'autoScriptToLang' => true,
                    'autoLangToFont' => true,
                ]);
                $mpdf->WriteHTML($html);
                $output = $mpdf->Output('', 'S');

                if (ob_get_length()) {
                    ob_clean();
                }

                $filename = 'Decision_Nomination_' . str_replace('/', '-', $aoo->num_aoo) . '_' . date('d-m-Y') . '.pdf';
                return response($output, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $filename . '"',
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
        }

        if ($documentType === 'tableau-examen-offres' || $documentType === 'tableau_examen_offres') {
            try {
                $customConcurrents = request()->input('concurrents');
                $data = $this->buildTableauExamenOffresData($aoo, $customConcurrents);
                $html = view('pdf.aoo.tableau_examen_offres', $data)->render();
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4-L',
                    'margin_left' => 10,
                    'margin_right' => 10,
                    'margin_top' => 10,
                    'margin_bottom' => 12,
                    'autoScriptToLang' => true,
                    'autoLangToFont' => true,
                ]);
                $footerHtml = '<table width="100%" style="border-top: 1px solid #cbd5e1; font-size: 7.5pt; color: #64748b; padding-top: 2px;">' .
                    '<tr>' .
                    '<td width="33%" style="text-align: left;">Consultation : ' . htmlspecialchars($aoo->num_aoo) . '</td>' .
                    '<td width="34%" style="text-align: center;">Examen des offres — DRCA-RSK</td>' .
                    '<td width="33%" style="text-align: right;">Page {PAGENO} / {nbpg}</td>' .
                    '</tr>' .
                    '</table>';
                $mpdf->SetHTMLFooter($footerHtml);

                $mpdf->WriteHTML($html);
                $output = $mpdf->Output('', 'S');

                if (ob_get_length()) {
                    ob_clean();
                }

                $filename = 'Tableau_Examen_Offres_' . str_replace(['/', ' '], '_', $aoo->num_aoo) . '_' . date('d-m-Y') . '.pdf';
                return response($output, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $filename . '"',
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
        }
        
        if (in_array($documentType, ['rapport-presentation', 'resultats-ao'])) {
            $typeDoc = str_replace('-', '_', $documentType); // rapport_presentation, resultats_ao
            try {
                $documentGenere = \App\Services\AooDocumentGenerationService::generate($aoo, $typeDoc);
                $filePath = storage_path('app/' . $documentGenere->chemin_fichier);
                if (ob_get_length()) {
                    ob_clean();
                }
                return response()->download($filePath, $documentGenere->nom_fichier);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
        }
        
        if ($documentType === 'rapport-prestation') {
            return $this->telechargerRapportPrestation($id);
        }

        if ($documentType === 'lettre-ecartement') {
            $fournisseurId = request()->query('fournisseur_id') ?: request()->input('fournisseur_id');
            try {
                $data = $this->buildLettreEcartementData($aoo, $fournisseurId ? (int) $fournisseurId : null);
                $html = view('pdf.aoo.lettre_ecartement', $data)->render();
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'margin_left' => 15,
                    'margin_right' => 15,
                    'margin_top' => 12,
                    'margin_bottom' => 15,
                    'autoScriptToLang' => true,
                    'autoLangToFont' => true,
                ]);
                $mpdf->WriteHTML($html);
                $output = $mpdf->Output('', 'S');

                $filename = 'Lettre_Ecartement_' . str_replace(['/', ' '], '_', $data['entreprise_nom']) . '_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf';
                return response($output, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
        }

        if (in_array($documentType, ['acte-engagement', 'marche-definitif', 'decision-approbation', 'os-commencement'], true)) {
            $fournisseurId = request()->query('fournisseur_id') ?: request()->input('fournisseur_id');
            try {
                $bladeView = 'pdf.aoo.' . str_replace('-', '_', $documentType);
                $builderMethod = 'build' . \Illuminate\Support\Str::studly(str_replace('-', '_', $documentType)) . 'Data';

                $data = method_exists($this, $builderMethod)
                    ? $this->$builderMethod($aoo, $fournisseurId ? (int) $fournisseurId : null)
                    : [];

                $html = view($bladeView, $data)->render();
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'margin_left' => 15,
                    'margin_right' => 15,
                    'margin_top' => 12,
                    'margin_bottom' => 15,
                    'autoScriptToLang' => true,
                    'autoLangToFont' => true,
                ]);
                $mpdf->WriteHTML($html);
                $output = $mpdf->Output('', 'S');

                $filename = \Illuminate\Support\Str::studly(str_replace('-', '_', $documentType)) . '_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf';
                return response($output, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
        }

        if ($documentType === 'rapport-analyse') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.aoo.rapport_analyse', ['aoo' => $aoo]);
            return $pdf->download('Rapport_Analyse_Offres_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf');
        }

        if ($documentType === 'decision-attribution') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.aoo.decision_attribution', ['aoo' => $aoo]);
            return $pdf->download('Decision_Attribution_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf');
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

    public function validateCommission(Request $request, Aoo $aoo)
    {
        \Illuminate\Support\Facades\Log::info('validateCommission payload:', $request->all());
        try {
            $data = $request->validate([
            'commission_validee' => 'nullable|boolean',
            'rapporteur_commission' => 'nullable|string|max:255',
            'president_commission' => 'nullable|string|max:255',
            'membres_commission' => 'nullable|array',
            'membres_commission.*.id' => 'nullable',
            'membres_commission.*.role' => 'nullable|string|max:255',
            'membres_commission.*.nom_prenom' => 'nullable|string|max:255',
            'membres_commission.*.fonction' => 'nullable|string|max:255',
            'membres_commission.*.organisme' => 'nullable|string|max:255',
            'heure_levee' => 'nullable|string',
            'num_decision_nomination' => 'nullable|string|max:255',
            'date_decision_nomination' => 'nullable|date',
            'lieu_ouverture' => 'nullable|string|max:255',
            'date_ouverture' => 'nullable|date',
            'heure_ouverture' => 'nullable|string',
        ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('Validation failed in validateCommission', $e->errors());
            throw $e;
        }

        $updateData = ['commission_validee' => true];

        $fields = [
            'rapporteur_commission', 'president_commission', 'membres_commission',
            'heure_levee', 'num_decision_nomination', 'date_decision_nomination',
            'lieu_ouverture', 'date_ouverture', 'heure_ouverture'
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        $aoo->update($updateData);

        return response()->json([
            'message' => 'Commission validée avec succès.',
            'aoo' => $aoo,
        ], 200);
    }

    public function generatePvOuverture(Aoo $aoo)
    {
        $templatePath = storage_path('app/templates/pv_ouverture.docx');
        if (file_exists($templatePath)) {
            $documentGenere = \App\Services\AooDocumentGenerationService::generate($aoo, 'pv_ouverture');
            $filePath = storage_path('app/' . $documentGenere->chemin_fichier);
            if (ob_get_length()) {
                ob_clean();
            }
            return response()->download($filePath, $documentGenere->nom_fichier);
        }

        $aoo->load('concurrents.fournisseur', 'lots.items');
        $pdf = Pdf::loadView('pdf.aoo.pv_ouverture', ['aoo' => $aoo])->setPaper('A4', 'portrait');

        return $pdf->download('PV_Ouverture_Plis_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf');
    }

    public function generateDocument(Request $request, Aoo $aoo, $type)
    {
        // Si le format demandé est PDF, on utilise directement la génération PDF native (bypasse LibreOffice et Word)
        if ($request->query('format') === 'pdf') {
            $pdfTypeMap = [
                'estimation' => 'estimation',
                'bordereau' => 'bordereau-prix',
                'avis_fr' => 'avis-publication-fr',
                'avis_ar' => 'avis-publication-ar',
                'decision-nomination' => 'decision-nomination',
                'decision_nomination' => 'decision-nomination',
            ];

            if (isset($pdfTypeMap[$type])) {
                return $this->downloadDocument($aoo->id, $pdfTypeMap[$type]);
            }
        }

        try {
            $extraData = [];
            if ($type === 'lettre_decartement' && $request->has('fournisseur_id')) {
                $aoo->load('concurrents.fournisseur');
                $concurrent = $aoo->concurrents->where('fournisseur_id', $request->input('fournisseur_id'))->first();
                if ($concurrent) {
                    $extraData['nom_soumissionnaire'] = $concurrent->fournisseur->raison_sociale ?? $concurrent->nom_soumissionnaire;
                    $extraData['adresse_soumissionnaire'] = $concurrent->fournisseur->adresse ?? '__________';
                    $extraData['motif_ecartement'] = $concurrent->motif_rejet ?? 'Non précisé';
                }
            }

            if ($type === 'avis_fr') {
                $templatePath = storage_path('app/templates/avis_publication_fr_fixed.docx');
                if (!file_exists($templatePath)) {
                    $templatePath = storage_path('app/templates/avis_publication_fr.docx');
                }
                if (!file_exists($templatePath)) {
                    return response()->json(['error' => 'Template Word non trouvé (avis_publication_fr.docx)'], 404);
                }

                $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
                
                // Champs de base demandés par l'utilisateur
                $templateProcessor->setValue('numero_ao', $aoo->num_aoo ?? '');
                $templateProcessor->setValue('objet_ao', $aoo->objet ?? '');
                
                // Formater les dates si elles existent
                $dateOuverture = $aoo->date_ouverture ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') : '';
                $heureOuverture = $aoo->heure_ouverture ?? '';
                $datePublication = date('d/m/Y'); // ou date d'approbation

                $templateProcessor->setValue('date_ouverture', $dateOuverture);
                $templateProcessor->setValue('heure_ouverture', $heureOuverture);
                $templateProcessor->setValue('lieu_ouverture_fr', $aoo->lieu_ouverture ?? '');
                $templateProcessor->setValue('date_publication', $datePublication);
                
                // Estimation (somme des lots ou globale)
                try {
                    $estimation = $aoo->lots()->sum('estimation');
                } catch (\Exception $e) {
                    $estimation = 0;
                }
                $templateProcessor->setValue('estimation', number_format($estimation, 2, ',', ' '));

                // Mapping des lots (On prend le premier lot par défaut si le document n'utilise pas de block)
                $lot = $aoo->lots->first();
                if ($lot) {
                    $templateProcessor->setValue('lot_numero', $lot->numero ?? 1);
                    
                    $est = $lot->estimation ?? 0;
                    $templateProcessor->setValue('estimation_ttc', number_format($est, 2, ',', ' '));
                    $templateProcessor->setValue('estimation_ttc_lettres', \App\Helpers\ChiffreEnLettre::convertir($est));
                    
                    $caut = $lot->cautionnement_provisoire ?? 0;
                    $templateProcessor->setValue('cautionnement_ttc', number_format($caut, 2, ',', ' '));
                    $templateProcessor->setValue('cautionnement_ttc_lettres', \App\Helpers\ChiffreEnLettre::convertir($caut));
                } else {
                    $templateProcessor->setValue('lot_numero', '1');
                    $templateProcessor->setValue('estimation_ttc', '0,00');
                    $templateProcessor->setValue('estimation_ttc_lettres', 'zéro');
                    $templateProcessor->setValue('cautionnement_ttc', '0,00');
                    $templateProcessor->setValue('cautionnement_ttc_lettres', 'zéro');
                }

                // Nom du fichier temporaire et final
                $filename = 'Avis_Publication_FR_' . str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $aoo->num_aoo) . '.docx';
                $tempPath = storage_path('app/' . $filename);
                $templateProcessor->saveAs($tempPath);
                
                if ($request->query('format') === 'pdf') {
                    try {
                        $pdfPath = \App\Services\PdfConverterService::convertToPdf($tempPath);
                        $pdfFilename = str_replace('.docx', '.pdf', $filename);
                        // Laravel deleteFileAfterSend will delete the PDF. We manually delete the temp DOCX.
                        register_shutdown_function(function() use ($tempPath) {
                            if (file_exists($tempPath)) @unlink($tempPath);
                        });
                        
                        \App\Models\DocumentGenere::updateOrCreate(
                            ['aoo_id' => $aoo->id, 'type_document' => $type, 'format' => 'pdf'],
                            ['statut' => 'généré', 'date_generation' => now()]
                        );
                        return response()->download($pdfPath, $pdfFilename)->deleteFileAfterSend(true);
                    } catch (\Exception $e) {
                        if (file_exists($tempPath)) @unlink($tempPath);
                        return response()->json(['error' => 'Erreur lors de la conversion PDF : ' . $e->getMessage()], 500);
                    }
                }

                
                \App\Models\DocumentGenere::updateOrCreate(
                    ['aoo_id' => $aoo->id, 'type_document' => $type, 'format' => 'docx'],
                    ['statut' => 'généré', 'date_generation' => now()]
                );
                return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
            }

            if ($type === 'avis_ar') {
                $templatePath = storage_path('app/templates/avis_publication_ar.docx');
                if (!file_exists($templatePath)) {
                    return response()->json(['error' => 'Template Word non trouvé (avis_publication_ar.docx)'], 404);
                }

                $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
                
                // Champs de base demandés par l'utilisateur
                $templateProcessor->setValue('numero_ao', $aoo->num_aoo ?? '');
                $templateProcessor->setValue('objet_ao_ar', $aoo->objet_ar ?? '');
                
                // Formater les dates si elles existent
                $dateOuverture = $aoo->date_ouverture ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') : '';
                $heureOuverture = $aoo->heure_ouverture ?? '';
                $datePublication = date('d/m/Y'); // ou date d'approbation

                $templateProcessor->setValue('date_ouverture', $dateOuverture);
                $templateProcessor->setValue('heure_ouverture', $heureOuverture);
                $templateProcessor->setValue('lieu_ouverture_ar', $aoo->lieu_ouverture_ar ?? '');
                $templateProcessor->setValue('date_publication', $datePublication);
                
                // Estimation (somme des lots ou globale)
                try {
                    $estimation = $aoo->lots()->sum('estimation');
                } catch (\Exception $e) {
                    $estimation = 0;
                }
                $templateProcessor->setValue('estimation', number_format($estimation, 2, ',', ' '));

                // Mapping dynamique des lots pour supporter plusieurs lots (via cloneBlock)
                $lotsData = [];
                foreach ($aoo->lots as $lot) {
                    $est = $lot->estimation ?? 0;
                    $caut = $lot->cautionnement_provisoire ?? 0;
                    $lotsData[] = [
                        'lot_numero' => $lot->numero ?? 1,
                        'estimation_ttc' => number_format($est, 2, ',', ' '),
                        'estimation_ttc_lettres' => \App\Helpers\NumberToWordsHelper::toArabicWords($est),
                        'cautionnement_ttc' => number_format($caut, 2, ',', ' '),
                        'cautionnement_ttc_lettres' => \App\Helpers\NumberToWordsHelper::toArabicWords($caut),
                        'objet_lot_ar' => $lot->objet_lot_ar ?? '',
                    ];
                }

                if (count($lotsData) === 0) {
                    $lotsData[] = [
                        'lot_numero' => '1', 'estimation_ttc' => '0,00', 'estimation_ttc_lettres' => 'صفر',
                        'cautionnement_ttc' => '0,00', 'cautionnement_ttc_lettres' => 'صفر', 'objet_lot_ar' => ''
                    ];
                }

                try {
                    // On essaie de cloner les blocs si l'utilisateur les a mis dans son Word
                    $templateProcessor->cloneBlock('block_caut', 0, true, false, $lotsData);
                    $templateProcessor->cloneBlock('block_est', 0, true, false, $lotsData);
                } catch (\Exception $e) {
                    // Si les blocs n'existent pas, on ignore
                }

                // Fallback : on remplace les variables simples au cas où il n'a pas utilisé de bloc (pour 1 lot)
                foreach ($lotsData[0] as $key => $val) {
                    $templateProcessor->setValue($key, $val);
                }

                // Nom du fichier temporaire et final
                $filename = 'Avis_Publication_AR_' . str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $aoo->num_aoo) . '.docx';
                $tempPath = storage_path('app/' . $filename);
                $templateProcessor->saveAs($tempPath);
                
                if ($request->query('format') === 'pdf') {
                    try {
                        $pdfPath = \App\Services\PdfConverterService::convertToPdf($tempPath);
                        $pdfFilename = str_replace('.docx', '.pdf', $filename);
                        register_shutdown_function(function() use ($tempPath) {
                            if (file_exists($tempPath)) @unlink($tempPath);
                        });
                        
                        \App\Models\DocumentGenere::updateOrCreate(
                            ['aoo_id' => $aoo->id, 'type_document' => $type, 'format' => 'pdf'],
                            ['statut' => 'généré', 'date_generation' => now()]
                        );
                        return response()->download($pdfPath, $pdfFilename)->deleteFileAfterSend(true);
                    } catch (\Exception $e) {
                        if (file_exists($tempPath)) @unlink($tempPath);
                        return response()->json(['error' => 'Erreur lors de la conversion PDF : ' . $e->getMessage()], 500);
                    }
                }

                
                \App\Models\DocumentGenere::updateOrCreate(
                    ['aoo_id' => $aoo->id, 'type_document' => $type, 'format' => 'docx'],
                    ['statut' => 'généré', 'date_generation' => now()]
                );
                return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
            }

            if ($type === 'estimation') {
                $templatePath = storage_path('app/templates/estimation.docx');
                if (!file_exists($templatePath)) {
                    return response()->json(['error' => 'Template Word non trouvé (estimation.docx)'], 404);
                }

                $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);
                
                $templateProcessor->setValue('num_aoo', $aoo->num_aoo ?? '');
                $templateProcessor->setValue('objet', $aoo->objet ?? '');
                $dateOuverture = $aoo->date_ouverture ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') : '......................';
                $templateProcessor->setValue('date_ouverture', $dateOuverture);
                
                $aoo->loadMissing('lots.items');
                $lot = $aoo->lots->first();
                if ($lot) {
                    $templateProcessor->setValue('lot_numero', $lot->num_lot ?? '1');
                    $templateProcessor->setValue('lot_objet', $lot->objet_lot ?? '');
                    
                    // Calcul des totaux et articles
                    $itemsData = [];
                    $totalHt = 0;
                    if ($lot->items && $lot->items->count() > 0) {
                        foreach ($lot->items as $index => $item) {
                            $mht = (float) $item->montant_ht;
                            $totalHt += $mht;
                            $itemsData[] = [
                                'n' => $index + 1,
                                'designation' => $item->designation,
                                'unite' => $item->unite ?: '-',
                                'quantite' => number_format((float) $item->quantite, 2, ',', ' '),
                                'pu_ht' => number_format((float) $item->prix_unitaire_ht, 2, ',', ' '),
                                'montant_ht' => number_format($mht, 2, ',', ' ')
                            ];
                        }
                    } else {
                        // Forfait par défaut si aucun article détaillé
                        $totalHt = (float) $lot->estimation; 
                        $itemsData[] = [
                            'n' => 1,
                            'designation' => 'Prestations selon le CPS.',
                            'unite' => 'Forfait',
                            'quantite' => '1,00',
                            'pu_ht' => number_format($totalHt, 2, ',', ' '),
                            'montant_ht' => number_format($totalHt, 2, ',', ' ')
                        ];
                    }
                    
                    try {
                        $templateProcessor->cloneRowAndSetValues('n', $itemsData);
                    } catch (\Exception $e) {
                        // On ignore si le tableau n'est pas bien formaté
                    }
                    
                    $totalTva = $totalHt * 0.20;
                    $totalTtc = $totalHt + $totalTva;
                    
                    $templateProcessor->setValue('total_ht', number_format($totalHt, 2, ',', ' '));
                    $templateProcessor->setValue('total_tva', number_format($totalTva, 2, ',', ' '));
                    $templateProcessor->setValue('total_ttc', number_format($totalTtc, 2, ',', ' '));
                    
                    $templateProcessor->setValue('montant_lettres', \App\Helpers\ChiffreEnLettre::convertir($totalTtc));
                } else {
                    $templateProcessor->setValue('lot_numero', '1');
                    $templateProcessor->setValue('lot_objet', '');
                    $templateProcessor->setValue('total_ht', '0,00');
                    $templateProcessor->setValue('total_tva', '0,00');
                    $templateProcessor->setValue('total_ttc', '0,00');
                    $templateProcessor->setValue('montant_lettres', 'zéro');
                }

                $filename = 'Estimation_' . str_replace('/', '_', $aoo->num_aoo) . '.docx';
                $tempPath = storage_path('app/' . $filename);
                $templateProcessor->saveAs($tempPath);
                
                if ($request->query('format') === 'pdf') {
                    try {
                        $pdfPath = \App\Services\PdfConverterService::convertToPdf($tempPath);
                        $pdfFilename = str_replace('.docx', '.pdf', $filename);
                        register_shutdown_function(function() use ($tempPath) {
                            if (file_exists($tempPath)) @unlink($tempPath);
                        });
                        
                        \App\Models\DocumentGenere::updateOrCreate(
                            ['aoo_id' => $aoo->id, 'type_document' => $type, 'format' => 'pdf'],
                            ['statut' => 'généré', 'date_generation' => now()]
                        );
                        return response()->download($pdfPath, $pdfFilename)->deleteFileAfterSend(true);
                    } catch (\Exception $e) {
                        if (file_exists($tempPath)) @unlink($tempPath);
                        return response()->json(['error' => 'Erreur lors de la conversion PDF : ' . $e->getMessage()], 500);
                    }
                }

                
                \App\Models\DocumentGenere::updateOrCreate(
                    ['aoo_id' => $aoo->id, 'type_document' => $type, 'format' => 'docx'],
                    ['statut' => 'généré', 'date_generation' => now()]
                );
                return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
            }

            $templatePath = storage_path("app/templates/{$type}.docx");
            if (!file_exists($templatePath)) {
                // Fallback PDF si le modèle Word n'est pas encore uploadé par l'utilisateur
                if ($type === 'bordereau') {
                    $aoo->loadMissing('lots.items');
                    $lignes = [];
                    $ht = 0;
                    $tva = 0;
                    $ttc = 0;
                    if ($aoo->lots->count() > 0 && $aoo->lots->first()->items->count() > 0) {
                        $lignes = $aoo->lots->first()->items;
                        $ht = $aoo->lots->first()->estimation ?? 1200000;
                        $tva = $ht * 0.20;
                        $ttc = $ht * 1.20;
                    }
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('templates.offre_standard', [
                        'numero_aoo' => $aoo->num_aoo,
                        'objet_aoo' => $aoo->objet_fr,
                        'lignes' => $lignes,
                        'montant_ht' => $ht,
                        'montant_tva' => $tva,
                        'montant_ttc' => $ttc
                    ])->setPaper('A4', 'portrait');
                    return $pdf->download('Bordereau_Prix_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf');
                }
                
                if ($type === 'rc') {
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML('<h1>Règlement de Consultation</h1><p>En cours de développement (PDF).</p>');
                    return $pdf->download('RC_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf');
                }
            } else {
                // If template exists but we fell through the if-blocks (like 'bordereau' that is generated by AooDocumentGenerationService?)
                // Wait, bordereau is generated by AooDocumentGenerationService?
                // Let's check where 'bordereau' is handled if template exists.
                // Oh wait, AooController@generateDocument doesn't have an explicit `if ($type === 'bordereau')`!
                // Ah, it actually does NOT. Let me check the original code:
                // `if ($type === 'avis_fr') ... if ($type === 'avis_ar') ... if ($type === 'estimation') ...`
                // Then `if (!file_exists($templatePath))` where `$templatePath = storage_path("app/templates/{$type}.docx");`.
                // Wait, if it EXISTS, what does it do? The original code didn't do anything for 'bordereau' if the file existed!
                // Wait, no! Look at `generateDocument`:
                // If it's none of `avis_fr`, `avis_ar`, `estimation`, it just falls out of the function without returning anything if the template exists!
                // Let me add handling for 'bordereau' via AooDocumentGenerationService since we saw it handles 'bordereau'.
            }

            $documentGenere = \App\Services\AooDocumentGenerationService::generate($aoo, $type, $extraData);
            $filePath = storage_path('app/' . $documentGenere->chemin_fichier);

            if (!file_exists($filePath)) {
                return response()->json(['error' => 'Fichier introuvable après génération.'], 404);
            }

            if (ob_get_length()) {
                ob_clean();
            }

            if ($request->query('format') === 'pdf') {
                try {
                    $pdfPath = \App\Services\PdfConverterService::convertToPdf($filePath);
                    return response()->download($pdfPath, str_replace('.docx', '.pdf', $documentGenere->nom_fichier));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Erreur de conversion PDF pour AOO {$aoo->id}: " . $e->getMessage());
                    return response()->json([
                        'error' => 'Erreur lors de la conversion PDF',
                        'message' => $e->getMessage()
                    ], 500);
                }
            }

            return response()->download($filePath, $documentGenere->nom_fichier);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erreur génération AOO {$aoo->id} type {$type}: " . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la génération du document',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function generateLettersEcartement(Aoo $aoo)
    {
        $lettres = LettreNotificationBuilder::collectForAoo($aoo);

        if (empty($lettres)) {
            return response()->json(['error' => 'Aucune lettre de notification disponible pour cette AOO.'], 404);
        }

        return response()->json([
            'message' => 'Lettres de notification préparées avec succès.',
            'count' => count($lettres),
        ], 200);
    }

    public function sendLettreEcartement(Aoo $aoo, $fournisseurId)
    {
        $aoo->load('concurrents.fournisseur', 'lots');
        $lettres = LettreNotificationBuilder::collectForAoo($aoo);
        $lettre = collect($lettres)->firstWhere('fournisseur_id', (int) $fournisseurId);

        if (!$lettre) {
            return response()->json(['error' => 'Aucune lettre disponible pour ce fournisseur.'], 404);
        }

        $concurrent = $aoo->concurrents->firstWhere('fournisseur_id', (int) $fournisseurId);
        if (!$concurrent || empty($concurrent->fournisseur?->email)) {
            return response()->json(['error' => 'Email introuvable pour ce fournisseur.'], 422);
        }

        try {
            $pdf = Pdf::loadView('pdf.aoo.lettre_notification', [
                'aoo' => $aoo,
                'lettre' => $lettre,
            ])->setPaper('A4', 'portrait');

            Mail::send([], [], function ($message) use ($concurrent, $pdf, $lettre) {
                $message->to($concurrent->fournisseur->email)
                    ->subject($lettre['objet_lettre'] . ' - ' . ($lettre['societe'] ?? 'Fournisseur'))
                    ->setBody(view('emails.lettre_ecartement', ['lettre' => $lettre])->render(), 'text/html')
                    ->attachData($pdf->output(), 'Lettre_Ecartement_' . preg_replace('/[^\w\-]+/u', '_', $lettre['societe']) . '.pdf', [
                        'mime' => 'application/pdf',
                    ]);
            });

            return response()->json(['message' => 'Lettre envoyée à ' . $concurrent->fournisseur->raison_sociale . '.'], 200);
        } catch (\Throwable $e) {
            \Log::error('Erreur envoi lettre ecartement: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de l\'envoi du mail.'], 500);
        }
    }

    public function sendLettresEcartement(Aoo $aoo)
    {
        $aoo->load('concurrents.fournisseur', 'lots');
        $lettres = LettreNotificationBuilder::collectForAoo($aoo);

        if (empty($lettres)) {
            return response()->json(['error' => 'Aucune lettre de notification à envoyer.'], 404);
        }

        $sent = [];
        $errors = [];

        foreach ($lettres as $lettre) {
            $concurrent = $aoo->concurrents->firstWhere('fournisseur_id', $lettre['fournisseur_id']);
            if (!$concurrent || empty($concurrent->fournisseur?->email)) {
                $errors[] = "Email introuvable pour " . ($concurrent->fournisseur?->raison_sociale ?? $lettre['societe']);
                continue;
            }

            try {
                $pdf = Pdf::loadView('pdf.aoo.lettre_notification', [
                    'aoo' => $aoo,
                    'lettre' => $lettre,
                ])->setPaper('A4', 'portrait');

                Mail::send([], [], function ($message) use ($concurrent, $pdf, $lettre) {
                    $message->to($concurrent->fournisseur->email)
                        ->subject($lettre['objet_lettre'] . ' - ' . ($lettre['societe'] ?? 'Fournisseur'))
                        ->setBody(view('emails.lettre_ecartement', ['lettre' => $lettre])->render(), 'text/html')
                        ->attachData($pdf->output(), 'Lettre_Ecartement_' . preg_replace('/[^\w\-]+/u', '_', $lettre['societe']) . '.pdf', [
                            'mime' => 'application/pdf',
                        ]);
                });

                $sent[] = $concurrent->fournisseur->raison_sociale;
            } catch (\Throwable $e) {
                \Log::error('Erreur envoi lettre ecartement pour ' . ($concurrent->fournisseur->raison_sociale ?? 'inconnu') . ': ' . $e->getMessage());
                $errors[] = "Impossible d'envoyer à " . ($concurrent->fournisseur->raison_sociale ?? 'inconnu');
            }
        }

        return response()->json([
            'message' => 'Envoi terminé.',
            'sent' => $sent,
            'errors' => $errors,
        ], 200);
    }

    public function telechargerResultatAoo($id)
    {
        $aoo = Aoo::with('concurrents.fournisseur')->findOrFail($id);

        if (empty($aoo->journal_ar) || preg_match('/^\?+$/', trim($aoo->journal_ar))) {
            $aoo->journal_ar = 'الصحراء المغربية';
        }
        if (empty($aoo->journal_fr) || preg_match('/^\?+$/', trim($aoo->journal_fr))) {
            $aoo->journal_fr = 'Le Matin';
        }

        try {
            $html = view('exports.aoo.resultat', compact('aoo'))->render();
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 12,
                'margin_right' => 12,
                'margin_top' => 10,
                'margin_bottom' => 10,
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
            ]);
            $mpdf->WriteHTML($html);
            $output = $mpdf->Output('', 'S');

            if (ob_get_length()) {
                ob_clean();
            }

            return response($output, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="resultat_aoo_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf"',
            ]);
        } catch (\Exception $e) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.aoo.resultat', compact('aoo'))
                ->setPaper('a4', 'portrait');
            return $pdf->download('resultat_aoo_' . str_replace('/', '_', $aoo->num_aoo) . '.pdf');
        }
    }

    public function generatePublicationAvis($id)
    {
        $aoo = Aoo::with('lots.items')->findOrFail($id);

        $required = [
            'objet' => "Objet de l'Appel d'Offres (Français)",
            'objet_ar' => "Objet de l'Appel d'Offres (Arabe)",
            'num_aoo' => "Numéro AOO",
            'date_preparation' => "Date de préparation",

            'date_ouverture' => "Date d'ouverture",
            'heure_ouverture' => "Heure d'ouverture",
        ];

        $missing = [];
        foreach ($required as $field => $label) {
            if (empty($aoo->{$field})) {
                $missing[] = $label;
            }
        }

        if ($aoo->lots->count() === 0) {
            $missing[] = "Aucun lot configuré dans le détail estimatif";
        } else {
            foreach ($aoo->lots as $index => $lot) {
                if ($lot->items->count() === 0) {
                    $missing[] = "Détail estimatif vide pour le " . ($lot->num_lot ?: 'Lot ' . ($index + 1));
                }
                if (empty($lot->cautionnement_provisoire) || (float) $lot->cautionnement_provisoire <= 0) {
                    $missing[] = "Cautionnement provisoire manquant pour le " . ($lot->num_lot ?: 'Lot ' . ($index + 1));
                }
            }
        }

        if (!empty($missing)) {
            return response()->json([
                'error' => 'Champs obligatoires manquants : ' . implode(', ', $missing),
            ], 422);
        }

        $data = $this->buildPublicationAvisData($aoo);
        $aoo->loadMissing('lots');
        Pdf::loadView('documents.avis-publication.francais.template', ['ao' => $aoo, 'lots' => $aoo->lots])->setPaper('A4', 'portrait');

        $html = view('pdf.aoo.avis_publication_ar', $data)->render();
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 14,
            'margin_bottom' => 10,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);
        $mpdf->WriteHTML($html);

        return response()->json([
            'message' => 'Avis de publication FR et AR générés avec succès.',
        ], 200);
    }

    private function buildPublicationAvisData(Aoo $aoo): array
    {
        $aoo->loadMissing('lots.items');

        $formattedLots = [];
        foreach ($aoo->lots as $index => $lot) {
            $estimationTtc = (float) $lot->estimation;
            if ($estimationTtc <= 0 && $lot->items->count() > 0) {
                $totalHt = $lot->items->sum(function ($item) {
                    return (float) $item->quantite * (float) $item->prix_unitaire_ht;
                });
                $estimationTtc = $totalHt * 1.20;
            }

            $cautionnement = (float) ($lot->cautionnement_provisoire ?? 0);

            $formattedLots[] = [
                'id' => $lot->id,
                'num_lot' => $lot->num_lot ?: 'Lot ' . ($index + 1),
                'num_lot_ar' => 'الحصة ' . ($index + 1),
                'objet_lot' => $lot->objet_lot ?: $aoo->objet,
                'objet_lot_ar' => $lot->objet_lot_ar ?: ($aoo->objet_ar ?: $aoo->objet),
                'estimation_ttc' => $estimationTtc,
                'estimation_chiffres' => number_format($estimationTtc, 2, ',', ' '),
                'estimation_lettres_fr' => \App\Helpers\NumberToWordsHelper::toFrenchWords($estimationTtc),
                'estimation_lettres_ar' => \App\Helpers\NumberToWordsHelper::toArabicWords($estimationTtc),
                'cautionnement' => $cautionnement,
                'cautionnement_chiffres' => number_format($cautionnement, 2, ',', ' '),
                'cautionnement_lettres_fr' => \App\Helpers\NumberToWordsHelper::toFrenchWords($cautionnement),
                'cautionnement_lettres_ar' => \App\Helpers\NumberToWordsHelper::toArabicWords($cautionnement),
            ];
        }

        $globalEstimation = (float) $aoo->budget;
        if ($globalEstimation <= 0 && count($formattedLots) > 0) {
            $globalEstimation = array_sum(array_column($formattedLots, 'estimation_ttc'));
        }

        $defaultLieuFr = "la salle des réunions au siège de la Direction Régionale de l'Office National du Conseil Agricole de Rabat-Salé-Kénitra, sis à angle avenue Mohamed V et Rue Sebta Kenitra";
        $defaultLieuAr = "بقاعة الاجتماعات بمقر المديرية الجهوية للاستشارة الفلاحية لجهة الرباط-سلا-القنيطرة الكائن مقرها بملتقى شارع محمد الخامس وزنقة سبتة القنيطرة";

        return [
            'aoo' => $aoo,
            'num_aoo' => $aoo->num_aoo,
            'reference' => $aoo->reference,
            'date_preparation' => $aoo->date_preparation ? $aoo->date_preparation->format('d/m/Y') : '',
            'objet' => $aoo->objet,
            'objet_ar' => $aoo->objet_ar ?: $aoo->objet,
            'date_ouverture' => $aoo->date_ouverture ? $aoo->date_ouverture->format('d/m/Y') : '',
            'date_ouverture_ar' => $aoo->date_ouverture ? $aoo->date_ouverture->format('Y/m/d') : '',
            'heure_ouverture' => $aoo->heure_ouverture ?: '10 Heures',
            'lieu_ouverture' => $aoo->lieu_ouverture ?: $defaultLieuFr,
            'lieu_ouverture_ar' => $aoo->lieu_ouverture_ar ?: $defaultLieuAr,
            'articles_rc' => $aoo->articles_rc ?: '08 et 10',
            'articles_rc_ar' => str_replace('et', 'و', $aoo->articles_rc ?: '8 و10'),
            'nombre_lots' => max(1, (int) $aoo->nombre_lots),
            'lots' => $formattedLots,
            'global_estimation' => $globalEstimation,
            'global_estimation_chiffres' => number_format($globalEstimation, 2, ',', ' '),
            'global_estimation_lettres_fr' => \App\Helpers\NumberToWordsHelper::toFrenchWords($globalEstimation),
            'global_estimation_lettres_ar' => \App\Helpers\NumberToWordsHelper::toArabicWords($globalEstimation),
        ];
    }

    public function translateFrToAr(Request $request)
    {
        $text = $request->input('text', '');
        $translated = \App\Services\TranslationService::translateFrToAr($text);
        return response()->json(['translated' => $translated]);
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
        $payload = $request->all();
        \Illuminate\Support\Facades\Log::info('--- DEBUT ATTRIBUER LOTS ---');
        \Illuminate\Support\Facades\Log::info('AOO ID: ' . $id);
        
        if (isset($payload['attributions']) && is_array($payload['attributions'])) {
            foreach ($payload['attributions'] as $index => $attr) {
                \Illuminate\Support\Facades\Log::info("Attribution Index [{$index}]:", [
                    'Lot ID' => $attr['lot_id'] ?? 'N/A',
                    'fournisseur_id (from request)' => $attr['fournisseur_id'] ?? 'N/A',
                    'raison_sociale' => $attr['titulaire_data']['raison_sociale'] ?? 'N/A',
                    'ICE' => $attr['titulaire_data']['ice'] ?? 'N/A',
                ]);
            }
        } else {
            \Illuminate\Support\Facades\Log::info('Aucune attribution trouvee dans le payload.');
        }

        $data = $request->validate([
            'attributions' => 'required|array',
            'attributions.*.lot_id' => 'required|exists:lots,id',
            'attributions.*.fournisseur_id' => 'required|exists:fournisseurs,id',
            'attributions.*.titulaire_data' => 'nullable|array',
            'attributions.*.marche_data' => 'nullable|array',
        ]);

        $aoo = Aoo::findOrFail($id);

        DB::transaction(function () use ($aoo, $data) {
            foreach ($data['attributions'] as $attributionData) {
                $lotId = $attributionData['lot_id'];
                $fournisseurId = (int) $attributionData['fournisseur_id'];
                $lot = $aoo->lots()->findOrFail($lotId);
                $fournisseur = \App\Models\Fournisseur::findOrFail($fournisseurId);
                
                if (!empty($attributionData['titulaire_data'])) {
                    // Valider l'unicité de l'ICE en ignorant l'ID du fournisseur modifié
                    $titulaireValidator = \Illuminate\Support\Facades\Validator::make($attributionData['titulaire_data'], [
                        'raison_sociale' => 'required|string|max:255',
                        'ice' => [
                            'required',
                            'string',
                            'max:255',
                            \Illuminate\Validation\Rule::unique('fournisseurs', 'ice')->ignore($fournisseur->id),
                        ],
                        'if' => 'nullable|string|max:255',
                        'rc' => 'nullable|string|max:255',
                        'adresse' => 'required|string',
                        'ville' => 'required|string|max:255',
                    ]);

                    if ($titulaireValidator->fails()) {
                        throw new \Illuminate\Validation\ValidationException($titulaireValidator);
                    }

                    $fournisseur->update($attributionData['titulaire_data']);
                }

                $lotUpdateData = ['attributaire_fournisseur_id' => $fournisseurId];
                if (!empty($attributionData['marche_data'])) {
                    $lotUpdateData = array_merge($lotUpdateData, $attributionData['marche_data']);
                }
                
                $lot->update($lotUpdateData);

                // Démarquer d'éventuelles décisions 'Retenu' contradictoires sur ce lot
                \App\Models\ConcurrentLotDecision::where('aoo_id', $aoo->id)
                    ->where('lot_id', $lotId)
                    ->where('fournisseur_id', '!=', $fournisseurId)
                    ->where('statut', 'Retenu')
                    ->update(['statut' => 'Ecarte']);

                // Synchroniser automatiquement la décision de lot en 'Retenu' pour le titulaire
                \App\Models\ConcurrentLotDecision::updateOrCreate(
                    [
                        'aoo_id' => $aoo->id,
                        'lot_id' => $lotId,
                        'fournisseur_id' => $fournisseurId,
                    ],
                    [
                        'statut' => 'Retenu',
                        'montant_propose' => $attributionData['marche_data']['montant_attribue_ttc'] ?? ($attributionData['marche_data']['montant_attribue_ht'] ?? null),
                    ]
                );

                // Créer ou synchroniser le Marché d'engagement
                $numMarche = 'M-' . str_replace('/', '-', $aoo->num_aoo) . ($aoo->lots->count() > 1 ? '-' . str_replace(' ', '', $lot->num_lot) : '');
                $montantMarche = $attributionData['marche_data']['montant_attribue_ttc'] ?? ($lot->montant_attribue_ttc ?? ($lot->estimation ?? ($aoo->budget ?? 0)));

                \App\Models\Marche::updateOrCreate(
                    [
                        'aoo_id' => $aoo->id,
                        'lot_id' => $lotId,
                    ],
                    [
                        'num_marche' => $numMarche,
                        'fournisseur_id' => $fournisseurId,
                        'lot' => $lot->num_lot ?? 'Lot unique',
                        'titulaire' => $fournisseur->raison_sociale,
                        'objet_marche' => $lot->objet_lot ?: $aoo->objet,
                        'montant' => $montantMarche,
                        'statut' => 'engagement_en_cours',
                    ]
                );
            }
            
            $aoo->update([
                'statut' => 'attribue',
            ]);
        });

        return response()->json([
            'message' => 'Attributions enregistrées avec succès',
        ]);
    }

    public function cloturerAoo($id)
    {
        $aoo = Aoo::with(['concurrents.fournisseur', 'lots.attributaire', 'lots.decisions'])->findOrFail($id);

        // Si multi-lots
        if ($aoo->lots->count() > 1) {
            $createdMarcheIds = [];
            foreach ($aoo->lots as $lot) {
                if ($lot->attributaire_fournisseur_id) {
                    $fournisseur = \App\Models\Fournisseur::findOrFail($lot->attributaire_fournisseur_id);
                    $marche = Marche::firstOrCreate(
                        ['aoo_id' => $aoo->id, 'lot_id' => $lot->id],
                        [
                            'num_marche' => 'M-' . str_replace('/', '-', $aoo->num_aoo) . '-' . str_replace(' ', '', $lot->num_lot),
                            'fournisseur_id' => $fournisseur->id,
                            'lot' => $lot->num_lot,
                            'titulaire' => $fournisseur->raison_sociale,
                            'montant' => $lot->montant_attribue_ttc ?? ($lot->estimation ?? 0),
                            'statut' => 'engagement_en_cours',
                        ]
                    );
                    $createdMarcheIds[] = $marche->id;
                }
            }

            if (empty($createdMarcheIds)) {
                return response()->json(['error' => "Impossible de clôturer : aucun lot n'a d'attributaire sélectionné."], 400);
            }

            $aoo->update(['statut' => 'attribue']);
            return response()->json([
                'message' => 'AOO multi-lots clôturé avec succès. Les marchés ont été générés.',
                'marche_id' => $createdMarcheIds[0] ?? null,
            ], 200);
        }

        // Lot unique
        $lot = $aoo->lots->first();
        $attributaireId = $lot?->attributaire_fournisseur_id;

        if (!$attributaireId) {
            $attributaire = $aoo->concurrents->where('statut_analyse', 'retenu')->first();
            if (!$attributaire) {
                $attributaire = $aoo->concurrents
                    ->filter(function ($c) {
                        $admin = strtolower(trim((string)$c->admin_conforme));
                        $tech = strtolower(trim((string)$c->tech_conforme));
                        $isAdminOk = ($admin === '1' || $admin === 'true' || $admin === 'oui' || $c->admin_conforme === true);
                        $isTechOk = ($tech === '1' || $tech === 'true' || $tech === 'oui' || $c->tech_conforme === true);
                        return $isAdminOk && $isTechOk;
                    })
                    ->first();
            }
            if ($attributaire && $attributaire->fournisseur_id) {
                $attributaireId = $attributaire->fournisseur_id;
                if ($lot) {
                    $lot->update(['attributaire_fournisseur_id' => $attributaireId]);
                }
            }
        }

        if (!$attributaireId) {
            return response()->json(['error' => "Le fournisseur retenu par la Commission est introuvable. Impossible de clôturer."], 400);
        }

        $fournisseur = \App\Models\Fournisseur::findOrFail($attributaireId);
        $aoo->update(['statut' => 'attribue']);

        $marche = Marche::firstOrCreate(
            ['aoo_id' => $aoo->id, 'lot_id' => $lot?->id],
            [
                'num_marche' => 'M-' . str_replace('/', '-', $aoo->num_aoo),
                'fournisseur_id' => $fournisseur->id,
                'lot' => $lot?->num_lot ?? 'Lot unique',
                'titulaire' => $fournisseur->raison_sociale,
                'montant' => $lot?->montant_attribue_ttc ?? ($aoo->budget ?? 0),
                'statut' => 'engagement_en_cours',
            ]
        );

        return response()->json([
            'message' => "L'AOO a été clôturé et le marché a été initialisé avec succès.",
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

    public function buildPvOuvertureData(Aoo $aoo): array
    {
        $aoo->loadMissing('concurrents.fournisseur', 'lots.items');

        $dateOuverture = $aoo->date_ouverture_plis 
            ? \Carbon\Carbon::parse($aoo->date_ouverture_plis)->format('d/m/Y')
            : ($aoo->date_ouverture ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') : ($aoo->date_limite_remise_plis ? \Carbon\Carbon::parse($aoo->date_limite_remise_plis)->format('d/m/Y') : date('d/m/Y')));

        $datePubPortail = $aoo->date_publication_portail 
            ? \Carbon\Carbon::parse($aoo->date_publication_portail)->format('d/m/Y')
            : ($aoo->date_publication_reelle ? \Carbon\Carbon::parse($aoo->date_publication_reelle)->format('d/m/Y') : $dateOuverture);

        $lieuOuverture = $aoo->lieu_ouverture_plis ?: ($aoo->lieu_ouverture ?: 'salle de réunions de la Direction Régionale du Conseil Agricole de la Région Rabat-Salé-Kenitra');

        $journaux = [];
        if (!empty($aoo->nom_journal_1)) $journaux[] = $aoo->nom_journal_1;
        if (!empty($aoo->nom_journal_2)) $journaux[] = $aoo->nom_journal_2;
        $pubJournaux = count($journaux) > 0 ? implode(' et ', $journaux) : ($aoo->publication_journaux ?: 'Le journal le Matin');

        // Concurrents lists
        $concurrentsDeposes = [];
        $concurrentsEcartesAdminTech = [];
        $concurrentsAdmisSansReserve = [];
        $actesEngagement = [];
        $actesEngagementRectifies = [];
        $concurrentsEcartesFinancier = [];
        $concurrentRetenu = null;

        $dbConcurrents = $aoo->concurrents()->with('fournisseur')->orderBy('id', 'asc')->get();

        foreach ($dbConcurrents as $c) {
            $nom = strtoupper(trim($c->fournisseur ? $c->fournisseur->raison_sociale : ($c->nom_soumissionnaire ?: 'SOCIETE')));
            $concurrentsDeposes[] = $nom;

            $isAdminRejected = ($c->admin_conforme === 0 || $c->admin_conforme === false || $c->admin_conforme === '0');
            $isTechRejected = $isAdminRejected || ($c->tech_conforme === 0 || $c->tech_conforme === false || $c->tech_conforme === '0');

            if ($isTechRejected) {
                $concurrentsEcartesAdminTech[] = $nom;
            } else {
                $concurrentsAdmisSansReserve[] = $nom;

                $ht = floatval($c->montant_ht ?: $c->montant_engagement ?: 0);
                $ttc = floatval($c->montant_ttc ?: ($ht > 0 ? round($ht * 1.2, 2) : 0));
                $rectifieHt = floatval($c->montant_rectifie ?: $ht);
                $rectifieTtc = floatval($rectifieHt > 0 ? round($rectifieHt * 1.2, 2) : $ttc);

                $actesEngagement[] = [
                    'nom' => $nom,
                    'montant_ttc' => $ttc,
                    'montant_ttc_format' => number_format($ttc, 2, ',', ' '),
                ];

                $actesEngagementRectifies[] = [
                    'nom' => $nom,
                    'montant_ttc' => $ttc,
                    'montant_ttc_format' => number_format($ttc, 2, ',', ' '),
                    'montant_rectifie_ttc' => $rectifieTtc,
                    'montant_rectifie_ttc_format' => number_format($rectifieTtc, 2, ',', ' '),
                ];

                // Verif if rejected later
                if ($c->statut_analyse && (str_contains(strtolower($c->statut_analyse), 'rejet') || str_contains(strtolower($c->statut_analyse), 'écart'))) {
                    $concurrentsEcartesFinancier[] = $nom;
                }

                if ($c->classement == 1 || $c->is_retenu || (isset($aoo->attributaire_provisoire) && $aoo->attributaire_provisoire === $nom)) {
                    $concurrentRetenu = [
                        'nom' => $nom,
                        'montant_ttc_format' => number_format($rectifieTtc > 0 ? $rectifieTtc : $ttc, 2, ',', ' '),
                    ];
                }
            }
        }

        if (!$concurrentRetenu && count($actesEngagementRectifies) > 0) {
            $sorted = $actesEngagementRectifies;
            usort($sorted, fn($a, $b) => $a['montant_rectifie_ttc'] <=> $b['montant_rectifie_ttc']);
            $first = $sorted[0];
            $concurrentRetenu = [
                'nom' => $first['nom'],
                'montant_ttc_format' => $first['montant_rectifie_ttc_format'],
            ];
        }

        $dateAchevement = $aoo->date_validation_commission 
            ? \Carbon\Carbon::parse($aoo->date_validation_commission)->format('d/m/Y')
            : ($aoo->date_decision_attribution ? \Carbon\Carbon::parse($aoo->date_decision_attribution)->format('d/m/Y') : $dateOuverture);

        $datePv = $aoo->date_pv 
            ? \Carbon\Carbon::parse($aoo->date_pv)->format('d/m/Y')
            : date('d/m/Y');

        return [
            'aoo' => $aoo,
            'num_aoo' => $aoo->num_aoo,
            'objet' => $aoo->objet,
            'maitre_ouvrage' => 'Direction Régionale Du Conseil Agricole De La Région Rabat-Sale-Kenitra',
            'date_ouverture' => $dateOuverture,
            'lieu_ouverture' => $lieuOuverture,
            'publication_journaux' => $pubJournaux,
            'date_publication_portail' => $datePubPortail,
            'concurrents_deposes' => $concurrentsDeposes,
            'concurrents_ecartes_admin_tech' => $concurrentsEcartesAdminTech,
            'concurrents_admis_sans_reserve' => $concurrentsAdmisSansReserve,
            'actes_engagement' => $actesEngagement,
            'actes_engagement_rectifies' => $actesEngagementRectifies,
            'concurrents_ecartes_financier' => $concurrentsEcartesFinancier,
            'concurrent_retenu' => $concurrentRetenu ?: ['nom' => '—', 'montant_ttc_format' => '—'],
            'justification' => $aoo->justification_attribution ?: 'offre jugée économiquement la plus avantageuse.',
            'date_achevement' => $dateAchevement,
            'date_pv' => $datePv,
        ];
    }

    private function buildDecisionNominationData(Aoo $aoo): array
    {
        $numeroDecision = $aoo->num_decision_nomination ?: '';
        $dateDecision = $aoo->date_decision_nomination 
            ? \Carbon\Carbon::parse($aoo->date_decision_nomination)->format('d/m/Y') 
            : '';

        $dateReunion = $aoo->date_ouverture 
            ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') 
            : ($aoo->date_preparation ? \Carbon\Carbon::parse($aoo->date_preparation)->format('d/m/Y') : date('d/m/Y'));

        $heureRaw = $aoo->heure_ouverture ?: '10:00';
        $heureReunion = str_replace(':', 'H', $heureRaw);
        if (strlen($heureReunion) === 4 && str_ends_with($heureReunion, 'H0')) {
            $heureReunion .= '0';
        }

        $lieuReunion = $aoo->lieu_ouverture ?: 'DRCA-RSK';

        $exercice = date('Y');
        if (!empty($aoo->date_ouverture)) {
            $exercice = \Carbon\Carbon::parse($aoo->date_ouverture)->format('Y');
        } elseif (preg_match('/\/(\d{4})\//', $aoo->num_aoo, $matches)) {
            $exercice = $matches[1];
        }

        $membresRaw = is_array($aoo->membres_commission) ? $aoo->membres_commission : [];
        if (empty($membresRaw)) {
            $membresRaw = [
                ['nom_prenom' => $aoo->president_commission ?: 'BOUAMAMA Boubker', 'fonction' => 'Chef de Service Administratif et Financier à la DRCA RSK', 'qualite' => 'Président'],
                ['nom_prenom' => $aoo->rapporteur_commission ?: 'KARKAS Ahmed', 'fonction' => 'Cadre au SAF à la DRCA RSK', 'qualite' => 'Membre'],
                ['nom_prenom' => 'Taouil Hasnaa', 'fonction' => 'Cadre au SAF à la DRCA RSK', 'qualite' => 'Membre'],
            ];
        } else {
            $membresRaw = array_map(function($m) {
                if (is_array($m)) {
                    $m['qualite'] = $m['qualite'] ?? $m['role'] ?? 'Membre';
                    $m['fonction'] = $m['fonction'] ?? $m['role'] ?? 'Membre';
                    $m['nom_prenom'] = $m['nom_prenom'] ?? ($m['nom'] ?? 'Membre');
                }
                return $m;
            }, $membresRaw);
        }

        return [
            'aoo' => $aoo,
            'num_aoo' => $aoo->num_aoo,
            'num_decision' => $numeroDecision,
            'num_decision_nomination' => $numeroDecision,
            'date_decision' => $dateDecision,
            'date_decision_nomination' => $dateDecision,
            'date_reunion' => $dateReunion,
            'heure_reunion' => $heureReunion,
            'lieu_reunion' => $lieuReunion,
            'exercice' => $exercice,
            'objet' => $aoo->objet,
            'membres' => $membresRaw,
            'references' => is_array($aoo->references_juridiques) ? $aoo->references_juridiques : [],
        ];
    }

    public function buildTableauExamenOffresData(Aoo $aoo, ?array $customConcurrents = null): array
    {
        $concurrentsList = [];

        if (is_array($customConcurrents) && count($customConcurrents) > 0) {
            foreach ($customConcurrents as $c) {
                $concurrentsList[] = [
                    'nom' => $c['raison_sociale'] ?? ($c['nom_soumissionnaire'] ?? 'Société'),
                    'admin_statut' => $c['adminStatut'] ?? ($c['admin_statut'] ?? (!empty($c['admin_conforme']) ? 'Admis sans réserve' : 'Rejeté')),
                    'admin_motif' => $c['admin_motif'] ?? ($c['admin_motif_rejet'] ?? '-'),
                    'tech_statut' => $c['techStatut'] ?? ($c['tech_statut'] ?? (!empty($c['tech_conforme']) ? 'Admis sans réserve' : 'Rejeté')),
                    'tech_motif' => $c['tech_motif'] ?? ($c['tech_motif_rejet'] ?? '-'),
                    'montant_ht' => isset($c['montant_ht']) && $c['montant_ht'] !== '' && $c['montant_ht'] !== '-' ? floatval($c['montant_ht']) : null,
                    'fin_statut' => $c['finStatut'] ?? ($c['fin_statut'] ?? 'Admis sans réserve'),
                    'fin_motif' => $c['fin_motif'] ?? '-',
                    'montant_rectifie' => isset($c['montant_rectifie']) && $c['montant_rectifie'] !== '' && $c['montant_rectifie'] !== '-' ? floatval($c['montant_rectifie']) : (isset($c['montant_ht']) && $c['montant_ht'] !== '' && $c['montant_ht'] !== '-' ? floatval($c['montant_ht']) : null),
                    'verif_statut' => $c['verifStatut'] ?? ($c['verif_statut'] ?? 'Admis sans réserve'),
                    'verif_motif' => $c['verif_motif'] ?? '-',
                    'classement' => isset($c['calculatedRank']) && $c['calculatedRank'] < 900 ? $c['calculatedRank'] : ($c['classement'] ?? null),
                    'classementDisplay' => $c['classementDisplay'] ?? null,
                ];
            }
        } else {
            $dbConcurrents = $aoo->concurrents()->with('fournisseur')->orderBy('id', 'asc')->get();
            foreach ($dbConcurrents as $c) {
                $nom = $c->fournisseur ? $c->fournisseur->raison_sociale : ($c->nom_soumissionnaire ?: 'Société');
                
                $adminStatut = ($c->admin_conforme === 0 || $c->admin_conforme === false || $c->admin_conforme === '0') ? 'Rejeté' : 'Admis sans réserve';
                $techStatut = $adminStatut === 'Rejeté' ? 'Non examiné' : (($c->tech_conforme === 0 || $c->tech_conforme === false || $c->tech_conforme === '0') ? 'Rejeté' : 'Admis sans réserve');
                $isTechRejected = $adminStatut === 'Rejeté' || $techStatut === 'Rejeté';
                $finStatut = $isTechRejected ? 'Non examiné' : 'Admis sans réserve';
                $verifStatut = $isTechRejected ? 'Non examiné' : 'Admis sans réserve';

                $concurrentsList[] = [
                    'nom' => $nom,
                    'admin_statut' => $c->admin_observations && str_contains($c->admin_observations, 'réserve') ? 'Admis avec réserve' : $adminStatut,
                    'admin_motif' => $c->admin_motif_rejet ?: ($c->admin_observations ?: '-'),
                    'tech_statut' => $c->tech_observations && str_contains($c->tech_observations, 'réserve') ? 'Admis avec réserve' : $techStatut,
                    'tech_motif' => $c->tech_observations ?: '-',
                    'montant_ht' => $isTechRejected ? null : ($c->montant_ht ?: $c->montant_engagement),
                    'fin_statut' => $finStatut,
                    'fin_motif' => '-',
                    'montant_rectifie' => $isTechRejected ? null : ($c->montant_ht ?: $c->montant_engagement),
                    'verif_statut' => $verifStatut,
                    'verif_motif' => '-',
                    'classement' => $c->classement ?: null,
                    'classementDisplay' => null,
                ];
            }
        }

        // Calcul dynamique des classements pour les concurrents admis
        $admisMap = [];
        foreach ($concurrentsList as $idx => $item) {
            $isAdmis = ($item['admin_statut'] === 'Admis sans réserve' || $item['admin_statut'] === 'Admis avec réserve') &&
                       ($item['tech_statut'] === 'Admis sans réserve' || $item['tech_statut'] === 'Admis avec réserve') &&
                       ($item['fin_statut'] === 'Admis sans réserve' || $item['fin_statut'] === 'Admis avec réserve') &&
                       ($item['verif_statut'] === 'Admis sans réserve' || $item['verif_statut'] === 'Admis avec réserve');
            if ($isAdmis && !empty($item['montant_rectifie']) && $item['montant_rectifie'] > 0) {
                $admisMap[$idx] = floatval($item['montant_rectifie']);
            }
        }
        asort($admisMap);
        $rankNum = 1;
        $calculatedRanks = [];
        foreach ($admisMap as $idx => $mVal) {
            $calculatedRanks[$idx] = $rankNum++;
        }
        foreach ($concurrentsList as $idx => &$item) {
            if (empty($item['classement']) && isset($calculatedRanks[$idx])) {
                $item['classement'] = $calculatedRanks[$idx];
            }
        }

        $estimation = floatval($aoo->estimation_marche ?? ($aoo->estimation_globale ?? 0));
        $seuilBas = $estimation > 0 ? $estimation * 0.8 : 0;
        $seuilHaut = $estimation > 0 ? $estimation * 1.2 : 0;

        $journaux = [];
        if (!empty($aoo->nom_journal_1)) $journaux[] = $aoo->nom_journal_1;
        if (!empty($aoo->nom_journal_2)) $journaux[] = $aoo->nom_journal_2;
        $pubJournaux = count($journaux) > 0 ? implode(' / ', $journaux) : ($aoo->publication_journaux ?: 'Le Matin / Assahra Al Maghribia');

        return [
            'aoo' => $aoo,
            'num_aoo' => $aoo->num_aoo,
            'objet' => $aoo->objet,
            'mode_passation' => $aoo->type_procedure ?: 'Appel d\'Offres Ouvert',
            'date_heure_limite' => ($aoo->date_limite_remise_plis ? \Carbon\Carbon::parse($aoo->date_limite_remise_plis)->format('d/m/Y') : '') . ($aoo->heure_limite_remise_plis ? ' à ' . $aoo->heure_limite_remise_plis : ($aoo->heure_ouverture_plis ? ' à ' . $aoo->heure_ouverture_plis : '')),
            'lieu' => $aoo->lieu_ouverture_plis ?: 'Direction Régionale du Conseil Agricole de Rabat-Salé-Kénitra',
            'publication_journaux' => $pubJournaux,
            'publication_portail' => $aoo->publication_portail ?: 'Portail des Marchés Publics (www.marchespublics.gov.ma)',
            'estimation' => $estimation,
            'seuil_bas' => $seuilBas,
            'seuil_haut' => $seuilHaut,
            'concurrents' => $concurrentsList,
            'date_edition' => date('d/m/Y'),
        ];
    }

    private function buildLettreEcartementData(Aoo $aoo, ?int $fournisseurId): array
    {
        $aoo->loadMissing('concurrents.fournisseur');

        if (!$fournisseurId && $aoo->concurrents->count() > 0) {
            $rejected = $aoo->concurrents->first(function ($c) {
                return $c->admin_conforme === false || $c->tech_conforme === false || str_contains($c->statut_analyse ?? '', 'Rejet');
            });
            if ($rejected) {
                $fournisseurId = $rejected->fournisseur_id ?: $rejected->id;
            }
        }

        $concurrent = null;
        if ($fournisseurId) {
            $concurrent = $aoo->concurrents->first(function ($c) use ($fournisseurId) {
                return $c->fournisseur_id == $fournisseurId || $c->id == $fournisseurId;
            });
        }

        if (!$concurrent && $aoo->concurrents->count() > 0) {
            $concurrent = $aoo->concurrents->first();
        }

        $fournisseur = $concurrent?->fournisseur;
        $entrepriseNom = $fournisseur->raison_sociale ?? $fournisseur->societe ?? $concurrent?->nom_soumissionnaire ?? 'Société ABC SARL';
        $adresse = $fournisseur->adresse ?? '';
        $ville = $fournisseur->ville ?? '';

        // Motif d'écartement
        $motif = '';
        if ($concurrent) {
            if ($concurrent->admin_conforme === false) {
                $motif = $concurrent->admin_motif_custom ?: ($concurrent->admin_motif_rejet ?: 'Dossier administratif non conforme');
            } elseif ($concurrent->tech_conforme === false) {
                $motif = $concurrent->tech_observations ?: 'Offre technique non conforme';
            } elseif (!empty($concurrent->motif_ecartement)) {
                $motif = $concurrent->motif_ecartement;
            } else {
                $motif = 'Dossier non conforme aux exigences du règlement de consultation';
            }
        } else {
            $motif = 'Dossier administratif non conforme';
        }

        if (empty(trim($motif))) {
            throw new \Exception("Le motif d'écartement est obligatoire pour générer la lettre.");
        }

        return [
            'num_aoo' => $aoo->num_aoo ?: '03/2026/DRCA-RSK',
            'date_publication' => $aoo->date_publication_fr ? $aoo->date_publication_fr->format('d/m/Y') : ($aoo->date_preparation ? $aoo->date_preparation->format('d/m/Y') : date('d/m/Y')),
            'objet' => $aoo->objet ?: 'Acquisition de matériel et équipements agricoles',
            'entreprise_nom' => $entrepriseNom,
            'adresse' => $adresse,
            'ville' => $ville,
            'motif_ecartement' => $motif,
        ];
    }

    private function buildActeEngagementData(Aoo $aoo, ?int $fournisseurId = null): array
    {
        $aoo->loadMissing(['concurrents.fournisseur', 'lots.attributaire']);

        $winnerLot = $aoo->lots->first(function ($l) { return !empty($l->attributaire_id); });
        $fournisseur = $winnerLot?->attributaire;

        if (!$fournisseur && $fournisseurId) {
            $fournisseur = Fournisseur::find($fournisseurId);
        }

        if (!$fournisseur && $aoo->concurrents->count() > 0) {
            $fournisseur = $aoo->concurrents->first()?->fournisseur;
        }

        $entrepriseNom = $fournisseur->raison_sociale ?? $fournisseur->societe ?? 'TERRAFITOO SARLAU';
        $representant = $fournisseur->representant ?? 'DEKKAKI MOHAMMED';
        $qualite = $fournisseur->qualite_representant ?? 'Gérant';
        $adresse = $fournisseur->adresse ?? 'Résidence Ben Rochd, Immeuble D2, N°6, V.N.';
        $ville = $fournisseur->ville ?? 'Meknès';
        $capital = $fournisseur->capital ?? '10 000';
        $telephone = $fournisseur->telephone ?? '0661108772';
        $email = $fournisseur->email ?? 'mdekkaki@hotmail.com';
        $cnss = $fournisseur->cnss ?? '9184342';
        $rc = $fournisseur->rc ?? '34009';
        $villeRc = $fournisseur->ville ?: 'Meknès';
        $patente = $fournisseur->patente ?? '17194027';
        $ice = $fournisseur->ice ?? '001835798000066';
        $rib = $fournisseur->rib ?? '127 480 2121155766090007 54';
        $banque = $fournisseur->banque ?? 'Banque Populaire';
        $agenceBancaire = $fournisseur->agence_bancaire ?? 'Meknès-Kamélia';

        $montantTtc = (float) ($winnerLot?->montant_attribué ?: ($aoo->estimation_budgetaire ?: 204384.00));
        $montantHt = round($montantTtc / 1.20, 2);
        $montantTva = round($montantTtc - $montantHt, 2);

        return [
            'num_aoo' => $aoo->num_aoo ?: '06/2026/DRCA-RSK',
            'date_ouverture' => $aoo->date_ouverture ? $aoo->date_ouverture->format('d/m/Y') : date('d/m/Y'),
            'heure_ouverture' => $aoo->heure_ouverture ?: '10:00',
            'objet_marche' => $aoo->objet ?: 'Organisation des journées de formation au profit des agriculteurs et femmes rurales',
            'lot_info' => $winnerLot ? ('Lot ' . $winnerLot->num_lot . ' : ' . ($winnerLot->objet_lot ?: $aoo->objet)) : 'Lot 2 : Organisation des journées de formation au profit des femmes rurales',
            'titulaire_nom' => $entrepriseNom,
            'representant_nom' => $representant,
            'qualite_gerant' => $qualite,
            'capital' => $capital,
            'adresse' => $adresse,
            'ville' => $ville,
            'telephone' => $telephone,
            'email' => $email,
            'cnss' => $cnss,
            'rc' => $rc,
            'ville_rc' => $villeRc,
            'patente' => $patente,
            'ice' => $ice,
            'rib' => $rib,
            'banque' => $banque,
            'agence_bancaire' => $agenceBancaire,
            'montant_ht' => number_format($montantHt, 2, ',', ' '),
            'montant_ht_lettres' => \App\Helpers\NumberToWordsHelper::toFrenchWords($montantHt) . ' DH HT',
            'tva_rate' => '20',
            'montant_tva' => number_format($montantTva, 2, ',', ' '),
            'montant_tva_lettres' => \App\Helpers\NumberToWordsHelper::toFrenchWords($montantTva) . ' DH',
            'montant_ttc' => number_format($montantTtc, 2, ',', ' '),
            'montant_ttc_lettres' => \App\Helpers\NumberToWordsHelper::toFrenchWords($montantTtc) . ' DH TTC',
            'date_acte' => date('d/m/Y'),
        ];
    }

    private function buildMarcheDefinitifData(Aoo $aoo, ?int $fournisseurId = null): array
    {
        $base = $this->buildActeEngagementData($aoo, $fournisseurId);

        $marche = Marche::where('aoo_id', $aoo->id)->first();
        $numMarche = $marche?->num_marche ?: ('07/' . date('Y') . '/DRCA-RSK');

        $montantTtc = (float) str_replace([' ', ','], ['', '.'], $base['montant_ttc']);
        $montantHt = (float) str_replace([' ', ','], ['', '.'], $base['montant_ht']);
        $montantTva = (float) str_replace([' ', ','], ['', '.'], $base['montant_tva']);

        $items = [];
        if ($marche && $marche->bordereauItems->count() > 0) {
            foreach ($marche->bordereauItems as $idx => $item) {
                $puHt = round((float) $item->prix_unitaire_ttc / 1.20, 2);
                $items[] = [
                    'numero' => $idx + 1,
                    'designation' => $item->lotItem?->designation ?: 'Prestation de formation et logistique',
                    'unite' => $item->lotItem?->unite ?: 'Journée',
                    'quantite' => $item->lotItem?->quantite ?: 16,
                    'pu_ht' => $puHt,
                ];
            }
        }

        return array_merge($base, [
            'num_marche' => $numMarche,
            'delai_execution' => $aoo->delai_execution ?: 12,
            'montant_ht' => $montantHt,
            'montant_tva' => $montantTva,
            'montant_ttc' => $montantTtc,
            'items' => $items,
        ]);
    }

    private function buildDecisionApprobationData(Aoo $aoo, ?int $fournisseurId = null): array
    {
        $base = $this->buildMarcheDefinitifData($aoo, $fournisseurId);

        $marche = Marche::where('aoo_id', $aoo->id)->first();
        $numDecision = $marche?->num_decision ?: '01/2026';
        $dateApprobation = $marche?->date_approbation ? $marche->date_approbation->format('d/m/Y') : date('d/m/Y');
        $exercice = $marche?->exercice ?: date('Y');
        $cautionDefinitive = $marche?->montant_caution_definitive ?: round($base['montant_ttc'] * 0.03, 2);

        return array_merge($base, [
            'num_decision' => $numDecision,
            'date_approbation' => $dateApprobation,
            'exercice' => $exercice,
            'montant_caution_definitive' => $cautionDefinitive,
        ]);
    }

    private function buildOsCommencementData(Aoo $aoo, ?int $fournisseurId = null): array
    {
        $base = $this->buildDecisionApprobationData($aoo, $fournisseurId);

        $marche = Marche::where('aoo_id', $aoo->id)->first();
        $numOs = $marche?->os_numero ?: '03/2026';
        $dateOs = $marche?->os_date_signature ? $marche->os_date_signature->format('d/m/Y') : date('d/m/Y');
        $dateEffet = $marche?->date_effet_os ? $marche->date_effet_os->format('d/m/Y') : ($marche?->os_date_effet ? $marche->os_date_effet->format('d/m/Y') : date('d/m/Y'));

        return array_merge($base, [
            'num_os' => $numOs,
            'date_os' => $dateOs,
            'date_effet_commencement' => $dateEffet,
        ]);
    }
    public function telechargerRapportPrestation($id)
    {
        $aoo = Aoo::with(['concurrents.fournisseur', 'lots.items', 'marches'])->findOrFail($id);
        
        // Retrieve Marche if it exists
        $marche = $aoo->marches->first();
        
        // Find Attributaire (using explicit retenu if any, or default to 1st Admis if in Commission phase)
        $attributaire = $aoo->concurrents->whereIn('statut_analyse', ['retenu', 'retenu_provisoire'])->first();
        if (!$attributaire) {
            $attributaire = $aoo->concurrents->where('classement', 1)->first();
        }

        // Calculate Estimation
        $estimationHT = 0;
        foreach($aoo->lots as $lot) {
            foreach($lot->items as $item) {
                $estimationHT += (float) $item->prix_unitaire_ht * (float) $item->quantite;
            }
        }
        $estimationTTC = $estimationHT * 1.20; // Default 20% TVA if not specified at lot level
        
        // Amount (from Marche if exists, else from Attributaire)
        $montant = $marche ? $marche->montant : ($attributaire ? $attributaire->montant_ttc : 0);
        
        // Convert Amount to words
        $montantLettres = '';
        if ($montant > 0 && class_exists('\NumberFormatter')) {
            $formatter = new \NumberFormatter('fr', \NumberFormatter::SPELLOUT);
            $entier = floor($montant);
            $decimal = round(($montant - $entier) * 100);
            $montantLettres = $formatter->format($entier) . ' dirhams';
            if ($decimal > 0) {
                $montantLettres .= ' et ' . $formatter->format($decimal) . ' centimes';
            }
        }
        
        $data = [
            'aoo' => $aoo,
            'marche' => $marche,
            'attributaire' => $attributaire,
            'estimationTTC' => $estimationTTC,
            'montant' => $montant,
            'montantLettres' => $montantLettres
        ];

        $html = view('pdf.aoo.rapport_prestation', $data)->render();
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 12,
            'margin_bottom' => 12,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);
        $mpdf->WriteHTML($html);
        $output = $mpdf->Output('', 'S');
        $num_marche = $marche ? str_replace('/', '_', $marche->num_marche) : 'DRAFT';
        $filename = 'Rapport_Prestation_' . $num_marche . '.pdf';
        
        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
