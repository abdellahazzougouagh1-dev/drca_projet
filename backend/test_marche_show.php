<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Marche;
use App\Http\Controllers\Api\MarcheController;
use Illuminate\Http\Request;

$controller = new MarcheController();
$marche = Marche::latest()->first();

if ($marche) {
    $req = Request::create("/marches/{$marche->id}", 'GET');
    $res = $controller->show($marche->id);
    echo "Marche ID: " . $marche->id . " Status: " . $res->getStatusCode() . "\n";
    echo "Data: " . substr($res->getContent(), 0, 300) . "...\n";
} else {
    echo "No marche found\n";
}
