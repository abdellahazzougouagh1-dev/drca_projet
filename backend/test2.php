<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = \Illuminate\Http\Request::create('/api/aoos/1/documents-generes/avis_fr', 'GET');
$controller = new \App\Http\Controllers\Api\AooController();
$aoo = \App\Models\Aoo::find(1);

try {
    $response = $controller->generateDocument($request, $aoo, 'avis_fr');
    echo "Avis FR status: " . $response->getStatusCode() . "\n";
    if($response->getStatusCode() == 500) {
        echo "Error Content: " . $response->getContent() . "\n";
    }
} catch (\Exception $e) {
    echo "Avis FR exception: " . $e->getMessage() . "\n";
}

try {
    $response = $controller->generateDocument($request, $aoo, 'avis_ar');
    echo "Avis AR status: " . $response->getStatusCode() . "\n";
    if($response->getStatusCode() == 500) {
        echo "Error Content: " . $response->getContent() . "\n";
    }
} catch (\Exception $e) {
    echo "Avis AR exception: " . $e->getMessage() . "\n";
}
