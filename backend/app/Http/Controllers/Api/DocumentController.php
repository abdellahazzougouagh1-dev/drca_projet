<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function generate(Request $request, $consultationId, $type)
    {
        $consultation = \App\Models\Consultation::with(['budget', 'engagement.fournisseur', 'suiviExecution', 'receptions', 'liquidation'])->findOrFail($consultationId);
        
        $documentNames = [
            'fiche_consultation' => 'Fiche de consultation',
            'note_interne' => 'Note interne',
            'reglement_consultation' => 'Règlement de consultation',
            'estimation_administrative' => 'Estimation administrative',
            'bordereau_prix' => 'Bordereau des prix unitaires',
            'detail_estimatif' => 'Détail estimatif',
            
            'lettre_consultation' => 'Lettre de consultation',
            'demande_devis' => 'Demande de devis',
            
            'decision_commission' => 'Décision de commission',
            'convocation' => 'Convocation',
            'liste_presence' => 'Liste de présence',
            'pv_ouverture' => "PV d'ouverture des plis",
            'devis_contradictoires' => 'Devis contradictoires',
            'decision_attribution' => "Décision d'attribution",
            'note_recours' => 'Note de recours à la concurrence',
            
            'bon_commande' => 'Bon de commande',
            'convention' => 'Convention',
            'fiche_engagement' => "Fiche d'engagement",
            'os_notification' => 'OS de notification',
            'os_commencement' => 'OS de commencement',
            
            'pv_reception' => 'PV de réception',
            'attestation_bonne_execution' => 'Attestation de bonne exécution',
            
            'ordre_imputation' => "Ordre d'imputation",
            'ordre_paiement' => 'Ordre de paiement',
            'ordre_virement' => 'Ordre de virement',
            'bulletin_decompte' => 'Bulletin de décompte',
            'etat_liquidation' => 'État de liquidation',
        ];

        $docTitle = $documentNames[$type] ?? 'Document Administratif';

        $viewName = view()->exists("documents.{$type}") ? "documents.{$type}" : 'documents.template';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($viewName, [
            'consultation' => $consultation,
            'docTitle' => $docTitle,
            'type' => $type
        ]);

        return $pdf->download("{$type}_{$consultation->numero_consultation}.pdf");
    }

    public function downloadZip($consultationId)
    {
        $consultation = \App\Models\Consultation::with(['budget', 'engagement.fournisseur'])->findOrFail($consultationId);

        $documentsToGenerate = [
            '01_Programmation' => ['fiche_consultation' => 'Fiche de consultation', 'note_interne' => 'Note interne', 'reglement_consultation' => 'Règlement de consultation', 'estimation_administrative' => 'Estimation administrative', 'bordereau_prix' => 'Bordereau des prix unitaires', 'detail_estimatif' => 'Détail estimatif'],
            '02_Consultation' => ['lettre_consultation' => 'Lettre de consultation', 'demande_devis' => 'Demande de devis'],
            '03_Commission' => ['decision_commission' => 'Décision de commission', 'convocation' => 'Convocation', 'liste_presence' => 'Liste de présence', 'pv_ouverture' => "PV d'ouverture des plis", 'devis_contradictoires' => 'Devis contradictoires', 'decision_attribution' => "Décision d'attribution", 'note_recours' => 'Note de recours à la concurrence'],
            '04_Engagement' => ['bon_commande' => 'Bon de commande', 'convention' => 'Convention', 'fiche_engagement' => "Fiche d'engagement", 'os_notification' => 'OS de notification', 'os_commencement' => 'OS de commencement'],
            '05_Reception' => ['pv_reception' => 'PV de réception', 'attestation_bonne_execution' => 'Attestation de bonne exécution'],
            '06_Liquidation' => ['ordre_imputation' => "Ordre d'imputation", 'ordre_paiement' => 'Ordre de paiement', 'ordre_virement' => 'Ordre de virement', 'bulletin_decompte' => 'Bulletin de décompte', 'etat_liquidation' => 'État de liquidation']
        ];

        $zip = new \ZipArchive();
        $zipFileName = "Dossier_Administratif_{$consultation->numero_consultation}.zip";
        $zipPath = storage_path("app/public/" . $zipFileName);

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            foreach ($documentsToGenerate as $folder => $docs) {
                $zip->addEmptyDir($folder);
                foreach ($docs as $type => $title) {
                    $viewName = view()->exists("documents.{$type}") ? "documents.{$type}" : 'documents.template';
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($viewName, [
                        'consultation' => $consultation,
                        'docTitle' => $title,
                        'type' => $type
                    ]);
                    $pdfContent = $pdf->output();
                    $zip->addFromString("{$folder}/{$title}.pdf", $pdfContent);
                }
            }
            $zip->close();
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
