<?php
$zip = new ZipArchive;
$file = __DIR__ . '/storage/app/templates/bordereau.docx';
if ($zip->open($file) === TRUE) {
    $xmlString = $zip->getFromName('word/document.xml');
    
    if (strpos($xmlString, '${quantite}') !== false) { echo "quantite found\n"; } else { echo "quantite missing\n"; }
    if (strpos($xmlString, '${montant_ht}') !== false) { echo "montant_ht found\n"; } else { echo "montant_ht missing\n"; }
    if (strpos($xmlString, '${total_ht}') !== false) { echo "total_ht found\n"; } else { echo "total_ht missing\n"; }
}
