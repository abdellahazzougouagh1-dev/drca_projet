<?php
require 'vendor/autoload.php';

$phpWord = new \PhpOffice\PhpWord\PhpWord();
$section = $phpWord->addSection();
$section->addText('Dummy template');

$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');

if (!is_dir('storage/app/templates')) {
    mkdir('storage/app/templates', 0777, true);
}

$templates = ['estimation', 'rc', 'bordereau', 'avis_fr', 'avis_ar', 'lettre_ecartement'];

foreach($templates as $t) {
    $objWriter->save('storage/app/templates/' . $t . '.docx');
}

echo "Templates created.\n";
