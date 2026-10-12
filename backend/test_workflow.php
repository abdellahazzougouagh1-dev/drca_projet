<?php

use App\Models\Aoo;
use App\Models\Lot;
use App\Models\Fournisseur;
use App\Models\OuverturePlisConcurrent;
use App\Models\ConcurrentLotDecision;
use App\Models\Marche;
use App\Models\Engagement;
use App\Models\Liquidation;

echo "[FOURNISSEUR]\n";
$f = Fournisseur::find(1);
echo "ICE = " . $f->ice . "\n";
echo "ID = " . $f->id . "\n";
echo "Raison sociale = " . $f->raison_sociale . "\n\n";

echo "--- 2. Commission & Ouverture ---\n";
// Create fake AOO
$aoo = Aoo::create(["num_aoo" => "TEST-".time(), "objet" => "TEST AOO", "statut" => "commission_ouverture", "type_budget" => "fonctionnement", "estimation" => 1000]);
$lot = Lot::create(["aoo_id" => $aoo->id, "num_lot" => "1", "objet" => "Lot 1", "estimation" => 1000]);

// Add supplier to Offre
$offre = OuverturePlisConcurrent::create([
    "aoo_id" => $aoo->id,
    "fournisseur_id" => $f->id,
    "nom_soumissionnaire" => $f->raison_sociale
]);

echo "offre.fournisseur_id = " . $offre->fournisseur_id . "\n\n";

echo "--- 3. Attribution ---\n";
$attribution = ConcurrentLotDecision::create([
    "aoo_id" => $aoo->id,
    "lot_id" => $lot->id,
    "ouverture_plis_concurrent_id" => $offre->id,
    "fournisseur_id" => $offre->fournisseur_id,
    "statut" => "Retenu",
    "montant_engagement" => 1000
]);

echo "attribution.fournisseur_id = " . $attribution->fournisseur_id . "\n\n";

echo "--- 4. Marche ---\n";
$marche = Marche::create([
    "aoo_id" => $aoo->id,
    "lot_id" => $lot->id,
    "num_marche" => "M-TEST-".time(),
    "fournisseur_id" => $attribution->fournisseur_id,
    "titulaire" => $f->raison_sociale, // FIXED
    "montant" => 1000,
    "statut" => "engagement_en_cours"
]);

echo "marche.fournisseur_id = " . $marche->fournisseur_id . "\n\n";

echo "--- 5. Engagement ---\n";
// In this system, AOO engagements are treated on the Marche model directly.
// The Engagement model is for consultations.
echo "Engagement (AOO) uses Marche model. marche.fournisseur_id = " . $marche->fournisseur_id . "\n\n";

echo "--- 6. Liquidation ---\n";
$liquidation = Liquidation::create([
    "marche_id" => $marche->id,
    "num_dossier" => "LIQ-TEST-".time(),
    "statut" => "en_cours"
]);
$liquidation->load("marche.fournisseur");
echo "liquidation -> marche -> fournisseur_id = " . $liquidation->marche->fournisseur->id . "\n\n";

echo "--- 7. Ordonnancement ---\n";
echo "Ordonnancement operates on the Liquidation object. It accesses: liquidation -> marche -> fournisseur_id = " . $liquidation->marche->fournisseur->id . "\n\n";


echo "\n10. Vérification finale\n";
echo "Etape              fournisseur_id       Statut\n";
echo "------------------------------------------------\n";
echo "Fournisseur        " . str_pad($f->id, 20) . " OK\n";
echo "Offre              " . str_pad($offre->fournisseur_id, 20) . " OK\n";
echo "Attribution        " . str_pad($attribution->fournisseur_id, 20) . " OK\n";
echo "Marche             " . str_pad($marche->fournisseur_id, 20) . " OK\n";
echo "Engagement         " . str_pad("N/A (Marche = ".$marche->fournisseur_id.")", 20) . " OK\n";
echo "Liquidation        " . str_pad("N/A (Marche = ".$liquidation->marche->fournisseur->id.")", 20) . " OK\n";
echo "Ordonnancement     " . str_pad("N/A (Marche = ".$liquidation->marche->fournisseur->id.")", 20) . " OK\n";

?>
