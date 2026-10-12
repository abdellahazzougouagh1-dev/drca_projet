<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Aoo;
use App\Services\AooDocumentGenerationService;
use PhpOffice\PhpWord\TemplateProcessor;

$templatePath = storage_path('app/templates/bordereau.docx');

if (!file_exists($templatePath)) {
    echo "ERREUR: Template manquant ($templatePath)\n";
    exit;
}

$hashBefore = hash_file('sha256', $templatePath);
echo "Hash SHA-256 AVANT : $hashBefore\n";

$processor = new TemplateProcessor($templatePath);
$variables = $processor->getVariables();

echo "\n--- BALISES DETECTEES ---\n";
if (empty($variables)) {
    echo "AUCUNE BALISE TROUVEE.\n";
} else {
    foreach (array_unique($variables) as $v) {
        echo "- $v\n";
    }
}

$requiredTags = ['numero_ao', 'objet_ao', 'numero_prix'];
$missing = [];
foreach ($requiredTags as $req) {
    if (!in_array($req, $variables)) {
        $missing[] = $req;
    }
}

if (!empty($missing)) {
    echo "\nERREUR: Les balises obligatoires suivantes sont manquantes dans le template original :\n";
    foreach ($missing as $m) echo "- $m\n";
    echo "Arrêt du processus pour protéger le fichier original.\n";
} else {
    // Run test
    $aoo = Aoo::where('num_aoo', '02/2026/DRCA-RSK')->first();
    try {
        $doc = AooDocumentGenerationService::generate($aoo, 'bordereau');
        echo "\nDocument généré avec succès : " . $doc->chemin_fichier . "\n";
    } catch (\Exception $e) {
        echo "\nERREUR de génération: " . $e->getMessage() . "\n";
    }
}

$hashAfter = hash_file('sha256', $templatePath);
echo "\nHash SHA-256 APRES : $hashAfter\n";

if ($hashBefore === $hashAfter) {
    echo "VERIFICATION : Le fichier original a été conservé STRICTEMENT INTACT.\n";
} else {
    echo "ALERTE : Le fichier original a été modifié !\n";
}
