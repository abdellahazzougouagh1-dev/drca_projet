<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$m = \App\Models\Marche::find(1);
if ($m) {
    $m->num_marche = '06/2026/DRCA-RSK';
    $m->statut = 'engagement_validee';
    $m->montant = 1206000;
    $m->save();

    \App\Models\MarcheBordereauItem::create([
        'marche_id' => $m->id,
        'designation' => 'Prestation Test',
        'unite' => 'Forfait',
        'quantite' => 1,
        'prix_unitaire_ht' => 1005000,
        'taux_tva' => 20
    ]);
    echo "OK";
} else {
    echo "Marche ID 1 not found.";
}
