<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Support\BonCommandeDocumentHelper;
use App\Support\MontantEnLettres;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use ZipArchive;

class DocumentController extends Controller
{
    public function generate(Request $request, $consultationId, $type)
    {
        $consultation = $this->loadConsultation($consultationId);

        $docTitle = $this->documentNames()[$type] ?? 'Document Administratif';
        $viewName = $this->viewName($type);

        $pdf = Pdf::loadView($viewName, [
            'consultation' => $consultation,
            'docTitle' => $docTitle,
            'type' => $type,
            'documentData' => [],
            'montantEnLettres' => $this->montantEnLettres($consultation),
            'doc' => BonCommandeDocumentHelper::build($consultation, [], $this->montantEnLettres($consultation), $type),
        ]);

        $safeNumero = str_replace(['/', '\\'], '_', $consultation->numero_consultation ?? 'dossier');
        return $pdf->download("{$type}_{$safeNumero}.pdf");
    }

    public function generateWithData(Request $request, $consultationId, $type)
    {
        $consultation = $this->loadConsultation($consultationId);
        $documentData = $request->validate([
            'document_data' => 'nullable|array',
        ])['document_data'] ?? [];

        $docTitle = $this->documentNames()[$type] ?? 'Document Administratif';

        $pdf = Pdf::loadView($this->viewName($type), [
            'consultation' => $consultation,
            'docTitle' => $docTitle,
            'type' => $type,
            'documentData' => $documentData,
            'montantEnLettres' => $this->montantEnLettres($consultation),
            'doc' => BonCommandeDocumentHelper::build($consultation, $documentData, $this->montantEnLettres($consultation), $type),
        ]);

        $safeNumero = str_replace(['/', '\\'], '_', $consultation->numero_consultation ?? 'dossier');
        return $pdf->download("{$type}_{$safeNumero}.pdf");
    }


    private function loadConsultation($consultationId): Consultation
    {
        return Consultation::with([
            'budget',
            'prestations',
            'offres.fournisseur',
            'engagement.fournisseur',
            'suiviExecution',
            'receptions',
            'receptionCommission',
            'liquidation',
        ])->findOrFail($consultationId);
    }

    private function documentNames(): array
    {
        return [
            'fiche_consultation' => 'Fiche de consultation',
            'note_interne' => 'Note interne',
            'avis_achat' => "Avis d'achat",
            'reglement_consultation' => 'Reglement de consultation',
            'estimation_administrative' => 'Estimation administrative',
            'bordereau_prix' => 'Bordereau des prix unitaires',
            'detail_estimatif' => 'Detail estimatif',
            'lettre_consultation' => 'Lettre de consultation',
            'demande_devis' => 'Demande de devis',
            'decision_commission' => 'Decision de commission',
            'decision_commission_ouverture' => "Decision de la commission d'ouverture",
            'convocation' => 'Convocation',
            'liste_presence' => 'Liste de presence',
            'pv_ouverture' => "PV d'ouverture des plis",
            'pv_ouverture_attribution' => "PV d'ouverture et d'attribution",
            'devis_contradictoires' => 'Devis contradictoires',
            'decision_attribution' => "Decision d'attribution",
            'note_recours' => 'Note de recours a la concurrence',
            'bon_commande' => 'Bon de commande',
            'convention' => 'Convention',
            'fiche_engagement' => "Fiche d'engagement budgetaire",
            'ordre_commande' => 'Ordre de commande',
            'os_notification' => 'OS de notification',
            'os_commencement' => 'OS de commencement',
            'accuse_reception' => 'Accuse de reception',
            'decision_commission_reception' => 'Decision commission de suivi et de reception',
            'pv_reception' => 'PV de reception',
            'attestation_bonne_execution' => 'Attestation de bonne execution',
            'ordre_imputation' => "Ordre d'imputation",
            'ordre_imputation_paiement_virement' => "Ordre d'imputation, paiement et virement",
            'ordre_paiement' => 'Ordre de paiement',
            'ordre_virement' => 'Ordre de virement',
            'bulletin_decompte' => 'Bulletin de decompte',
            'etat_liquidation' => 'Etat de liquidation',
        ];
    }

    private function viewName(string $type): string
    {
        $bonCommandeDocuments = [
            'avis_achat',
            'decision_commission_ouverture',
            'pv_ouverture_attribution',
            'bon_commande',
            'fiche_engagement',
            'ordre_commande',
            'accuse_reception',
            'decision_commission_reception',
            'pv_reception',
            'ordre_imputation',
            'ordre_paiement',
            'ordre_virement',
            'ordre_imputation_paiement_virement',
        ];

        if (in_array($type, $bonCommandeDocuments, true) && view()->exists("documents.{$type}")) {
            return "documents.{$type}";
        }

        return view()->exists("documents.{$type}") ? "documents.{$type}" : 'documents.template';
    }

    private function montantEnLettres(Consultation $consultation): string
    {
        $total = $consultation->prestations->sum('montant_ttc')
            ?: ($consultation->budget->montant_ttc ?? 0);

        return MontantEnLettres::convert((float) $total);
    }
}
