<?php
$zip = new ZipArchive;
$file = __DIR__ . '/storage/app/templates/bordereau.docx';
if ($zip->open($file) === TRUE) {
    $xmlString = $zip->getFromName('word/document.xml');
    
    // The number 16 is alone, it could be <w:t>16</w:t>
    $xmlString = preg_replace('/<w:t>16<\/w:t>/', '<w:t>${quantite}</w:t>', $xmlString);
    $xmlString = preg_replace('/<w:t>102 800,00<\/w:t>/', '<w:t>${montant_ht}</w:t>', $xmlString);
    $xmlString = preg_replace('/<w:t>Total HT<\/w:t><\/w:r><w:r><w:rPr><w:sz w:val="22"\/><w:szCs w:val="22"\/><\/w:rPr><w:t>\${montant_ht}<\/w:t>/', '<w:t>Total HT</w:t></w:r><w:r><w:rPr><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr><w:t>${total_ht}</w:t>', $xmlString);
    // Let's just find Total HT and the next w:t
    $xmlString = preg_replace('/(Total HT.*?<w:t[^>]*>).*?(<\/w:t>)/s', '$1${total_ht}$2', $xmlString);

    $zip->addFromString('word/document.xml', $xmlString);
    $zip->close();
    echo "DONE\n";
}
