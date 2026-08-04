<?php

namespace App\Services;

use App\Models\Marche;
use ZipArchive;
use Dompdf\Dompdf;

class MarcheArchiveBuilder
{
    /**
     * Build a ZIP archive containing all Marche documents.
     * Missing files are tracked and reported in a README file.
     *
     * @param Marche $marche
     * @return string Path to the generated ZIP file
     * @throws \Exception
     */
    public static function build(Marche $marche): string
    {
        // Load relationships
        $marche->loadMissing(['aoo', 'fournisseur', 'lot', 'bordereauItems.lotItem']);

        // Create temporary directory for the archive
        $tempDir = storage_path('app/temp/archives');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $archivePath = $tempDir . '/marche_' . $marche->id . '_' . time() . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Impossible de créer l\'archive ZIP pour le marché ' . $marche->num_marche);
        }

        $missingDocs = [];

        // Create folder structure
        $docsFolder = 'Documents/';
        $borderauFolder = 'Documents/Bordeaux/';
        $decisionsFolder = 'Documents/Decisions/';
        $certificatsFolder = 'Documents/Certificats/';

        // Document types to attempt to add
        $documents = [
            'Rapport de Présentation' => 'presentation',
            'Bordereau des Prix' => 'bordereau',
            'Décision d\'Attribution' => 'decision-attribution',
            'PV Réception Provisoire' => 'pv-reception-provisoire',
            'PV Réception Définitive' => 'pv-reception-definitive',
            'Attestation Bonne Exécution' => 'attestation-bonne-execution',
            'Décision Nomination' => 'decision-nomination',
        ];

        // Try to add each document type
        foreach ($documents as $docLabel => $docType) {
            $documentPath = self::resolveDocumentPath($marche, $docType);

            // Check if the document file exists before adding
            if ($documentPath && file_exists($documentPath)) {
                $fileName = $docLabel . '.pdf';
                $zip->addFile($documentPath, $docsFolder . $fileName);
            } else {
                // Track missing documents
                $missingDocs[] = [
                    'type' => $docType,
                    'label' => $docLabel,
                    'path' => $documentPath
                ];
            }
        }

        // Add bordereau summary if available
        $borderauSummary = self::generateBordereauSummary($marche);
        if ($borderauSummary) {
            $zip->addFromString($borderauFolder . 'RESUME_BORDEREAU.txt', $borderauSummary);
        }

        // Add metadata file
        $metadata = self::generateMetadata($marche);
        $zip->addFromString($docsFolder . 'METADATA.txt', $metadata);

        // If there are missing documents, create a README
        if (!empty($missingDocs)) {
            $readmeContent = self::generateMissingDocsReadme($marche, $missingDocs);
            $zip->addFromString($docsFolder . 'README_DOCS_MANQUANTS.txt', $readmeContent);
            
            \Log::warning("Marche {$marche->num_marche} archive: " . count($missingDocs) . " document(s) manquant(s)");
        }

        $zip->close();

        return $archivePath;
    }

    /**
     * Resolve the storage path for a document.
     *
     * @param Marche $marche
     * @param string $documentType
     * @return string|null
     */
    private static function resolveDocumentPath(Marche $marche, string $documentType): ?string
    {
        try {
            $path = storage_path('app/documents/marches/' . $marche->id . '/' . $documentType . '.pdf');
            return $path;
        } catch (\Exception $e) {
            \Log::warning("Unable to resolve document path for {$documentType} of marche {$marche->id}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Generate a text summary of the Marche bordereau details.
     *
     * @param Marche $marche
     * @return string
     */
    private static function generateBordereauSummary(Marche $marche): string
    {
        if (!$marche->bordereauItems || $marche->bordereauItems->isEmpty()) {
            return '';
        }

        $summary = "RÉSUMÉ BORDEREAU - MARCHE: " . $marche->num_marche . "\n";
        $summary .= str_repeat("=", 60) . "\n\n";

        $summary .= "Titulaire: " . $marche->titulaire . "\n";
        $summary .= "Montant Marche: " . number_format($marche->montant, 2, ',', ' ') . " MAD\n";
        $summary .= "AOO Parent: " . $marche->aoo?->num_aoo . "\n";
        $summary .= "Lot: " . $marche->lot?->num_lot . "\n\n";

        $summary .= "DÉTAIL BORDEREAU DES PRIX:\n";
        $summary .= str_repeat("-", 60) . "\n";

        $totalHt = 0;
        $totalTtc = 0;

        foreach ($marche->bordereauItems as $item) {
            $lotItem = $item->lotItem;
            if ($lotItem) {
                $montantHt = $lotItem->quantite * $item->prix_unitaire_ttc / 1.20;
                $montantTtc = $lotItem->quantite * $item->prix_unitaire_ttc;

                $summary .= "\n" . $lotItem->designation . "\n";
                $summary .= "  Quantité: " . number_format($lotItem->quantite, 2, ',', ' ') . " " . $lotItem->unite . "\n";
                $summary .= "  Prix Unitaire TTC: " . number_format($item->prix_unitaire_ttc, 2, ',', ' ') . " MAD\n";
                $summary .= "  Montant HT: " . number_format($montantHt, 2, ',', ' ') . " MAD\n";
                $summary .= "  Montant TTC: " . number_format($montantTtc, 2, ',', ' ') . " MAD\n";

                $totalHt += $montantHt;
                $totalTtc += $montantTtc;
            }
        }

        $summary .= "\n" . str_repeat("-", 60) . "\n";
        $summary .= "TOTAL HT: " . number_format($totalHt, 2, ',', ' ') . " MAD\n";
        $summary .= "TVA (20%): " . number_format($totalTtc - $totalHt, 2, ',', ' ') . " MAD\n";
        $summary .= "TOTAL TTC: " . number_format($totalTtc, 2, ',', ' ') . " MAD\n";

        return $summary;
    }

    /**
     * Generate metadata for the archive.
     *
     * @param Marche $marche
     * @return string
     */
    private static function generateMetadata(Marche $marche): string
    {
        $metadata = "ARCHIVE METADATA - MARCHE\n";
        $metadata .= str_repeat("=", 60) . "\n\n";
        $metadata .= "Numéro Marche: " . $marche->num_marche . "\n";
        $metadata .= "Titulaire: " . $marche->titulaire . "\n";
        $metadata .= "Montant: " . number_format($marche->montant, 2, ',', ' ') . " MAD\n";
        $metadata .= "AOO Parent: " . $marche->aoo?->num_aoo . "\n";
        $metadata .= "Lot: " . $marche->lot?->num_lot . "\n";
        $metadata .= "Date de Création: " . now()->format('d/m/Y H:i:s') . "\n";
        $metadata .= "Statut: " . $marche->statut . "\n";
        $metadata .= "Date de Signature: " . ($marche->date_signature ? $marche->date_signature->format('d/m/Y') : 'N/A') . "\n";
        $metadata .= "Date d'Approbation: " . ($marche->date_approbation ? $marche->date_approbation->format('d/m/Y') : 'N/A') . "\n";

        return $metadata;
    }

    /**
     * Generate a README file listing missing documents.
     *
     * @param Marche $marche
     * @param array $missingDocs
     * @return string
     */
    private static function generateMissingDocsReadme(Marche $marche, array $missingDocs): string
    {
        $readme = "📋 DOCUMENTS MANQUANTS\n";
        $readme .= str_repeat("=", 70) . "\n\n";

        $readme .= "Marché: " . $marche->num_marche . "\n";
        $readme .= "Titulaire: " . $marche->titulaire . "\n";
        $readme .= "Date du Rapport: " . now()->format('d/m/Y à H:i:s') . "\n\n";

        $readme .= "⚠️ DOCUMENTS INDISPONIBLES (" . count($missingDocs) . " document(s)):\n";
        $readme .= str_repeat("-", 70) . "\n\n";

        foreach ($missingDocs as $index => $doc) {
            $readme .= ($index + 1) . ". " . $doc['label'] . "\n";
            $readme .= "   Type: " . $doc['type'] . "\n";
            $readme .= "   Chemin attendu: " . ($doc['path'] ?? 'Non déterminé') . "\n";
            $readme .= "   Statut: ❌ Non généré\n\n";
        }

        $readme .= str_repeat("=", 70) . "\n";
        $readme .= "ACTIONS RECOMMANDÉES:\n";
        $readme .= "1. Vérifier que toutes les étapes du workflow sont terminées\n";
        $readme .= "2. Regénérer les documents manquants depuis l'interface\n";
        $readme .= "3. Relancer l'export de l'archive après génération\n";

        return $readme;
    }
}
