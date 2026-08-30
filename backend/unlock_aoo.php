<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$aoo = App\Models\Aoo::where('num_aoo', '10/2026/DRCA-RSK')->first();
if ($aoo) {
    $aoo->commission_validee = 0;
    $aoo->save();
    echo 'AOO Unlocked!';
} else {
    echo 'AOO Not found!';
}
