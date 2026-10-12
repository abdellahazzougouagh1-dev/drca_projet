<?php
require 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\TablePosition;

$phpWord = new PhpWord();
$section = $phpWord->addSection([
    'marginLeft'   => 800,
    'marginRight'  => 800,
    'marginTop'    => 800,
    'marginBottom' => 800,
]);

// Header styles
$phpWord->addFontStyle('HeaderBold', ['bold' => true, 'size' => 14, 'name' => 'Times New Roman']);
$phpWord->addFontStyle('HeaderNormal', ['bold' => true, 'size' => 12, 'name' => 'Times New Roman']);
$phpWord->addParagraphStyle('Center', ['alignment' => Jc::CENTER, 'spaceAfter' => 200]);

$section->addText("APPEL D'OFFRES OUVERT NATIONAL SUR OFFRES DE PRIX NO \${numero_ao}", 'HeaderBold', 'Center');
$section->addText("BORDEREAU DES PRIX DETAIL ESTIMATIF RELATIF A : \${objet_ao}", 'HeaderNormal', 'Center');
$section->addTextBreak(1);
$section->addText("BORDEREAU DES PRIX - DETAIL ESTIMATIF", ['bold' => true, 'underline' => 'single', 'size' => 12], 'Center');
$section->addTextBreak(1);

// Table style
$tableStyle = [
    'borderSize' => 6,
    'borderColor' => '000000',
    'cellMargin' => 80,
    'alignment' => Jc::CENTER
];
$phpWord->addTableStyle('BordereauTable', $tableStyle);
$table = $section->addTable('BordereauTable');

$cellStyle = ['valign' => 'center'];
$headerFontStyle = ['bold' => true, 'size' => 11];
$cellParaStyle = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];

// Header Row
$table->addRow();
$table->addCell(1000, $cellStyle)->addText("Prix", $headerFontStyle, $cellParaStyle);
$table->addCell(4000, $cellStyle)->addText("Désignation des prestations", $headerFontStyle, $cellParaStyle);
$table->addCell(1500, $cellStyle)->addText("Unité de compte", $headerFontStyle, $cellParaStyle);
$table->addCell(1500, $cellStyle)->addText("Quantité", $headerFontStyle, $cellParaStyle);
$table->addCell(1500, $cellStyle)->addText("Prix unitaire HT Dh", $headerFontStyle, $cellParaStyle);
$table->addCell(2000, $cellStyle)->addText("Prix Total HT (DH)", $headerFontStyle, $cellParaStyle);

// Data Row (Template)
$table->addRow();
$table->addCell(1000, $cellStyle)->addText("\${numero_prix}", null, $cellParaStyle);
$table->addCell(4000, $cellStyle)->addText("\${designation}", null, ['alignment' => Jc::LEFT]);
$table->addCell(1500, $cellStyle)->addText("\${unite}", null, $cellParaStyle);
$table->addCell(1500, $cellStyle)->addText("\${quantite}", null, $cellParaStyle);
$table->addCell(1500, $cellStyle)->addText("\${prix_unitaire_ht}", null, $cellParaStyle);
$table->addCell(2000, $cellStyle)->addText("\${montant_ht}", null, $cellParaStyle);

$section->addTextBreak(1);

// Totals Table
$totalTableStyle = [
    'borderSize' => 6,
    'borderColor' => '000000',
    'cellMargin' => 80,
    'alignment' => Jc::END
];
$phpWord->addTableStyle('TotalsTable', $totalTableStyle);
$totalsTable = $section->addTable('TotalsTable');

$totalsTable->addRow();
$totalsTable->addCell(5000, $cellStyle)->addText("Total HT", $headerFontStyle, ['alignment' => Jc::RIGHT]);
$totalsTable->addCell(2000, $cellStyle)->addText("\${total_ht}", $headerFontStyle, $cellParaStyle);

$totalsTable->addRow();
$totalsTable->addCell(5000, $cellStyle)->addText("TVA", $headerFontStyle, ['alignment' => Jc::RIGHT]);
$totalsTable->addCell(2000, $cellStyle)->addText("\${total_tva}", $headerFontStyle, $cellParaStyle);

$totalsTable->addRow();
$totalsTable->addCell(5000, $cellStyle)->addText("Total TTC", $headerFontStyle, ['alignment' => Jc::RIGHT]);
$totalsTable->addCell(2000, $cellStyle)->addText("\${total_ttc}", $headerFontStyle, $cellParaStyle);

$section->addTextBreak(2);

// Footer
$section->addText("Fait à .............................................. le ..............................................", ['size' => 12]);
$section->addTextBreak(2);
$section->addText("(Signature et cachet du concurrent)", ['size' => 12, 'italic' => true], ['alignment' => Jc::END]);

// Save the file
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save(__DIR__ . '/storage/app/templates/bordereau.docx');

echo "CLEAN BORDEREAU GENERATED";
