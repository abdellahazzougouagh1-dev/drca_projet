<?php
require 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

$dir = __DIR__ . '/storage/app/templates/';
if (!is_dir($dir)) mkdir($dir, 0777, true);

function saveDoc($phpWord, $filename) {
    global $dir;
    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save($dir . $filename);
}

// 1. AVIS FR
$phpWord = new PhpWord();
$section = $phpWord->addSection();
$section->addText('Office National du Conseil Agricole', ['bold' => true, 'size' => 12], ['alignment' => 'center']);
$section->addText('Direction Régionale du Conseil Agricole de Rabat-Salé-Kénitra', ['bold' => true, 'size' => 12], ['alignment' => 'center']);
$section->addText('********************', [], ['alignment' => 'center']);
$section->addTextBreak();
$section->addText('AVIS D’APPEL D’OFFRES OUVERT NATIONAL', ['bold' => true, 'size' => 14, 'underline' => 'single'], ['alignment' => 'center']);
$section->addText('SUR OFFRES DE PRIX N° ${numero_ao}', ['bold' => true, 'size' => 14, 'underline' => 'single'], ['alignment' => 'center']);
$section->addText('(Séance publique)', ['italic' => true, 'size' => 12], ['alignment' => 'center']);
$section->addTextBreak();
$section->addText('Le ${date_ouverture} à ${heure_ouverture}, il sera procédé, à la salle des réunions au siège de la Direction Régionale de l’Office National du Conseil Agricole de Rabat-Salé-Kénitra, sis à ${lieu_ouverture} à l’ouverture des plis relatif à l’appel d’offres ouvert national sur offres de prix n°${numero_ao}, ayant pour objet ${objet_ao}.', ['size' => 11]);
$section->addTextBreak();
$section->addText('Le dossier d’appel d’offres doit être téléchargé à partir du portail des marchés publics accessible à l’adresse : www.marchespublics.gov.ma', ['size' => 11]);
$section->addTextBreak();
$section->addText('L’estimation des coûts des prestations établie par le maitre d’ouvrage est fixée à la somme de :', ['size' => 11]);
$section->addText('${LOT_BLOCK}', ['size' => 11]);
$section->addListItem('- Lot ${lot_numero} : ${lot_estimation} Dirhams TTC.', 0, null, 'multilevel', ['size' => 11]);
$section->addText('${/LOT_BLOCK}', ['size' => 11]);
$section->addTextBreak();
$section->addText('Le cautionnement provisoire est fixé à la somme de :', ['size' => 11]);
$section->addText('${LOT_BLOCK}', ['size' => 11]);
$section->addListItem('- Lot ${lot_numero} : ${lot_cautionnement} Dirhams.', 0, null, 'multilevel', ['size' => 11]);
$section->addText('${/LOT_BLOCK}', ['size' => 11]);
$section->addTextBreak();
$section->addText('Le contenu, la présentation ainsi que le dépôt des dossiers des concurrents doivent être conformes aux dispositions des articles 30 à 34 et 135 du décret n°2-22-431 du 15 chaabane 1444 (8 mars 2023) relatif aux marchés publics ainsi que les articles 9 et 12 de l’arrêté du Ministre Délégué auprès de la Ministre de l’économie et des finances, chargé du budget n° 1692-23 du 4 hija 1444 (23 juin 2023) relatif à la dématérialisation des procédures, des documents et des pièces relatives aux marchés publics.', ['size' => 11]);
$section->addTextBreak();
$section->addText('Les pièces justificatives à fournir sont celles prévues par les articles ${articles_rc} du règlement de consultation.', ['size' => 11]);
saveDoc($phpWord, 'avis_fr.docx');

// 2. AVIS AR
$phpWord = new PhpWord();
$section = $phpWord->addSection(['bidi' => true]);
$fontAr = ['name' => 'Arial', 'size' => 12, 'bidi' => true];
$section->addText('المكتب الوطني للاستشارة الفلاحية', ['bold' => true, 'size' => 14, 'bidi' => true], ['alignment' => 'center']);
$section->addText('المديرية الجهوية للاستشارة الفلاحية لجهة الرباط-سلا-القنيطرة.', ['bold' => true, 'size' => 14, 'bidi' => true], ['alignment' => 'center']);
$section->addText('********************', [], ['alignment' => 'center']);
$section->addTextBreak();
$section->addText('إعلان عن طلب عروض مفتوح وطني بعروض أثمان رقم ${numero_ao}', ['bold' => true, 'size' => 16, 'bidi' => true, 'underline' => 'single'], ['alignment' => 'center']);
$section->addText('(جلسة عمومية)', ['bold' => true, 'size' => 14, 'bidi' => true], ['alignment' => 'center']);
$section->addTextBreak();
$section->addText('في يوم ${date_ouverture} على الساعة ${heure_ouverture} سيتم بقاعة الاجتماعات بمقر المديرية الجهوية للاستشارة الفلاحية لجهة الرباط-سلا-القنيطرة الكائن مقرها ${lieu_ouverture_ar}، فتح الأظرفة المتعلقة بطلب عروض مفتوح وطني بعروض أثمان رقم ${numero_ao} (جلسة عمومية)، يخص ${objet_ao_ar}.', $fontAr, ['alignment' => 'both']);
$section->addTextBreak();
$section->addText('يجب سحب ملف طلب العروض حصرا عبر تحميله إلكترونيا من بوابة صفقات العمومية www.marchespublics.gov.ma.', $fontAr, ['alignment' => 'both']);
$section->addTextBreak();
$section->addText('حدد مبلغ الضمان المؤقت في:', $fontAr, ['alignment' => 'both']);
$section->addText('${LOT_BLOCK_AR}', $fontAr);
$section->addText('- ${lot_numero_ar} : ${lot_cautionnement_ar} درهم.', $fontAr);
$section->addText('${/LOT_BLOCK_AR}', $fontAr);
$section->addTextBreak();
$section->addText('تقدير كلفة الأعمال المحددة من طرف صاحب المشروع في مبلغ:', $fontAr, ['alignment' => 'both']);
$section->addText('${LOT_BLOCK_AR}', $fontAr);
$section->addText('- ${lot_numero_ar} : ${lot_estimation_ar} درهم مع احتساب جميع الرسوم.', $fontAr);
$section->addText('${/LOT_BLOCK_AR}', $fontAr);
$section->addTextBreak();
$section->addText('يجب أن يتوافق محتوى وتقديم وإيداع ملفات المتنافسين مع أحكام المواد من 30 إلى 34 و135 من المرسوم رقم 2-22-431 الصادر في 15 شعبان 1444 (8 مارس 2023) المتعلق بالصفقات العمومية.', $fontAr, ['alignment' => 'both']);
$section->addTextBreak();
$section->addText('إن الوثائق المثبتة الواجب الإدلاء بها هي تلك المنصوص عليها في المادة ${articles_rc} من نظام الاستشارة.', $fontAr, ['alignment' => 'both']);
saveDoc($phpWord, 'avis_ar.docx');

// 3. ESTIMATION & BORDEREAU
foreach(['estimation.docx', 'bordereau.docx'] as $filename) {
    $phpWord = new PhpWord();
    $section = $phpWord->addSection(['orientation' => 'landscape']);
    $title = $filename == 'estimation.docx' ? 'ESTIMATION DE L\'ADMINISTRATION' : 'BORDEREAU DES PRIX - DETAIL ESTIMATIF';
    $section->addText($title, ['bold' => true, 'size' => 14], ['alignment' => 'center']);
    $section->addTextBreak();
    $section->addText('Appel d\'offre Numéro: ${numero_ao}', ['bold' => true, 'size' => 11]);
    $section->addText('Ayant pour Objet: ${objet_ao}', ['bold' => true, 'size' => 11]);
    $section->addTextBreak();
    
    $styleTable = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 50];
    $phpWord->addTableStyle('MyTable', $styleTable);
    $table = $section->addTable('MyTable');
    
    // Header
    $table->addRow();
    $table->addCell(1000)->addText('N°', ['bold' => true]);
    $table->addCell(5000)->addText('Désignation', ['bold' => true]);
    $table->addCell(1500)->addText('Unité', ['bold' => true]);
    $table->addCell(1500)->addText('Quantité', ['bold' => true]);
    $table->addCell(2000)->addText('PU HT', ['bold' => true]);
    $table->addCell(2000)->addText('Montant HT', ['bold' => true]);
    
    // Row template
    $table->addRow();
    $table->addCell(1000)->addText('${numero_prix}');
    $table->addCell(5000)->addText('${designation}');
    $table->addCell(1500)->addText('${unite}');
    $table->addCell(1500)->addText('${quantite}');
    $table->addCell(2000)->addText('${prix_unitaire_ht}');
    $table->addCell(2000)->addText('${montant_ht}');
    
    $section->addTextBreak();
    
    $table2 = $section->addTable('MyTable');
    $table2->addRow(); $table2->addCell(4000)->addText('Total Hors Taxe', ['bold' => true]); $table2->addCell(3000)->addText('${total_ht}');
    $table2->addRow(); $table2->addCell(4000)->addText('TVA', ['bold' => true]); $table2->addCell(3000)->addText('${total_tva}');
    $table2->addRow(); $table2->addCell(4000)->addText('Total TTC', ['bold' => true]); $table2->addCell(3000)->addText('${total_ttc}');
    
    $section->addTextBreak();
    $section->addText('Arrêté à la somme de : ${montant_lettres}', ['bold' => true, 'size' => 11]);
    saveDoc($phpWord, $filename);
}

// 4. RC (Basic)
$phpWord = new PhpWord();
$section = $phpWord->addSection();
$section->addText('REGLEMENT DE CONSULTATION', ['bold' => true, 'size' => 16], ['alignment' => 'center']);
$section->addTextBreak();
$section->addText('Appel d\'offre n° ${numero_ao}', ['bold' => true, 'size' => 12]);
$section->addText('Objet: ${objet_ao}', ['size' => 12]);
$section->addTextBreak();
$section->addText('Le présent règlement de consultation est établi en vertu des dispositions du décret n°2-22-431...', ['size' => 11]);
saveDoc($phpWord, 'rc.docx');

echo "Done.\n";
