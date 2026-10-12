<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$liq = App\Models\Liquidation::latest()->first();
if ($liq) {
    $liq->commission_reception = null;
    $liq->save();
    echo "OK updated liq " . $liq->id;
} else {
    echo "No liquidation found";
}
