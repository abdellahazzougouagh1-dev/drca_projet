<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $aoo = App\Models\Aoo::find(18);
    $html = view('pdf.aoo.avis_publication_ar', ['aoo' => $aoo])->render();
    
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'autoScriptToLang' => true,
        'autoLangToFont' => true,
    ]);
    
    $mpdf->WriteHTML($html);
    $mpdf->Output('public/test_avis.pdf', 'F');
    echo "Saved to public/test_avis.pdf\n";
} catch (\Exception $e) {
    echo 'MPDF_ERROR: ' . $e->getMessage() . "\n";
}
