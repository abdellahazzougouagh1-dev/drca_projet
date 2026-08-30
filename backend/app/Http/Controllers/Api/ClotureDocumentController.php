<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Marche;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ClotureDocumentController extends Controller
{
    private function safeFileName($prefix, $num_marche, $extension = 'pdf')
    {
        $safeNum = str_replace(['/', '\\'], '_', $num_marche);
        return "{$prefix}_{$safeNum}.{$extension}";
    }

    private function processWordTemplate($templateName, $marche, $cloture, $prefixFileName)
    {
        $templatePath = storage_path("app/templates/{$templateName}.docx");
        
        if (!file_exists($templatePath)) {
            return null; // Return null to fallback to PDF
        }

        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

        // General fields
        $templateProcessor->setValue('num_marche', $marche->num_marche ?? '');
        $templateProcessor->setValue('objet_marche', $marche->objet_marche ?? '');
        
        // Fournisseur
        $templateProcessor->setValue('titulaire', $marche->titulaire ?? '');
        $templateProcessor->setValue('raison_sociale', $marche->fournisseur->raison_sociale ?? $marche->titulaire ?? '');
        
        // Cloture data
        $templateProcessor->setValue('type_cautionnement', $cloture->type_cautionnement ?? '');
        $templateProcessor->setValue('organisme_caution', $cloture->organisme_caution ?? '');
        $templateProcessor->setValue('montant_caution', number_format($cloture->montant_caution ?? 0, 2, ',', ' '));
        $templateProcessor->setValue('reference_caution', $cloture->reference_caution ?? '');
        $templateProcessor->setValue('date_caution', $cloture->date_caution ? \Carbon\Carbon::parse($cloture->date_caution)->format('d/m/Y') : '');
        $templateProcessor->setValue('motif_mainlevee', $cloture->motif_mainlevee ?? '');
        $templateProcessor->setValue('signataire_mainlevee', $cloture->signataire_mainlevee ?? '');
        $templateProcessor->setValue('date_reception_provisoire', $cloture->date_reception_provisoire ? \Carbon\Carbon::parse($cloture->date_reception_provisoire)->format('d/m/Y') : '');
        $templateProcessor->setValue('date_reception_definitive', $cloture->date_reception_definitive ? \Carbon\Carbon::parse($cloture->date_reception_definitive)->format('d/m/Y') : '');
        $templateProcessor->setValue('signataire_certificat', $cloture->signataire_certificat ?? '');

        // Output to a temp file
        $tempPath = storage_path('app/temp/' . uniqid('cloture_') . '.docx');
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0777, true);
        }
        $templateProcessor->saveAs($tempPath);

        return response()->download($tempPath, $this->safeFileName($prefixFileName, $marche->num_marche, 'docx'))->deleteFileAfterSend(true);
    }

    public function generate(Request $request, $marcheId, $type)
    {
        $marche = Marche::with(['fournisseur', 'aoo', 'cloture', 'liquidations'])->findOrFail($marcheId);

        if (!$marche->cloture) {
            return response()->json(['error' => 'Les données de clôture n\'ont pas été enregistrées.'], 400);
        }

        if ($marche->statut !== 'cloture_validee') {
            if (!in_array($marche->statut, ['cloture_en_cours', 'cloture_validee'])) {
                return response()->json(['error' => 'Le certificat de référence ne peut pas encore être généré. Le marché doit être suffisamment exécuté/clôturé.'], 403);
            }
        }

        $totalLiquide = $marche->liquidations->whereIn('statut', ['VALIDÉE', 'TRANSMISE À L\'ORDONNANCEMENT', 'ORDONNANCÉE'])->sum('montant_ttc');
        $totalRetenues = $marche->liquidations->whereIn('statut', ['VALIDÉE', 'TRANSMISE À L\'ORDONNANCEMENT', 'ORDONNANCÉE'])->sum('retenues');

        $data = [
            'marche' => $marche,
            'fournisseur' => $marche->fournisseur,
            'cloture' => $marche->cloture,
            'finances' => [
                'total_liquide' => $totalLiquide,
                'total_retenues' => $totalRetenues,
                'reste' => $marche->montant - $totalLiquide,
            ]
        ];

        switch ($type) {
            case 'mainlevee':
                $wordResponse = $this->processWordTemplate('mainlevee', $marche, $marche->cloture, 'Mainlevee');
                if ($wordResponse) return $wordResponse;

                $pdf = Pdf::loadView('pdf.cloture.mainlevee', $data);
                return $pdf->download($this->safeFileName('Mainlevee', $marche->num_marche));

            case 'certificat_reference':
                $wordResponse = $this->processWordTemplate('certificat_reference', $marche, $marche->cloture, 'Certificat_Reference');
                if ($wordResponse) return $wordResponse;

                $pdf = Pdf::loadView('pdf.cloture.certificat_reference', $data);
                return $pdf->download($this->safeFileName('Certificat_Reference', $marche->num_marche));

            default:
                return response()->json(['error' => 'Type de document invalide'], 400);
        }
    }
}
