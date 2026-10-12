<?php

use App\Models\Notification;
use App\Models\NotificationLigne;
use App\Models\Aoo;
use App\Models\Marche;
use App\Models\Engagement;

$notif1 = Notification::create([
    'numero' => 'NOTIF-TEST-100',
    'date_notification' => now(),
    'exercice' => '2026',
    'type_notification' => 'Initial',
    'nature_notification' => 'Subvention',
    'montant_total' => 1000000,
]);

$ligne1 = NotificationLigne::create([
    'notification_id' => $notif1->id,
    'article' => '10',
    'paragraphe' => '20',
    'ligne_budgetaire' => '30',
    'libelle' => 'Ligne Collision 1',
    'credits_neufs' => 1000000,
]);

$notif2 = Notification::create([
    'numero' => 'NOTIF-TEST-101',
    'date_notification' => now(),
    'exercice' => '2026',
    'type_notification' => 'Initial',
    'nature_notification' => 'Subvention',
    'montant_total' => 500000,
]);

$ligne2 = NotificationLigne::create([
    'notification_id' => $notif2->id,
    'article' => '10',
    'paragraphe' => '20',
    'ligne_budgetaire' => '30',
    'libelle' => 'Ligne Collision 2',
    'credits_neufs' => 500000,
]);

echo "Ligne 1 ID: {$ligne1->id}, Credits: {$ligne1->credits_neufs}\n";
echo "Ligne 2 ID: {$ligne2->id}, Credits: {$ligne2->credits_neufs}\n";

$aoo1 = Aoo::create([
    'num_aoo' => 'AOO-TEST-100',
    'notification_ligne_id' => $ligne1->id,
    'art' => '10',
    'par' => '20',
    'lig' => '30',
    'objet' => 'Objet 1',
    'date_ouverture' => now(),
    'heure_ouverture' => '10:00',
]);

$marche1 = Marche::create([
    'num_marche' => 'MARCHE-TEST-100',
    'aoo_id' => $aoo1->id,
    'notification_ligne_id' => $ligne1->id,
    'titulaire' => 'Titulaire 1',
    'montant' => 100000,
]);

$aoo2 = Aoo::create([
    'num_aoo' => 'AOO-TEST-101',
    'notification_ligne_id' => $ligne2->id,
    'art' => '10',
    'par' => '20',
    'lig' => '30',
    'objet' => 'Objet 2',
    'date_ouverture' => now(),
    'heure_ouverture' => '10:00',
]);

$marche2 = Marche::create([
    'num_marche' => 'MARCHE-TEST-101',
    'aoo_id' => $aoo2->id,
    'notification_ligne_id' => $ligne2->id,
    'titulaire' => 'Titulaire 2',
    'montant' => 200000,
]);

echo "\n--- VERIFICATION FINANCIERE ---\n";
echo "Ligne 1 Engages (Attendu: 100000) => {$ligne1->fresh()->credits_engages}\n";
echo "Ligne 1 Dispo (Attendu: 900000) => {$ligne1->fresh()->credits_disponibles}\n";
echo "Ligne 2 Engages (Attendu: 200000) => {$ligne2->fresh()->credits_engages}\n";
echo "Ligne 2 Dispo (Attendu: 300000) => {$ligne2->fresh()->credits_disponibles}\n";

if ($ligne1->fresh()->credits_engages == 100000 && $ligne2->fresh()->credits_engages == 200000) {
    echo "\nTEST ANTI-COLLISION: PASS\n";
} else {
    echo "\nTEST ANTI-COLLISION: FAIL\n";
}

// Nettoyage
$marche1->delete();
$marche2->delete();
$aoo1->delete();
$aoo2->delete();
$ligne1->delete();
$ligne2->delete();
$notif1->delete();
$notif2->delete();

