<?php
$soffice = 'C:\Program Files\LibreOffice\program\soffice.exe';
$input = __DIR__.'/storage/app/templates/avis_publication_fr.docx';
$outdir = __DIR__.'/storage/app';
$cmd = '"'.$soffice.'" --headless --nologo --nofirststartwizard --convert-to pdf --outdir "'.$outdir.'" "'.$input.'" 2>&1';
echo "Running: $cmd\n";
exec($cmd, $output, $return_var);
echo "Output: " . implode("\n", $output) . "\n";
echo "Exit Code: $return_var\n";
$pdfPath = __DIR__.'/storage/app/avis_publication_fr.pdf';
if (file_exists($pdfPath)) {
    echo "File exists!\n";
    unlink($pdfPath);
} else {
    echo "File DOES NOT exist.\n";
}
