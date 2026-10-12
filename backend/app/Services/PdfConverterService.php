<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Log;

class PdfConverterService
{
    /**
     * Convertit un fichier Word (.docx) en PDF en utilisant LibreOffice Headless.
     * 
     * @param string $inputPath Chemin absolu du fichier .docx
     * @return string Chemin absolu du fichier .pdf généré
     * @throws \Exception Si LibreOffice n'est pas installé ou si la conversion échoue
     */
    public static function convertToPdf(string $inputPath): string
    {
        if (!file_exists($inputPath)) {
            throw new \Exception("Le fichier d'entrée introuvable pour la conversion: " . $inputPath);
        }

        $outDir = dirname($inputPath);
        $fileName = pathinfo($inputPath, PATHINFO_FILENAME);
        $expectedPdfPath = $outDir . DIRECTORY_SEPARATOR . $fileName . '.pdf';

        // Nettoyer s'il existe déjà une ancienne version
        if (file_exists($expectedPdfPath)) {
            unlink($expectedPdfPath);
        }

        // Détection de l'exécutable LibreOffice selon l'OS
        $soffice = self::getLibreOfficePath();

        if (!$soffice) {
            throw new \Exception("LibreOffice (soffice) n'est pas installé ou introuvable sur le serveur. Impossible de générer le PDF.");
        }

        $tempProfile = 'file:///' . str_replace('\\', '/', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'LibreOfficeTempProfile_' . uniqid());

        // Sur Windows, si getLibreOfficePath renvoie un chemin avec espaces, il ne faut pas le remettre dans escapeshellarg si c'est utilisé directement dans le tableau Process (Process le fait)
        $process = new Process([
            $soffice,
            '-env:UserInstallation=' . $tempProfile,
            '--headless',
            '--nologo',
            '--nofirststartwizard',
            '--convert-to',
            'pdf',
            '--outdir',
            $outDir,
            $inputPath
        ]);

        // Fixe un timeout de 60 secondes max pour la conversion
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            $rawError = $process->getErrorOutput();
            $errorOutput = mb_convert_encoding($rawError, 'UTF-8', 'ISO-8859-1'); // Safe fallback for Windows cmd output
            
            Log::error("Erreur de conversion PDF LibreOffice", [
                'errorOutput' => $errorOutput,
                'inputPath' => $inputPath
            ]);
            throw new \Exception("LibreOffice n'a pas pu générer le PDF. Assurez-vous qu'il est bien installé sur le serveur (Détails: " . substr($errorOutput, 0, 100) . ")");
        }

        if (!file_exists($expectedPdfPath)) {
            throw new \Exception("La commande LibreOffice a réussi mais le PDF n'a pas été généré.");
        }

        return $expectedPdfPath;
    }

    /**
     * Trouve le chemin de LibreOffice selon le système d'exploitation.
     */
    private static function getLibreOfficePath(): ?string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $paths = [
                'C:\Program Files\LibreOffice\program\soffice.exe',
                'C:\Program Files (x86)\LibreOffice\program\soffice.exe',
            ];
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    return $path; // Ne pas utiliser escapeshellarg ici car Symfony Process s'en charge
                }
            }
            // Fallback: assume it's in PATH
            return 'soffice';
        }

        // Linux/Mac
        $process = new Process(['which', 'soffice']);
        $process->run();
        if ($process->isSuccessful() && trim($process->getOutput()) !== '') {
            return trim($process->getOutput());
        }
        
        $process = new Process(['which', 'libreoffice']);
        $process->run();
        if ($process->isSuccessful() && trim($process->getOutput()) !== '') {
            return trim($process->getOutput());
        }

        return null;
    }
}
