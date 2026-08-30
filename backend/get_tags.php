<?php
require 'vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

$templatePath = __DIR__ . '/storage/app/templates/bordereau.docx';
if (!file_exists($templatePath)) {
    echo "File not found: " . $templatePath . "\n";
    exit;
}

$processor = new TemplateProcessor($templatePath);
$variables = $processor->getVariables();

echo "Variables trouvées dans bordereau.docx :\n";
foreach(array_unique($variables) as $v) {
    echo "- " . $v . "\n";
}
