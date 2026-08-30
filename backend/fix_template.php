<?php
$zip = new ZipArchive;
$file = __DIR__ . '/storage/app/templates/bordereau_fixed.docx';
if ($zip->open($file) === TRUE) {
    $xmlString = $zip->getFromName('word/document.xml');
    
    $dom = new DOMDocument();
    $dom->loadXML($xmlString);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

    // 1. Replace numero_ao
    $nodes = $xpath->query("//w:t[contains(text(), '06/2026/DRCA-RSK')]");
    foreach($nodes as $node) {
        $node->nodeValue = str_replace('06/2026/DRCA-RSK', '${numero_ao}', $node->nodeValue);
    }
    
    // 2. Replace objet
    $nodes = $xpath->query("//w:t[contains(text(), 'LOT2 :')]");
    foreach($nodes as $node) {
        $node->nodeValue = '${objet_ao}';
    }
    // Clear out the rest of the object text to avoid duplicates
    $nodes = $xpath->query("//w:t[contains(text(), 'L''ORGANISATION DES JOURNEES')]");
    foreach($nodes as $node) { $node->nodeValue = ''; }
    $nodes = $xpath->query("//w:t[contains(text(), 'FORMATION AU PROFIT')]");
    foreach($nodes as $node) { $node->nodeValue = ''; }
    $nodes = $xpath->query("//w:t[contains(text(), 'FEMMES RURALES')]");
    foreach($nodes as $node) { $node->nodeValue = ''; }

    // 3. First row tags
    $replacements = [
        '1' => '${numero_prix}',
        'Animation de journées de formation selon les prescriptions du CPS.' => '${designation}',
        'Journée' => '${unite}',
        '16' => '${quantite}',
        '6 425,00' => '${prix_unitaire_ht}',
        '102 800,00' => '${montant_ht}'
    ];
    
    foreach($replacements as $search => $replace) {
        $nodes = $xpath->query("//w:t[contains(text(), '$search')]");
        foreach($nodes as $node) {
            $node->nodeValue = str_replace($search, $replace, $node->nodeValue);
        }
    }
    
    // 4. Remove second row of the table
    $nodes = $xpath->query("//w:tr[.//w:t[contains(text(), 'Restauration des participantes')]]");
    foreach($nodes as $node) {
        $node->parentNode->removeChild($node);
    }

    // 5. Replace Totals
    $nodes = $xpath->query("//w:t[contains(text(), 'Montant total Hors TVA Prix 1 (a)')]");
    foreach($nodes as $node) { $node->nodeValue = 'Total HT'; }
    $nodes = $xpath->query("//w:t[contains(text(), 'Montant de la TVA Prix I (20%) (b)')]");
    foreach($nodes as $node) { $node->nodeValue = 'TVA (20%)'; }
    $nodes = $xpath->query("//w:t[contains(text(), 'Montant total TTC (a+b+c+d)')]");
    foreach($nodes as $node) { $node->nodeValue = 'Total TTC'; }

    $nodes = $xpath->query("//w:tr[.//w:t[contains(text(), 'Prix 2 (c)')]]");
    foreach($nodes as $node) { $node->parentNode->removeChild($node); }
    $nodes = $xpath->query("//w:tr[.//w:t[contains(text(), 'Prix 2 (10%) (d)')]]");
    foreach($nodes as $node) { $node->parentNode->removeChild($node); }

    $nodes = $xpath->query("//w:tr[.//w:t[contains(text(), 'Total HT')]]//w:t[contains(text(), '\${montant_ht}')]");
    foreach($nodes as $node) { $node->nodeValue = '${total_ht}'; }
    
    $nodes = $xpath->query("//w:t[contains(text(), '20 560,00')]");
    foreach($nodes as $node) { $node->nodeValue = '${total_tva}'; }
    
    $nodes = $xpath->query("//w:t[contains(text(), '204 384,00')]");
    foreach($nodes as $node) { $node->nodeValue = '${total_ttc}'; }

    $newXml = $dom->saveXML();
    
    $zip->addFromString('word/document.xml', $newXml);
    $zip->close();
    echo "SUCCESS";
} else {
    echo "FAILED";
}
