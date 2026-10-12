<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $aoo = \App\Models\Aoo::first();
    if($aoo) {
        $doc = \App\Services\AooDocumentGenerationService::generate($aoo, 'bordereau');
        echo "Bordereau success: " . $doc->chemin_fichier . "\n";
    }
} catch (\Exception $e) {
    echo "Bordereau error: " . $e->getMessage() . "\n";
}

try {
    if($aoo) {
        $doc = \App\Services\AooDocumentGenerationService::generate($aoo, 'estimation');
        echo "Estimation success: " . $doc->chemin_fichier . "\n";
    }
} catch (\Exception $e) {
    echo "Estimation error: " . $e->getMessage() . "\n";
}
