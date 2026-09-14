<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CONSULTATIONS ===\n";
foreach (\App\Models\Consultation::all() as $c) {
    echo "ID: {$c->id}, Num: {$c->numero_consultation}, BC: {$c->numero_bc}, Mode: '{$c->mode_engagement}', Statut: {$c->statut_dossier}\n";
}

echo "\n=== LIQUIDATIONS FINANCIERES ===\n";
foreach (\App\Models\LiquidationFinanciere::all() as $lf) {
    echo "ID: {$lf->id}, ConsultID: {$lf->consultation_id}, Montant: {$lf->montant_a_payer}, Date: {$lf->date_facture}\n";
}

echo "\n=== LIQUIDATIONS (MARCHES) ===\n";
foreach (\App\Models\Liquidation::all() as $l) {
    echo "ID: {$l->id}, MarcheID: {$l->marche_id}, Montant: {$l->montant_brut_ttc}, Num: {$l->num_liquidation}\n";
}

echo "\n=== MARCHES ===\n";
foreach (\App\Models\Marche::all() as $m) {
    echo "ID: {$m->id}, Num: {$m->num_marche}, Objet: {$m->objet_marche}\n";
}
