<?php
$sourcePath = 'storage/app/templates/avis_publication_fr.docx';
$destPath = 'storage/app/templates/avis_publication_fr_v2.docx';
copy($sourcePath, $destPath);

$zip = new ZipArchive;
if ($zip->open($destPath) === TRUE) {
    $xml = $zip->getFromName('word/document.xml');
    
    // Fix encoding
    $xml = mb_convert_encoding($xml, 'UTF-8', 'ISO-8859-1'); // Actually, utf8_decode works too, let's use utf8_decode to reverse it properly
    $xml = utf8_decode($xml);
    
    // Fix cautionnement vs estimation line
    $xml = preg_replace('/cautionnement_ttc_lettres/', 'estimation_ttc_lettres', $xml, 1);
    $xml = preg_replace('/cautionnement_ttc/', 'estimation_ttc', $xml, 1);
    
    $zip->addFromString('word/document.xml', $xml);
    $zip->close();
    echo "OK\n";
} else {
    echo "Error opening zip\n";
}
