<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\DocumentModele;
use App\Models\DocumentGenere;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Storage;
use App\Support\MontantEnLettres;

class DocumentGenerationService
{
    public static function generate(Consultation $consultation, string $typeDocument, array $extraData = []): ?DocumentGenere
    {
        $templatePath = storage_path('app/templates/' . $typeDocument . '.docx');
        
        if (!file_exists($templatePath)) {
            if (!Storage::exists('templates')) {
                Storage::makeDirectory('templates');
            }
            throw new \Exception("Fichier modèle introuvable. Veuillez placer le modèle dans: storage/app/templates/{$typeDocument}.docx");
        }

        $templateProcessor = new TemplateProcessor($templatePath);

        self::fillConsultationData($templateProcessor, $consultation);

        if ($typeDocument === 'estimation' || $typeDocument === 'bordereau') {
            self::fillEstimationData($templateProcessor, $consultation);
        } elseif ($typeDocument === 'rc') {
            // RC n'a besoin que des données globales pour l'instant
        } elseif ($typeDocument === 'avis_fr') {
            self::fillAvisFrData($templateProcessor, $consultation);
        } elseif ($typeDocument === 'avis_ar') {
            self::fillAvisArData($templateProcessor, $consultation);
        } elseif ($typeDocument === 'lettre_ecartement') {
            self::fillLettreEcartementData($templateProcessor, $consultation, $extraData);
        }

        $outputDir = 'documents_generes/consultation/' . $consultation->id;
        $absoluteOutputDir = storage_path('app/' . $outputDir);
        if (!is_dir($absoluteOutputDir)) {
            mkdir($absoluteOutputDir, 0777, true);
        }

        $fileName = $typeDocument . '_' . $consultation->numero_consultation . '_' . time() . '.docx';
        $fileName = str_replace(['/', '\\'], '_', $fileName);
        $outputPath = storage_path('app/' . $outputDir . '/' . $fileName);

        $templateProcessor->saveAs($outputPath);

        $document = new DocumentGenere([
            'consultation_id' => $consultation->id,
            'type_document' => $typeDocument,
            'nom_fichier' => $fileName,
            'chemin_fichier' => $outputDir . '/' . $fileName,
            'chemin_pdf' => '', // Avoid DB strict mode error
            'version' => DocumentGenere::where('consultation_id', $consultation->id)->where('type_document', $typeDocument)->count() + 1,
            'statut' => 'généré',
            'utilisateur_id' => auth()->id() ?? 1
        ]);
        $document->save();

        return $document;
    }

    private static function fillConsultationData(TemplateProcessor $processor, Consultation $consultation)
    {
        $processor->setValue('numero_ao', $consultation->numero_consultation ?? '');
        $processor->setValue('objet_ao', $consultation->objet_consultation ?? '');
        $processor->setValue('objet_ao_ar', $consultation->objet_consultation_ar ?? '');
        
        // Use date_reunion / heure_reunion for opening dates if available
        $dateOuverture = $consultation->date_reunion ? \Carbon\Carbon::parse($consultation->date_reunion)->format('d/m/Y') : ($consultation->date_consultation ? \Carbon\Carbon::parse($consultation->date_consultation)->format('d/m/Y') : '');
        $processor->setValue('date_ouverture', $dateOuverture);
        $processor->setValue('heure_ouverture', $consultation->heure_reunion ? \Carbon\Carbon::parse($consultation->heure_reunion)->format('H:i') : '');
        $processor->setValue('lieu_ouverture', $consultation->lieu_reunion ?? ($consultation->lieu_consultation ?? ''));
        $processor->setValue('lieu_ouverture_ar', $consultation->lieu_reunion_ar ?? '');
    }

    private static function fillEstimationData(TemplateProcessor $processor, Consultation $consultation)
    {
        $consultation->loadMissing(['prestations', 'budget']);
        
        $items = $consultation->prestations;
        if ($items->count() > 0) {
            try {
                $processor->cloneRow('numero_prix', $items->count());
            } catch (\Exception $e) {
                // Si la balise n'existe pas dans un tableau, on ignore
                return;
            }
            $i = 1;
            $totalHt = 0;
            $totalTva = 0;
            
            foreach ($items as $item) {
                $processor->setValue("numero_prix#{$i}", $i);
                $processor->setValue("designation#{$i}", $item->designation);
                $processor->setValue("unite#{$i}", $item->unite);
                $processor->setValue("quantite#{$i}", $item->quantite);
                $processor->setValue("prix_unitaire_ht#{$i}", number_format($item->prix_unitaire_ht, 2, ',', ' '));
                
                $montantHt = $item->quantite * $item->prix_unitaire_ht;
                $processor->setValue("montant_ht#{$i}", number_format($montantHt, 2, ',', ' '));
                
                $totalHt += $montantHt;
                $totalTva += $montantHt * ($item->tva / 100);
                
                $i++;
            }

            $totalTtc = $totalHt + $totalTva;

            $processor->setValue('total_ht', number_format($totalHt, 2, ',', ' '));
            $processor->setValue('total_tva', number_format($totalTva, 2, ',', ' '));
            $processor->setValue('total_ttc', number_format($totalTtc, 2, ',', ' '));
            $processor->setValue('montant_lettres', class_exists(MontantEnLettres::class) ? MontantEnLettres::convert($totalTtc) : '');
        }
    }

    private static function fillAvisFrData(TemplateProcessor $processor, Consultation $consultation)
    {
        $consultation->loadMissing(['budget']);
        
        // Consultation doesn't have multiple lots typically, so we fake a single lot logic for the template
        try {
            $processor->cloneBlock('LOT_BLOCK', 1, true, true);
        } catch (\Exception $e) {
            // Ignorer si la balise n'existe pas
        }
        
        $estimation = $consultation->budget ? $consultation->budget->montant_estimatif_ht * (1 + ($consultation->budget->tva/100)) : 0;
        
        $processor->setValue("lot_numero#1", "Lot unique");
        $processor->setValue("lot_objet#1", $consultation->objet_consultation);
        $processor->setValue("lot_estimation#1", number_format($estimation, 2, ',', ' '));
        $processor->setValue("lot_estimation_lettres#1", class_exists(MontantEnLettres::class) ? MontantEnLettres::convert($estimation) : '');
        $processor->setValue("lot_cautionnement#1", number_format($consultation->cautionnement_provisoire ?? 0, 2, ',', ' '));
        $processor->setValue("lot_cautionnement_lettres#1", class_exists(MontantEnLettres::class) ? MontantEnLettres::convert($consultation->cautionnement_provisoire ?? 0) : '');
    }

    private static function fillAvisArData(TemplateProcessor $processor, Consultation $consultation)
    {
        $consultation->loadMissing(['budget']);
        
        try {
            $processor->cloneBlock('LOT_BLOCK_AR', 1, true, true);
        } catch (\Exception $e) {
            // Ignorer si la balise n'existe pas
        }
        
        $estimation = $consultation->budget ? $consultation->budget->montant_estimatif_ht * (1 + ($consultation->budget->tva/100)) : 0;

        $processor->setValue("lot_numero_ar#1", "حصة فريدة");
        $processor->setValue("lot_objet_ar#1", $consultation->objet_consultation_ar ?? '');
        $processor->setValue("lot_estimation_ar#1", number_format($estimation, 2, ',', ' '));
        $processor->setValue("lot_cautionnement_ar#1", number_format($consultation->cautionnement_provisoire ?? 0, 2, ',', ' '));
    }

    private static function fillLettreEcartementData(TemplateProcessor $processor, Consultation $consultation, array $extraData)
    {
        $processor->setValue('nom_soumissionnaire', $extraData['nom_soumissionnaire'] ?? '__________');
        $processor->setValue('motif_ecartement', $extraData['motif_ecartement'] ?? '__________');
    }
}
