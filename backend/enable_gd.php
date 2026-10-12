<?php

$ini = php_ini_loaded_file();
echo "Fichier php.ini chargé : " . ($ini ?: 'Non trouvé') . "\n";

if ($ini && file_exists($ini)) {
    $content = file_get_contents($ini);
    if (strpos($content, ';extension=gd') !== false) {
        $newContent = str_replace(';extension=gd', 'extension=gd', $content);
        if (@file_put_contents($ini, $newContent)) {
            echo "✅ SUCCÈS : extension=gd activée dans " . $ini . "\n";
            echo "👉 Veuillez redémarrer 'php artisan serve' pour prendre en compte le changement.\n";
        } else {
            echo "⚠️ PERMISSION REFUSÉE : Impossible d'écrire dans " . $ini . ".\n";
            echo "👉 Veuillez ouvrir " . $ini . " en tant qu'Administrateur et enlever le ';' devant extension=gd.\n";
        }
    } elseif (strpos($content, 'extension=gd') !== false) {
        echo "ℹ️ INFO : extension=gd est déjà décommentée dans " . $ini . ".\n";
        echo "👉 N'oubliez pas de REDÉMARRER le serveur 'php artisan serve' (Ctrl+C puis relancer).\n";
    } else {
        $newContent = $content . "\nextension=gd\n";
        if (@file_put_contents($ini, $newContent)) {
            echo "✅ SUCCÈS : Ligne 'extension=gd' ajoutée à " . $ini . "\n";
        } else {
            echo "⚠️ ÉCHEC : Éditez " . $ini . " manuellement et ajoutez 'extension=gd'.\n";
        }
    }
}
