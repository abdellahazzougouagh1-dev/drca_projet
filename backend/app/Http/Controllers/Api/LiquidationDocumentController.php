<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Liquidation;
use App\Models\Marche;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Http\Request;

class LiquidationDocumentController extends Controller
{
    private function safeFileName($prefix, $num_marche, $extension = 'pdf')
    {
        $safeNum = str_replace(['/', '\\'], '_', $num_marche);
        return "{$prefix}_{$safeNum}.{$extension}";
    }

    private function processWordTemplate($templateName, $liquidation, $prefixFileName)
    {
        $templatePath = storage_path("app/templates/{$templateName}.docx");
        
        if (!file_exists($templatePath)) {
            return null; // Return null to fallback to PDF or error
        }

        $templateProcessor = new TemplateProcessor($templatePath);
        $marche = $liquidation->marche;

        // General fields
        $templateProcessor->setValue('num_marche', $marche->num_marche ?? '');
        $templateProcessor->setValue('objet_marche', $marche->objet_marche ?? '');
        $templateProcessor->setValue('lot', $marche->lot ?? 'Lot Unique');
        
        // Fournisseur
        $templateProcessor->setValue('titulaire', $marche->titulaire ?? '');
        $templateProcessor->setValue('raison_sociale', $marche->fournisseur->raison_sociale ?? $marche->titulaire ?? '');
        $templateProcessor->setValue('gerant', $marche->fournisseur->representant ?? '');
        
        // Liquidation fields
        $templateProcessor->setValue('num_decompte', $liquidation->num_decompte ?? '');
        $templateProcessor->setValue('type_decompte', ucfirst($liquidation->type_decompte ?? ''));
        $templateProcessor->setValue('date_decompte', $liquidation->date_decompte ? \Carbon\Carbon::parse($liquidation->date_decompte)->format('d/m/Y') : '');
        $templateProcessor->setValue('num_facture', $liquidation->num_facture ?? '');
        $templateProcessor->setValue('date_facture', $liquidation->date_facture ? \Carbon\Carbon::parse($liquidation->date_facture)->format('d/m/Y') : '');
        $templateProcessor->setValue('reference_service_fait', $liquidation->reference_service_fait ?? '');
        $templateProcessor->setValue('reference_pv_reception', $liquidation->reference_pv_reception ?? '');
        $templateProcessor->setValue('date_reception', $liquidation->date_reception ? \Carbon\Carbon::parse($liquidation->date_reception)->format('d/m/Y') : '');

        // Finances
        $templateProcessor->setValue('montant_ht', number_format($liquidation->montant_ht ?? 0, 2, ',', ' '));
        $templateProcessor->setValue('montant_tva', number_format($liquidation->montant_tva ?? 0, 2, ',', ' '));
        $templateProcessor->setValue('montant_ttc', number_format($liquidation->montant_ttc ?? 0, 2, ',', ' '));
        $templateProcessor->setValue('retenue_garantie', number_format($liquidation->retenue_garantie ?? 0, 2, ',', ' '));
        $templateProcessor->setValue('penalites_retard', number_format($liquidation->penalites_retard ?? 0, 2, ',', ' '));
        $templateProcessor->setValue('avances_a_recuperer', number_format($liquidation->avances_a_recuperer ?? 0, 2, ',', ' '));
        $templateProcessor->setValue('retenues', number_format($liquidation->retenues ?? 0, 2, ',', ' '));
        $templateProcessor->setValue('net_a_payer', number_format($liquidation->net_a_payer ?? 0, 2, ',', ' '));
        
        $templateProcessor->setValue('montant_ttc_lettres', \App\Helpers\NumberToWordsHelper::toFrenchWords($liquidation->montant_ttc ?? 0));
        $templateProcessor->setValue('net_a_payer_lettres', \App\Helpers\NumberToWordsHelper::toFrenchWords($liquidation->net_a_payer ?? 0));

        // Lignes de liquidation (Décompte)
        if (isset($liquidation->lignes) && count($liquidation->lignes) > 0) {
            try {
                $values = [];
                $i = 1;
                foreach ($liquidation->lignes as $ligne) {
                    $values[] = [
                        'n' => $i++,
                        'designation' => $ligne->designation ?? '',
                        'unite' => $ligne->unite ?? '',
                        'quantite_prevue' => $ligne->quantite_prevue ?? '',
                        'quantite_executee' => $ligne->quantite_executee ?? '',
                        'prix_unitaire_ht' => number_format($ligne->prix_unitaire_ht ?? 0, 2, ',', ' '),
                        'ligne_montant_ht' => number_format($ligne->montant_ht ?? 0, 2, ',', ' ')
                    ];
                }
                $templateProcessor->cloneRowAndSetValues('n', $values);
            } catch (\Exception $e) {
                // Ignore if the row tag 'n' doesn't exist in the template
            }
        }

        // Output to a temp file
        $tempPath = storage_path('app/temp/' . uniqid('liq_') . '.docx');
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0777, true);
        }
        $templateProcessor->saveAs($tempPath);

        return response()->download($tempPath, $this->safeFileName($prefixFileName, $marche->num_marche, 'docx'))->deleteFileAfterSend(true);
    }

    public function generate(Request $request, $marcheId, $liquidationId, $type)
    {
        $liquidation = Liquidation::with([
            'marche.fournisseur', 
            'marche.aoo.notificationLigne', 
            'marche.lot', 
            'marche.notificationLigne', 
            'lignes'
        ])->where('marche_id', $marcheId)->findOrFail($liquidationId);
        $marche = $liquidation->marche;

        $data = [
            'liquidation' => $liquidation,
            'marche' => $marche,
            'fournisseur' => $marche->fournisseur,
        ];

        $isPreview = $request->has('preview') || $request->query('preview');

        switch ($type) {
            case 'decision_reception':
                $pdf = Pdf::loadView('pdf.liquidation.decision_reception', $data);
                return $isPreview 
                    ? $pdf->stream($this->safeFileName('Decision_Reception', $marche->num_marche))
                    : $pdf->download($this->safeFileName('Decision_Reception', $marche->num_marche));

            case 'pv_reception':
                $pdf = Pdf::loadView('pdf.liquidation.pv_reception', $data);
                return $isPreview 
                    ? $pdf->stream($this->safeFileName('PV_Reception', $marche->num_marche))
                    : $pdf->download($this->safeFileName('PV_Reception', $marche->num_marche));

            case 'decompte':
                $pdf = Pdf::loadView('pdf.liquidation.decompte', $data);
                return $isPreview 
                    ? $pdf->stream($this->safeFileName('Decompte', $marche->num_marche))
                    : $pdf->download($this->safeFileName('Decompte', $marche->num_marche));

            case 'csf':
                $pdf = Pdf::loadView('pdf.liquidation.csf', $data);
                return $isPreview 
                    ? $pdf->stream($this->safeFileName('Certificat_Service_Fait', $marche->num_marche))
                    : $pdf->download($this->safeFileName('Certificat_Service_Fait', $marche->num_marche));

            case 'etat_liquidation':
                $montantTtc = (float) ($liquidation->montant_brut_ttc ?: $liquidation->montant_ttc ?: 0);
                $montantHt = (float) ($liquidation->montant_brut_ht ?: $liquidation->montant_ht ?: ($montantTtc / 1.20));
                $montantTva = (float) ($liquidation->montant_tva ?: ($montantTtc - $montantHt));
                $montantRas = (float) ($liquidation->autres_deductions ?: ($liquidation->montant_brut_ttc ? ($liquidation->montant_brut_ttc - $liquidation->net_a_payer) : 0));
                $tauxRas = ($montantTva > 0 && $montantRas > 0) ? round(($montantRas / $montantTva) * 100) . '%' : ($montantRas > 0 ? '75%' : '0%');
                $netAVerser = (float) ($liquidation->net_a_payer ?: ($montantTtc - $montantRas));

                $factureRef = null;
                if ($liquidation->num_facture) {
                    $factureRef = "Facture N°" . $liquidation->num_facture . ($liquidation->date_facture ? " du " . \Carbon\Carbon::parse($liquidation->date_facture)->format('d/m/Y') : "");
                } elseif ($liquidation->num_decompte) {
                    $factureRef = "Décompte N°" . $liquidation->num_decompte . ($liquidation->date_decompte ? " du " . \Carbon\Carbon::parse($liquidation->date_decompte)->format('d/m/Y') : "");
                } else {
                    $factureRef = "Facture N°" . ($liquidation->num_liquidation ?? '010/' . ($liquidation->exercice_budgetaire ?? date('Y'))) . " du " . \Carbon\Carbon::parse($liquidation->date_service_fait ?? now())->format('d/m/Y');
                }

                $liqData = [
                    'numMarche' => $marche->num_marche,
                    'objet' => $liquidation->objet_liquidation ?: $marche->objet_marche,
                    'factureRef' => $factureRef,
                    'beneficiaire' => $marche->fournisseur->raison_sociale ?? $marche->titulaire ?? 'Fournisseur',
                    'montantTtc' => $montantTtc,
                    'montantEnLettres' => \App\Support\MontantEnLettres::convert($montantTtc),
                    'montantHt' => $montantHt,
                    'montantTva' => $montantTva,
                    'tauxRas' => $tauxRas,
                    'montantRas' => $montantRas,
                    'netAVerser' => $netAVerser,
                    'dateLiquidation' => $liquidation->date_decompte ? \Carbon\Carbon::parse($liquidation->date_decompte)->format('d/m/Y') : ($liquidation->date_service_fait ? \Carbon\Carbon::parse($liquidation->date_service_fait)->format('d/m/Y') : date('d/m/Y')),
                    'budget' => $marche->type_budget ?? 'Investissement',
                    'exercice' => $liquidation->exercice_budgetaire ?? date('Y'),
                    'numLiquidation' => $liquidation->num_liquidation
                ];

                $pdf = Pdf::loadView('pdf.liquidation.etat_liquidation', $liqData);
                return $isPreview 
                    ? $pdf->stream($this->safeFileName('Etat_Liquidation', $marche->num_marche))
                    : $pdf->download($this->safeFileName('Etat_Liquidation', $marche->num_marche));

            case 'etat_paiement':
                $pdf = Pdf::loadView('pdf.liquidation.etat_paiement', $data);
                return $isPreview 
                    ? $pdf->stream($this->safeFileName('Etat_Paiement', $marche->num_marche))
                    : $pdf->download($this->safeFileName('Etat_Paiement', $marche->num_marche));

            case 'recapitulatif':
                $liquidations = Liquidation::where('marche_id', $marcheId)->orderBy('created_at')->get();
                $data['liquidations'] = $liquidations;
                $pdf = Pdf::loadView('pdf.liquidation.recapitulatif', $data);
                return $isPreview 
                    ? $pdf->stream($this->safeFileName('Recapitulatif_Liquidation', $marche->num_marche))
                    : $pdf->download($this->safeFileName('Recapitulatif_Liquidation', $marche->num_marche));

            case 'fiche_transmission':
                $pdf = Pdf::loadView('pdf.liquidation.fiche_transmission', $data);
                return $isPreview 
                    ? $pdf->stream($this->safeFileName('Fiche_Transmission', $marche->num_marche))
                    : $pdf->download($this->safeFileName('Fiche_Transmission', $marche->num_marche));

            default:
                return response()->json(['error' => 'Type de document invalide'], 400);
        }
    }

    public function generateMarcheDocument(Request $request, $marcheId, $type)
    {
        $marche = Marche::with(['fournisseur', 'aoo'])->findOrFail($marcheId);
        $liquidation = Liquidation::where('marche_id', $marcheId)->latest()->first();

        $data = [
            'marche' => $marche,
            'fournisseur' => $marche->fournisseur,
            'liquidation' => $liquidation,
            'aoo' => $marche->aoo,
        ];

        switch ($type) {
            case 'decision_commission':
            case 'decision_reception':
            case 'decision_nomination':
                $pdf = Pdf::loadView('pdf.liquidation.decision_reception', $data);
                if ($request->has('preview') || $request->query('preview')) {
                    return $pdf->stream($this->safeFileName('Decision_Commission_Reception', $marche->num_marche));
                }
                return $pdf->download($this->safeFileName('Decision_Commission_Reception', $marche->num_marche));

            default:
                return response()->json(['error' => 'Type de document invalide'], 400);
        }
    }
}
