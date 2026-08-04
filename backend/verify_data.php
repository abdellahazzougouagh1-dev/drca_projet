<?php

// Simple script to verify seeded data exists

require __DIR__ . '/bootstrap/app.php';

$app = app();

try {
    $aooCount = \App\Models\Aoo::count();
    $marcheCount = \App\Models\Marche::count();
    
    echo "✓ Database Check Results:\n";
    echo "  - AOO records: $aooCount\n";
    echo "  - Marche records: $marcheCount\n";
    
    if ($aooCount > 0) {
        echo "\n✓ AOO Data Sample (first record):\n";
        $aoo = \App\Models\Aoo::first();
        echo "  - ID: {$aoo->id}\n";
        echo "  - Num: {$aoo->num_aoo}\n";
        echo "  - Objet: {$aoo->objet}\n";
    }
    
    if ($marcheCount > 0) {
        echo "\n✓ Marche Data Sample (first record):\n";
        $marche = \App\Models\Marche::first();
        echo "  - ID: {$marche->id}\n";
        echo "  - Num: {$marche->num_marche}\n";
        echo "  - Titulaire: {$marche->titulaire}\n";
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
