<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Marche;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\TemplateProcessor;

class DocumentController extends Controller
{
    private function safeFileName($prefix, $num_marche, $extension = 'pdf')
    {
        $safeNum = str_replace(['/', '\\'], '_', $num_marche);
        return "{$prefix}_{$safeNum}.{$extension}";
    }

    private function processWordTemplate($templateName, $marche, $prefixFileName)
    {
        $templatePath = storage_path("app/templates/{$templateName}.docx");
        
        if (!file_exists($templatePath)) {
            return null; // Return null to fallback to PDF
        }

        $templateProcessor = new TemplateProcessor($templatePath);

        $montantHT = $marche->montant_ht ?? 0;
        $montantTVA = $marche->montant_tva ?? 0;
        $montantTTC = $marche->montant ?? 0;
        $tauxTVA = $marche->taux_tva ?? 20;

        $variables = [
            // Général
            'num_marche' => $marche->num_marche ?? '',
            'numero_marche' => $marche->num_marche ?? '', // Alias
            'objet_marche' => $marche->objet_marche ?? '',
            'lot' => $marche->lot ?? 'Lot Unique',
            'delai_execution' => $marche->delai_execution ?? '',
            'agent_suivi' => $marche->agent_suivi ?? '',
            
            // AOO
            'num_aoo' => $marche->aoo->num_aoo ?? '',
            'numero_ao' => $marche->aoo->num_aoo ?? '', // Alias
            'date_ao' => $marche->aoo && $marche->aoo->date_ouverture ? \Carbon\Carbon::parse($marche->aoo->date_ouverture)->format('d/m/Y') : '',
            'date_ouverture_aoo' => $marche->aoo && $marche->aoo->date_ouverture ? \Carbon\Carbon::parse($marche->aoo->date_ouverture)->format('d/m/Y') : '',

            // Fournisseur
            'titulaire' => $marche->titulaire ?? '',
            'raison_sociale' => $marche->fournisseur->raison_sociale ?? $marche->titulaire ?? '',
            'nom_societe' => $marche->fournisseur->raison_sociale ?? $marche->titulaire ?? '', // Alias
            'gerant' => $marche->fournisseur->representant ?? '',
            'nom_gerant' => $marche->fournisseur->representant ?? '', // Alias
            'qualite_gerant' => $marche->qualite_gerant ?? 'Gérant',
            'adresse' => $marche->fournisseur->adresse ?? '',
            'adresse_societe' => $marche->fournisseur->adresse ?? '', // Alias
            'ville' => $marche->fournisseur->ville ?? '',
            'ville_societe' => $marche->fournisseur->ville ?? '', // Alias
            'ice' => $marche->fournisseur->ice ?? '',
            'rc' => $marche->fournisseur->rc ?? '',
            'patente' => $marche->fournisseur->patente ?? '',
            'cnss' => $marche->fournisseur->cnss ?? '',
            'banque' => $marche->fournisseur->banque ?? '',
            'agence_bancaire' => $marche->fournisseur->agence_bancaire ?? '',
            'rib' => $marche->fournisseur->rib ?? '',

            // Finances
            'montant_ht' => number_format($montantHT, 2, ',', ' '),
            'montant_tva' => number_format($montantTVA, 2, ',', ' '),
            'montant_ttc' => number_format($montantTTC, 2, ',', ' '),
            'taux_tva' => $tauxTVA,
            'montant_ht_lettres' => \App\Helpers\NumberToWordsHelper::toFrenchWords($montantHT),
            'montant_ttc_lettres' => \App\Helpers\NumberToWordsHelper::toFrenchWords($montantTTC),
            'montant_caution' => number_format((float)($marche->montant_caution_definitive ?? 0), 2, ',', ' '),
            'montant_caution_lettres' => \App\Helpers\NumberToWordsHelper::toFrenchWords((float)($marche->montant_caution_definitive ?? 0)),

            // Décision & Dates Approbation
            'num_decision' => $marche->num_decision ?? '',
            'date_approbation' => $marche->date_approbation ? \Carbon\Carbon::parse($marche->date_approbation)->format('d/m/Y') : '',
            'date_notification' => $marche->date_notification_marche ? \Carbon\Carbon::parse($marche->date_notification_marche)->format('d/m/Y') : '',
            'date_reception' => date('d/m/Y'), // Accusé réception OS

            // OS
            'os_numero' => $marche->os_numero ?? '',
            'numero_os' => $marche->os_numero ?? '', // Alias
            'os_date_signature' => $marche->os_date_signature ? \Carbon\Carbon::parse($marche->os_date_signature)->format('d/m/Y') : '',
            'date_os' => $marche->os_date_signature ? \Carbon\Carbon::parse($marche->os_date_signature)->format('d/m/Y') : '', // Alias
            'os_date_effet' => $marche->os_date_effet ? \Carbon\Carbon::parse($marche->os_date_effet)->format('d/m/Y') : '',
            
            // OS Arrêt
            'os_arret_numero' => $marche->os_arret_numero ?? '',
            'os_arret_date_signature' => $marche->os_arret_date_signature ? \Carbon\Carbon::parse($marche->os_arret_date_signature)->format('d/m/Y') : '',
            'os_arret_date_effet' => $marche->os_arret_date_effet ? \Carbon\Carbon::parse($marche->os_arret_date_effet)->format('d/m/Y') : '',
            'os_arret_motif' => $marche->os_arret_motif ?? '',

            // OS Reprise
            'os_reprise_numero' => $marche->os_reprise_numero ?? '',
            'os_reprise_date_signature' => $marche->os_reprise_date_signature ? \Carbon\Carbon::parse($marche->os_reprise_date_signature)->format('d/m/Y') : '',
            'os_reprise_date_effet' => $marche->os_reprise_date_effet ? \Carbon\Carbon::parse($marche->os_reprise_date_effet)->format('d/m/Y') : '',
        ];

        foreach ($variables as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        // Output to a temp file
        $tempPath = storage_path('app/temp/' . uniqid('doc_') . '.docx');
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0777, true);
        }
        $templateProcessor->saveAs($tempPath);

        return response()->download($tempPath, $this->safeFileName($prefixFileName, $marche->num_marche, 'docx'))->deleteFileAfterSend(true);
    }

    public function generateActe($id)
    {
        $marche = Marche::with(['aoo', 'fournisseur', 'lot'])->findOrFail($id);
        
        $pdf = Pdf::loadView('pdf.acte_engagement', compact('marche'));
        return $pdf->download($this->safeFileName('Acte_Engagement', $marche->num_marche));
    }

    public function generateNotification($id)
    {
        $marche = Marche::with(['aoo', 'fournisseur', 'lot'])->findOrFail($id);

        if (!$marche->date_approbation) {
            return response()->json(['message' => 'La date d\'approbation est obligatoire.'], 400);
        }

        $pdf = Pdf::loadView('pdf.notification_approbation', compact('marche'));
        return $pdf->download($this->safeFileName('Notification_Approbation', $marche->num_marche));
    }

    public function generateOs($id)
    {
        $marche = Marche::with(['aoo', 'fournisseur', 'lot'])->findOrFail($id);

        if (!$marche->date_notification_marche) {
            return response()->json(['message' => 'La date de notification est obligatoire pour générer l\'OS.'], 400);
        }

        $pdf = Pdf::loadView('pdf.os_commencement', compact('marche'));
        return $pdf->download($this->safeFileName('OS_Commencement', $marche->num_marche));
    }

    public function generateOsArretReprise($id)
    {
        $marche = Marche::with(['aoo', 'fournisseur', 'lot'])->findOrFail($id);

        if ($marche->os_arret_numero && !$marche->os_reprise_numero) {
             $pdf = Pdf::loadView('pdf.os_arret', compact('marche'));
             return $pdf->download($this->safeFileName('OS_Arret', $marche->num_marche));
        } elseif ($marche->os_reprise_numero) {
             $pdf = Pdf::loadView('pdf.os_reprise', compact('marche'));
             return $pdf->download($this->safeFileName('OS_Reprise', $marche->num_marche));
        }

        return response()->json(['message' => 'Veuillez remplir les informations d\'arrêt ou de reprise avant de générer le document.'], 400);
    }

    public function generateBordereau($id)
    {
        $marche = Marche::with(['aoo', 'fournisseur', 'lot', 'bordereauItems.lotItem', 'lot.items'])->findOrFail($id);

        $wordResponse = $this->processWordTemplate('bordereau', $marche, 'Bordereau');
        if ($wordResponse) return $wordResponse;

        $items = $marche->bordereauItems;
        $relationLot = $marche->getRelation('lot');
        
        if ($items->isEmpty() && $relationLot && $relationLot->items) {
            $items = $relationLot->items->map(function ($lotItem) {
                return (object) [
                    'lotItem' => $lotItem,
                    'prix_unitaire_attributaire' => $lotItem->prix_unitaire_ht,
                    'montant_ht' => $lotItem->montant_ht
                ];
            });
        }

        $pdf = Pdf::loadView('documents.bordereau.template', compact('marche', 'items', 'relationLot'));
        $pdf->setPaper('A4', 'portrait');

        $directory = storage_path('app/generated/bordereaux');
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }
        
        $safeNumMarche = str_replace(['/', '\\'], '_', $marche->num_marche ?? $marche->id);
        $fileName = 'Bordereau_' . $safeNumMarche . '.pdf';
        
        $filePath = $directory . '/' . $fileName;
        $pdf->save($filePath);

        return $pdf->download($fileName);
    }

    public function generateCps($id)
    {
        $marche = Marche::with(['aoo', 'fournisseur', 'lot', 'bordereauItems.lotItem'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.marche.cps', compact('marche'));
        return $pdf->download($this->safeFileName('CPS', $marche->num_marche));
    }

    public function generateContrat($id)
    {
        $marche = Marche::with(['aoo', 'fournisseur', 'lot', 'bordereauItems.lotItem'])->findOrFail($id);

        if ($marche->chemin_cps && \Illuminate\Support\Facades\Storage::exists($marche->chemin_cps)) {
            try {
                $outputPath = \App\Services\AooDocumentGenerationService::generateMarcheFromCps($marche);
                return response()->download($outputPath, $this->safeFileName('Marche_Final', $marche->num_marche) . '.docx', [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Erreur lors de la génération du marché : ' . $e->getMessage()], 500);
            }
        }

        // Fallback: générer le PDF par défaut si aucun CPS n'a été importé
        $pdf = Pdf::loadView('pdf.marche.contrat_marche', compact('marche'));
        return $pdf->download($this->safeFileName('Marche_Final', $marche->num_marche) . '.pdf');
    }

    public function generateDesignationAgent($id)
    {
        $marche = Marche::with(['aoo', 'fournisseur', 'lot'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.marche.designation_agent_suivi', compact('marche'));
        return $pdf->download($this->safeFileName('Designation_Agent', $marche->num_marche));
    }
}
