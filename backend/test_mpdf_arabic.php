<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Aoo;
use App\Http\Controllers\Api\AooController;

$aoo = Aoo::with('lots.items')->first();

if (!$aoo) {
    echo "No AOO found!\n";
    exit(1);
}

$controller = new AooController();
$reflector = new ReflectionClass(AooController::class);
$method = $reflector->getMethod('buildPublicationAvisData');
$method->setAccessible(true);

$data = $method->invoke($controller, $aoo);

// Render view using mpdf
$html = view('pdf.aoo.avis_publication_ar', $data)->render();

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 14,
    'margin_bottom' => 10,
    'autoScriptToLang' => true,
    'autoLangToFont' => true,
]);

$mpdf->WriteHTML($html);
$output = $mpdf->Output('', 'S');

file_put_contents(__DIR__ . '/public/avis_publication_ar_mpdf.pdf', $output);

echo "mPDF Arabic PDF successfully generated! Size: " . strlen($output) . " bytes\n";
