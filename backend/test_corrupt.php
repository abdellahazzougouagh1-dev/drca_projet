<?php
require 'vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;

$file = __DIR__ . '/storage/app/documents_generes/aoo/7/bordereau_02_2026_DRCA-RSK_1786377114.docx';
if (file_exists($file)) {
    try {
        $phpWord = IOFactory::load($file);
        echo "Valid DOCX!\n";
    } catch (\Exception $e) {
        echo "Corrupt DOCX: " . $e->getMessage() . "\n";
    }
} else {
    echo "File not found\n";
}
