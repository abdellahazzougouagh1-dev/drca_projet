<?php

namespace App\Exports;

use App\Models\Aoo;
use App\Support\AooDocumentHelper;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class OuverturePlisExport implements WithEvents
{
    protected Aoo $aoo;

    private const LAST_COL = 'H';

    private const LOGO_COL_END = 'D';

    public function __construct(Aoo $aoo)
    {
        $this->aoo = $aoo;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setTitle('TAB OUVERTURE');

                $this->setBaseColumnWidths($sheet);
                $this->applyWhiteBackground($sheet);

                $headerEndRow = $this->buildHeaderSection($sheet);
                $tableHeaderRow = $headerEndRow + 1;
                $tableSubHeaderRow = $tableHeaderRow + 1;
                $dataStartRow = $tableSubHeaderRow + 1;

                $this->buildSubmissionTableHeader($sheet, $tableHeaderRow, $tableSubHeaderRow);
                $lastDataRow = $this->fillSubmissionRows($sheet, $dataStartRow);

                $this->applyThinBorders($sheet, 'A' . $tableHeaderRow . ':' . self::LAST_COL . $lastDataRow);
                $this->styleSubmissionBody($sheet, $dataStartRow, $lastDataRow);
                $this->autoSizeColumns($sheet, $dataStartRow, $lastDataRow);
            },
        ];
    }

    private function setBaseColumnWidths($sheet): void
    {
        $widths = [
            'A' => 32,
            'B' => 9,
            'C' => 9,
            'D' => 9,
            'E' => 9,
            'F' => 16,
            'G' => 24,
            'H' => 28,
        ];

        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $sheet->getDefaultRowDimension()->setRowHeight(18);
    }

    private function applyWhiteBackground($sheet): void
    {
        $sheet->getStyle('A1:' . self::LAST_COL . '200')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFFFFFF'],
            ],
            'font' => [
                'name' => 'Arial',
                'size' => 11,
                'color' => ['argb' => 'FF000000'],
            ],
        ]);
    }

    private function buildHeaderSection($sheet): int
    {
        $dateOuverture = AooDocumentHelper::formatDate($this->aoo->date_ouverture);
        $heureOuverture = AooDocumentHelper::formatTime($this->aoo->heure_ouverture);
        $numAoo = trim((string) ($this->aoo->num_aoo ?? ''));
        $objet = trim((string) ($this->aoo->objet ?? ''));
        $journalFr = trim((string) ($this->aoo->journal_fr ?? ''));
        $journalAr = trim((string) ($this->aoo->journal_ar ?? ''));
        $estimation = $this->formatMontant($this->aoo->budget);

        if ($dateOuverture === '................') {
            $dateOuverture = '';
        }
        if ($heureOuverture === '................') {
            $heureOuverture = '';
        }

        $aoLine = "Appel d'offre Numéro : {$numAoo}";
        if ($dateOuverture !== '') {
            $aoLine .= " du {$dateOuverture}";
        }
        if ($heureOuverture !== '') {
            $aoLine .= " à {$heureOuverture}";
        }

        // Ligne 1-3 : grand bloc logos à gauche, ligne AO unifiée à droite
        $sheet->mergeCells('A1:' . self::LOGO_COL_END . '3');
        $sheet->mergeCells('E1:' . self::LAST_COL . '3');

        for ($row = 1; $row <= 3; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(26);
        }

        $this->insertLogo($sheet, 'A1', [
            'images/logo-onca.png',
            'images/logo_onca.png',
        ], 58, 12, 8);

        $this->insertLogo($sheet, 'A1', [
            'images/sceau-maroc.png',
            'images/logo_royaume.png',
            'images/logo-maroc.png',
        ], 58, 120, 8);

        $sheet->setCellValue('E1', $aoLine);
        $sheet->getStyle('E1:' . self::LAST_COL . '3')->getFont()->setBold(true);
        $sheet->getStyle('E1:' . self::LAST_COL . '3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E1:' . self::LAST_COL . '3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('E1:' . self::LAST_COL . '3')->getAlignment()->setWrapText(true);
        $this->applyThinBorders($sheet, 'A1:' . self::LAST_COL . '3');

        // Ligne 4 : objet (label + valeur pleine largeur)
        $sheet->mergeCells('A4:' . self::LAST_COL . '6');
        $objetText = $objet !== '' ? "Ayant pour Objet : {$objet}" : 'Ayant pour Objet';
        $sheet->setCellValue('A4', $objetText);
        $sheet->getRowDimension(4)->setRowHeight(22);
        $sheet->getRowDimension(5)->setRowHeight(22);
        $sheet->getRowDimension(6)->setRowHeight(22);
        $sheet->getStyle('A4:' . self::LAST_COL . '6')->getFont()->setBold(true);
        $sheet->getStyle('A4:' . self::LAST_COL . '6')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A4:' . self::LAST_COL . '6')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('A4:' . self::LAST_COL . '6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $this->applyThinBorders($sheet, 'A4:' . self::LAST_COL . '6');

        // Ligne 7 : journaux
        $sheet->setCellValue('A7', 'Journaux :');
        $sheet->setCellValue('B7', 'Français');
        $sheet->mergeCells('C7:E7');
        $sheet->setCellValue('C7', $journalFr);
        $sheet->setCellValue('F7', 'Arabe');
        $sheet->mergeCells('G7:H7');
        $sheet->setCellValue('G7', $journalAr);
        $sheet->getStyle('A7:H7')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B7:H7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('F7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $this->applyThinBorders($sheet, 'A7:' . self::LAST_COL . '7');

        // Ligne 8 : portail
        $sheet->mergeCells('A8:' . self::LAST_COL . '8');
        $sheet->setCellValue('A8', "Portail des marchés de l'Etat");
        $this->styleRange($sheet, 'A8', true, Alignment::HORIZONTAL_LEFT);
        $this->applyThinBorders($sheet, 'A8:' . self::LAST_COL . '8');

        // Ligne 9 : estimation (gauche) + caution (droite)
        $sheet->mergeCells('A9:E9');
        $estimationText = "Montant de l'estimation établi par le maître d'ouvrage qui est de :";
        if ($estimation !== '') {
            $estimationText .= " {$estimation} DH";
        }
        $sheet->setCellValue('A9', $estimationText);

        $sheet->setCellValue('F9', 'Caution Provisoire :');
        $sheet->setCellValue('G9', '');
        $sheet->setCellValue('H9', 'DH');

        $sheet->getStyle('A9:E9')->getFont()->setBold(true);
        $sheet->getStyle('A9:E9')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A9:E9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('A9:E9')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle('F9')->getFont()->setBold(true);
        $sheet->getStyle('F9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('F9')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle('G9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G9')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle('H9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('H9')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $this->applyThinBorders($sheet, 'A9:' . self::LAST_COL . '9');
        $sheet->getRowDimension(9)->setRowHeight(24);

        return 9;
    }

    private function buildSubmissionTableHeader($sheet, int $headerRow, int $subHeaderRow): void
    {
        $sheet->mergeCells('A' . $headerRow . ':A' . $subHeaderRow);
        $sheet->setCellValue('A' . $headerRow, 'Soumissionnaire');

        $sheet->mergeCells('B' . $headerRow . ':E' . $headerRow);
        $sheet->setCellValue('B' . $headerRow, 'DOSSIER ADMINISTRATIF COMPRENANT');
        $sheet->setCellValue('B' . $subHeaderRow, 'D/H');
        $sheet->setCellValue('C' . $subHeaderRow, 'C.P');
        $sheet->setCellValue('D' . $subHeaderRow, 'RC');
        $sheet->setCellValue('E' . $subHeaderRow, 'CPS');

        $sheet->setCellValue('F' . $headerRow, 'DOSSIER TECHNIQUE');
        $sheet->setCellValue('F' . $subHeaderRow, 'M.Hum et tech');

        $sheet->mergeCells('G' . $headerRow . ':G' . $subHeaderRow);
        $sheet->setCellValue('G' . $headerRow, 'OFFRE FINANCIERE');

        $sheet->mergeCells('H' . $headerRow . ':H' . $subHeaderRow);
        $sheet->setCellValue('H' . $headerRow, 'Observations');

        $headerRange = 'A' . $headerRow . ':' . self::LAST_COL . $subHeaderRow;
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($headerRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($headerRange)->getAlignment()->setWrapText(true);
        $sheet->getRowDimension($headerRow)->setRowHeight(28);
        $sheet->getRowDimension($subHeaderRow)->setRowHeight(20);
    }

    private function fillSubmissionRows($sheet, int $startRow): int
    {
        $row = $startRow;
        $concurrents = $this->aoo->concurrents;

        if ($concurrents->isEmpty()) {
            for ($i = 0; $i < 6; $i++) {
                $this->applyThinBorders($sheet, 'A' . $row . ':' . self::LAST_COL . $row);
                $sheet->getRowDimension($row)->setRowHeight(22);
                $row++;
            }

            return $row - 1;
        }

        foreach ($concurrents as $concurrent) {
            $nom = $concurrent->fournisseur?->raison_sociale ?? $concurrent->nom_soumissionnaire ?? '';
            $sheet->setCellValue('A' . $row, $nom);
                    $sheet->setCellValue('B' . $row, $concurrent->dh ? 'X' : '');
                    $sheet->setCellValue('C' . $row, $concurrent->cp ? 'X' : '');
                    $sheet->setCellValue('D' . $row, $concurrent->rc ? 'X' : '');
                    $sheet->setCellValue('E' . $row, $concurrent->cps ? 'X' : '');
                    $sheet->setCellValue('F' . $row, $concurrent->m_hum ? 'X' : '');
                    
            $montant = $concurrent->montant_engagement
                ? $this->formatMontant($concurrent->montant_engagement) . ' DH'
                : '';
                    $sheet->setCellValue('G' . $row, $montant);
            $sheet->setCellValue('H' . $row, $concurrent->observations ?? '');
                    
            $sheet->getRowDimension($row)->setRowHeight(22);
                    $row++;
                }

        $emptyRowsStart = $row;
        for ($i = 0; $i < 3; $i++) {
            $sheet->getRowDimension($row)->setRowHeight(22);
            $row++;
        }
        $this->applyThinBorders($sheet, 'A' . $emptyRowsStart . ':' . self::LAST_COL . ($row - 1));

        return $row - 1;
    }

    private function styleSubmissionBody($sheet, int $startRow, int $endRow): void
    {
        if ($endRow < $startRow) {
            return;
        }

        $range = 'A' . $startRow . ':' . self::LAST_COL . $endRow;
        $sheet->getStyle($range)->getFont()->setSize(10);
        $sheet->getStyle('B' . $startRow . ':G' . $endRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A' . $startRow . ':H' . $endRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A' . $startRow . ':A' . $endRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('H' . $startRow . ':H' . $endRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('A' . $startRow . ':H' . $endRow)->getAlignment()->setWrapText(true);
    }

    private function autoSizeColumns($sheet, int $dataStartRow, int $dataEndRow): void
    {
        $minWidths = [
            'A' => 30,
            'G' => 20,
            'H' => 24,
        ];

        foreach (range('A', self::LAST_COL) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        if (method_exists($sheet, 'calculateColumnWidths')) {
            $sheet->calculateColumnWidths();
        }

        foreach ($minWidths as $column => $minWidth) {
            $current = $sheet->getColumnDimension($column)->getWidth();
            if ($current < $minWidth) {
                $sheet->getColumnDimension($column)->setWidth($minWidth);
                $sheet->getColumnDimension($column)->setAutoSize(false);
            }
        }

        for ($row = $dataStartRow; $row <= $dataEndRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(-1);
        }
    }

    private function styleRange($sheet, string $range, bool $bold, string $horizontal): void
    {
        $sheet->getStyle($range)->getFont()->setBold($bold);
        $sheet->getStyle($range)->getAlignment()->setHorizontal($horizontal);
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function applyThinBorders($sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    private function insertLogo($sheet, string $coordinate, array $candidates, int $height, int $offsetX, int $offsetY): void
    {
        $path = $this->resolveImagePath($candidates);
        if (!$path) {
            return;
        }

        $drawing = new Drawing();
        $drawing->setName(basename($path));
        $drawing->setDescription(basename($path));
        $drawing->setPath($path);
        $drawing->setCoordinates($coordinate);
        $drawing->setHeight($height);
        $drawing->setOffsetX($offsetX);
        $drawing->setOffsetY($offsetY);
        $drawing->setWorksheet($sheet);
    }

    private function resolveImagePath(array $candidates): ?string
    {
        foreach ($candidates as $relativePath) {
            $path = public_path($relativePath);
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function formatMontant(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, 2, ',', ' ');
    }
}
