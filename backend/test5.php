<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $pdf = \App\Services\PdfConverterService::convertToPdf(storage_path('app/templates/avis_publication_fr.docx'));
    echo 'Success: ' . $pdf . "\n";
} catch (\Exception $e) {
    echo 'Exception: ' . $e->getMessage() . "\n";
}
