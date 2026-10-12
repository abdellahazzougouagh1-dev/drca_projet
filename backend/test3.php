<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $aoo = new \App\Models\Aoo();
    $aoo->num_aoo = 'TEST/00';
    $aoo->objet = 'Test';
    $aoo->save();
    
    $doc = \App\Services\AooDocumentGenerationService::generate($aoo, 'estimation');
    echo "Success: " . $doc->chemin_fichier . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
