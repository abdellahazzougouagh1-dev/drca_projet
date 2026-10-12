<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Aoo;
use App\Services\AooDocumentGenerationService;
use PhpOffice\PhpWord\TemplateProcessor;

$aoo = Aoo::where('num_aoo', '02/2026/DRCA-RSK')->first();
if (!$aoo) {
    echo "AOO not found\n";
    exit;
}

try {
    $doc = AooDocumentGenerationService::generate($aoo, 'bordereau');
    echo "Generated successfully: " . $doc->chemin_fichier . "\n";
} catch (\Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n";
}
