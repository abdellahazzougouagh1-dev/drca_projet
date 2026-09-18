<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $consultation = \App\Models\Consultation::with(['prestations', 'budget'])->first();
    if (!$consultation) {
        $consultation = new \App\Models\Consultation([
            'numero_consultation' => '01/2026',
            'objet_consultation' => 'Test Objet',
            'annee' => 2026,
            'type_budget' => 'Investissement'
        ]);
        $consultation->id = 1;
    }
    $doc = \App\Support\BonCommandeDocumentHelper::build($consultation, [], 'Cent dirhams', 'bon_commande');
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.bon_commande', [
        'consultation' => $consultation,
        'docTitle' => 'Bon de commande',
        'type' => 'bon_commande',
        'documentData' => [],
        'montantEnLettres' => 'Cent dirhams',
        'doc' => $doc,
    ]);
    $output = $pdf->output();
    echo "PDF GENERATED SUCCESS, length: " . strlen($output) . " bytes\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
