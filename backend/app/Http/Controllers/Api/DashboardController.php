<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    /**
     * Export styled Excel report for budget synthesis.
     */
    public function exportBudgetExcel(Request $request)
    {
        $exercice = $request->input('exercice') ?: ($request->input('year') ?: date('Y'));
        $domaine = strtoupper($request->input('domaine') ?: 'ALL');
        $domaineLabel = $domaine === 'ALL' ? 'Tous les domaines' : $domaine;

        $rows = $request->input('rows');
        $summary = $request->input('summary');

        // Fallback: If rows not sent from frontend, compute from NotificationController
        if (empty($rows)) {
            $notificationController = app(NotificationController::class);
            $dashStats = $notificationController->getDashboardStats($request)->getData(true);
            $lignes = $dashStats['lignes'] ?? [];
            if ($domaine !== 'ALL') {
                $lignes = array_values(array_filter($lignes, function ($l) use ($domaine) {
                    return strtoupper($l['domaine'] ?? '') === $domaine;
                }));
            }
            $rows = [];
            foreach ($lignes as $l) {
                $totCredits = (float)($l['total_credits'] ?? 0);
                $totEng = (float)($l['total_engagements'] ?? ($l['credits_engages'] ?? 0));
                $totOrd = (float)($l['total_ordonnance'] ?? 0);
                $totPai = (float)($l['total_paiements'] ?? 0);
                $disp = max(0, $totCredits - $totEng);

                $rows[] = [
                    'domaine' => strtoupper($l['domaine'] ?? 'INVESTISSEMENT'),
                    'imputation' => $l['imputation'] ?? '',
                    'libelle' => $l['libelle'] ?? '',
                    'total_credits' => $totCredits,
                    'credits_engages' => $totEng,
                    'taux_engagement' => $totCredits > 0 ? ($totEng / $totCredits) * 100 : 0,
                    'credits_disponibles' => $disp,
                    'total_ordonnance' => $totOrd,
                    'taux_ordonnancement' => $totEng > 0 ? ($totOrd / $totEng) * 100 : 0,
                    'taux_ordonnancement_notifie' => $totCredits > 0 ? ($totOrd / $totCredits) * 100 : 0,
                    'total_paiements' => $totPai,
                    'taux_paiement_ordonnancement' => $totOrd > 0 ? ($totPai / $totOrd) * 100 : 0,
                    'taux_paiement_engagement' => $totEng > 0 ? ($totPai / $totEng) * 100 : 0,
                    'taux_paiement_notifie' => $totCredits > 0 ? ($totPai / $totCredits) * 100 : 0,
                ];
            }
        }

        // Initialize Spreadsheet
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('DRCA - Système de Suivi Budgétaire')
            ->setLastModifiedBy('Directeur DRCA')
            ->setTitle("Synthèse budgétaire - {$exercice}")
            ->setSubject('Suivi et exécution budgétaire');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Synthèse budgétaire');
        $sheet->setShowGridLines(true);

        // Page setup: Landscape A4, fit to 1 page wide
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

        // Color Palette (Tailwind Emerald theme matching Frontend)
        $cEmeraldDark   = 'FF064E3B'; // Emerald 900
        $cEmeraldMain   = 'FF047857'; // Emerald 700 (Header background)
        $cEmeraldBorder = 'FF059669'; // Emerald 600
        $cEmeraldLight  = 'FFECFDF5'; // Emerald 50 (Summary footer)
        $cEmeraldZebra  = 'FFF0FDF4'; // Emerald 25 / Mint (Alternating row)
        $cWhite         = 'FFFFFFFF';
        $cSlateDark     = 'FF0F172A'; // Slate 900
        $cSlateText     = 'FF334155'; // Slate 700
        $cSlateMuted    = 'FF64748B'; // Slate 500
        $cSlateLight    = 'FFE2E8F0'; // Slate 200 (Borders)
        $cViolet        = 'FF6D28D9'; // Violet 700 (Ordonnancement)
        $cAmber         = 'FFB45309'; // Amber 700 (Paiements)
        $cBlueBg        = 'FFDBEAFE'; // Blue 100 (Fonctionnement badge)
        $cBlueText      = 'FF1E40AF'; // Blue 800
        $cIndigoBg      = 'FFE0E7FF'; // Indigo 100 (Investissement badge)
        $cIndigoText    = 'FF4338CA'; // Indigo 700

        // 1. BANNER / TITLE SECTION
        // Row 1: Institution header
        $sheet->mergeCells('A1:M1');
        $sheet->setCellValue('A1', 'ROYAUME DU MAROC  •  DIRECTION RÉGIONALE DE COORDINATION DE L\'AGRICULTURE');
        $sheet->getStyle('A1')->getFont()->setSize(9)->setBold(true)->setColor(new Color('FF475569'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(20);

        // Row 2: Title
        $sheet->mergeCells('A2:M2');
        $sheet->setCellValue('A2', 'SYNTHÈSE PAR LIGNE BUDGÉTAIRE');
        $sheet->getStyle('A2')->getFont()->setSize(16)->setBold(true)->setColor(new Color($cEmeraldMain));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(28);

        // Row 3: Subtitle / Metadata
        $sheet->mergeCells('A3:M3');
        $sheet->setCellValue('A3', "Exercice : {$exercice}   |   Périmètre : {$domaineLabel}   |   Montants en Dirhams marocains (DH)   |   Exporté le : " . date('d/m/Y à H:i'));
        $sheet->getStyle('A3')->getFont()->setSize(10)->setItalic(true)->setColor(new Color($cSlateMuted));
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // Row 4: Spacer
        $sheet->getRowDimension(4)->setRowHeight(8);

        // 2. TABLE HEADERS (Row 5)
        $headers = [
            'A5' => 'DOMAINE',
            'B5' => 'LIGNE BUDGÉTAIRE',
            'C5' => 'NOTIFIÉ',
            'D5' => 'TOTAL ENGAGEMENTS',
            'E5' => 'TAUX ENG.',
            'F5' => 'DISPONIBLE',
            'G5' => 'TOTAL ORDONNANCEMENT',
            'H5' => 'TAUX ORD./ENG.',
            'I5' => 'TAUX ORD./NOT.',
            'J5' => 'TOTAL PAIEMENTS',
            'K5' => 'TAUX PAI./ORD.',
            'L5' => 'TAUX PAI./ENG.',
            'M5' => 'TAUX PAI./NOT.',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $sheet->getStyle('A5:M5')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => $cWhite],
                'size' => 10.5,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => $cEmeraldMain],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => $cEmeraldBorder],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['argb' => $cEmeraldDark],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C5:M5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension(5)->setRowHeight(32);

        // Number format codes in Excel
        $fmtCurrency = '#,##0.00 "DH"';
        $fmtPercent = '0.00%';

        // 3. DATA ROWS
        $currentRow = 6;
        $totalNotifie = 0;
        $totalEngage = 0;
        $totalDisponible = 0;
        $totalOrdonnance = 0;
        $totalPaiements = 0;

        foreach ($rows as $index => $r) {
            $dom = strtoupper($r['domaine'] ?? 'INVESTISSEMENT');
            $imp = trim($r['imputation'] ?? '');
            $lib = trim($r['libelle'] ?? '');
            $ligneLabel = $imp . ($lib !== '' ? '  ' . $lib : '');

            $notifie = (float)($r['total_credits'] ?? 0);
            $engages = (float)($r['credits_engages'] ?? 0);
            $dispo   = (float)($r['credits_disponibles'] ?? max(0, $notifie - $engages));
            $ordo    = (float)($r['total_ordonnance'] ?? 0);
            $paie    = (float)($r['total_paiements'] ?? 0);

            // Rates as decimals (0 to 1) for Excel percentage formatting
            $tEng = isset($r['taux_engagement']) ? ((float)$r['taux_engagement']) / 100 : ($notifie > 0 ? $engages / $notifie : 0);
            $tOrdEng = isset($r['taux_ordonnancement']) ? ((float)$r['taux_ordonnancement']) / 100 : ($engages > 0 ? $ordo / $engages : 0);
            $tOrdNot = isset($r['taux_ordonnancement_notifie']) ? ((float)$r['taux_ordonnancement_notifie']) / 100 : ($notifie > 0 ? $ordo / $notifie : 0);
            $tPaiOrd = isset($r['taux_paiement_ordonnancement']) ? ((float)$r['taux_paiement_ordonnancement']) / 100 : ($ordo > 0 ? $paie / $ordo : 0);
            $tPaiEng = isset($r['taux_paiement_engagement']) ? ((float)$r['taux_paiement_engagement']) / 100 : ($engages > 0 ? $paie / $engages : 0);
            $tPaiNot = isset($r['taux_paiement_notifie']) ? ((float)$r['taux_paiement_notifie']) / 100 : ($notifie > 0 ? $paie / $notifie : 0);

            $totalNotifie += $notifie;
            $totalEngage += $engages;
            $totalDisponible += $dispo;
            $totalOrdonnance += $ordo;
            $totalPaiements += $paie;

            $sheet->setCellValue("A{$currentRow}", $dom);
            $sheet->setCellValue("B{$currentRow}", $ligneLabel);
            $sheet->setCellValue("C{$currentRow}", $notifie);
            $sheet->setCellValue("D{$currentRow}", $engages);
            $sheet->setCellValue("E{$currentRow}", $tEng);
            $sheet->setCellValue("F{$currentRow}", $dispo);
            $sheet->setCellValue("G{$currentRow}", $ordo);
            $sheet->setCellValue("H{$currentRow}", $tOrdEng);
            $sheet->setCellValue("I{$currentRow}", $tOrdNot);
            $sheet->setCellValue("J{$currentRow}", $paie);
            $sheet->setCellValue("K{$currentRow}", $tPaiOrd);
            $sheet->setCellValue("L{$currentRow}", $tPaiEng);
            $sheet->setCellValue("M{$currentRow}", $tPaiNot);

            // Row background (zebra striping)
            $rowBg = ($index % 2 === 0) ? $cWhite : $cEmeraldZebra;
            $sheet->getStyle("A{$currentRow}:M{$currentRow}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $rowBg],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => $cSlateLight],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'font' => [
                    'size' => 10,
                    'color' => ['argb' => $cSlateText],
                ],
            ]);

            // Badge styling for Col A
            if ($dom === 'FONCTIONNEMENT') {
                $sheet->getStyle("A{$currentRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $cBlueBg]],
                    'font' => ['color' => ['argb' => $cBlueText], 'bold' => true, 'size' => 9.5],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            } else {
                $sheet->getStyle("A{$currentRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $cIndigoBg]],
                    'font' => ['color' => ['argb' => $cIndigoText], 'bold' => true, 'size' => 9.5],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }

            // Text alignments
            $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C{$currentRow}:M{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Formats
            $sheet->getStyle("C{$currentRow}")->getNumberFormat()->setFormatCode($fmtCurrency);
            $sheet->getStyle("D{$currentRow}")->getNumberFormat()->setFormatCode($fmtCurrency);
            $sheet->getStyle("E{$currentRow}")->getNumberFormat()->setFormatCode($fmtPercent);
            $sheet->getStyle("F{$currentRow}")->getNumberFormat()->setFormatCode($fmtCurrency);
            $sheet->getStyle("G{$currentRow}")->getNumberFormat()->setFormatCode($fmtCurrency);
            $sheet->getStyle("H{$currentRow}")->getNumberFormat()->setFormatCode($fmtPercent);
            $sheet->getStyle("I{$currentRow}")->getNumberFormat()->setFormatCode($fmtPercent);
            $sheet->getStyle("J{$currentRow}")->getNumberFormat()->setFormatCode($fmtCurrency);
            $sheet->getStyle("K{$currentRow}")->getNumberFormat()->setFormatCode($fmtPercent);
            $sheet->getStyle("L{$currentRow}")->getNumberFormat()->setFormatCode($fmtPercent);
            $sheet->getStyle("M{$currentRow}")->getNumberFormat()->setFormatCode($fmtPercent);

            // Colors matching frontend table
            // Engagements -> Bold Emerald Green
            $sheet->getStyle("D{$currentRow}")->getFont()->setBold(true)->setColor(new Color($cEmeraldMain));
            // Ordonnancements -> Bold Violet
            $sheet->getStyle("G{$currentRow}")->getFont()->setBold(true)->setColor(new Color($cViolet));
            // Taux Ord./Not. -> Semi-bold Emerald Green
            $sheet->getStyle("I{$currentRow}")->getFont()->setBold(true)->setColor(new Color($cEmeraldMain));
            // Paiements -> Bold Amber
            $sheet->getStyle("J{$currentRow}")->getFont()->setBold(true)->setColor(new Color($cAmber));
            // Taux Pai./Not. -> Semi-bold Emerald Green
            $sheet->getStyle("M{$currentRow}")->getFont()->setBold(true)->setColor(new Color($cEmeraldMain));

            $sheet->getRowDimension($currentRow)->setRowHeight(22);
            $currentRow++;
        }

        // 4. SUMMARY / FOOTER ROW
        $sumNotifie = isset($summary['total_notifie']) ? (float)$summary['total_notifie'] : $totalNotifie;
        $sumEngage = isset($summary['total_engage']) ? (float)$summary['total_engage'] : $totalEngage;
        $sumDispo = isset($summary['total_disponible']) ? (float)$summary['total_disponible'] : $totalDisponible;
        $sumOrdo = isset($summary['total_ordonnance']) ? (float)$summary['total_ordonnance'] : $totalOrdonnance;
        $sumPai = isset($summary['total_paiement']) ? (float)$summary['total_paiement'] : $totalPaiements;

        $tConsommation = isset($summary['taux_consommation']) ? ((float)$summary['taux_consommation']) / 100 : ($sumNotifie > 0 ? $sumEngage / $sumNotifie : 0);
        $tGlobalOrdEng = isset($summary['taux_ordonnancement']) ? ((float)$summary['taux_ordonnancement']) / 100 : ($sumEngage > 0 ? $sumOrdo / $sumEngage : 0);
        $tGlobalOrdNot = isset($summary['taux_ordonnancement_notifie']) ? ((float)$summary['taux_ordonnancement_notifie']) / 100 : ($sumNotifie > 0 ? $sumOrdo / $sumNotifie : 0);
        $tGlobalPaiOrd = isset($summary['taux_paiement_ordonnancement']) ? ((float)$summary['taux_paiement_ordonnancement']) / 100 : ($sumOrdo > 0 ? $sumPai / $sumOrdo : 0);
        $tGlobalPaiEng = isset($summary['taux_paiement_engagement']) ? ((float)$summary['taux_paiement_engagement']) / 100 : ($sumEngage > 0 ? $sumPai / $sumEngage : 0);
        $tGlobalPaiNot = isset($summary['taux_paiement_notifie']) ? ((float)$summary['taux_paiement_notifie']) / 100 : ($sumNotifie > 0 ? $sumPai / $sumNotifie : 0);

        $sheet->mergeCells("A{$currentRow}:B{$currentRow}");
        $totalLabel = 'TOTAL ' . ($domaine === 'ALL' ? 'GLOBAL' : $domaine);
        $sheet->setCellValue("A{$currentRow}", $totalLabel);
        $sheet->setCellValue("C{$currentRow}", $sumNotifie);
        $sheet->setCellValue("D{$currentRow}", $sumEngage);
        $sheet->setCellValue("E{$currentRow}", $tConsommation);
        $sheet->setCellValue("F{$currentRow}", $sumDispo);
        $sheet->setCellValue("G{$currentRow}", $sumOrdo);
        $sheet->setCellValue("H{$currentRow}", $tGlobalOrdEng);
        $sheet->setCellValue("I{$currentRow}", $tGlobalOrdNot);
        $sheet->setCellValue("J{$currentRow}", $sumPai);
        $sheet->setCellValue("K{$currentRow}", $tGlobalPaiOrd);
        $sheet->setCellValue("L{$currentRow}", $tGlobalPaiEng);
        $sheet->setCellValue("M{$currentRow}", $tGlobalPaiNot);

        // Footer styling (Emerald 50 fill, double accounting underline)
        $sheet->getStyle("A{$currentRow}:M{$currentRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => $cEmeraldDark],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => $cEmeraldLight],
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['argb' => $cEmeraldMain],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color' => ['argb' => $cEmeraldDark],
                ],
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFA7F3D0'], // Light emerald
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("C{$currentRow}:M{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle("C{$currentRow}")->getNumberFormat()->setFormatCode($fmtCurrency);
        $sheet->getStyle("D{$currentRow}")->getNumberFormat()->setFormatCode($fmtCurrency);
        $sheet->getStyle("E{$currentRow}")->getNumberFormat()->setFormatCode($fmtPercent);
        $sheet->getStyle("F{$currentRow}")->getNumberFormat()->setFormatCode($fmtCurrency);
        $sheet->getStyle("G{$currentRow}")->getNumberFormat()->setFormatCode($fmtCurrency);
        $sheet->getStyle("H{$currentRow}")->getNumberFormat()->setFormatCode($fmtPercent);
        $sheet->getStyle("I{$currentRow}")->getNumberFormat()->setFormatCode($fmtPercent);
        $sheet->getStyle("J{$currentRow}")->getNumberFormat()->setFormatCode($fmtCurrency);
        $sheet->getStyle("K{$currentRow}")->getNumberFormat()->setFormatCode($fmtPercent);
        $sheet->getStyle("L{$currentRow}")->getNumberFormat()->setFormatCode($fmtPercent);
        $sheet->getStyle("M{$currentRow}")->getNumberFormat()->setFormatCode($fmtPercent);

        $sheet->getRowDimension($currentRow)->setRowHeight(26);

        // 5. COLUMN WIDTHS & ERGONOMICS
        $colWidths = [
            'A' => 18, // Domaine
            'B' => 52, // Ligne Budgétaire
            'C' => 18, // Notifié
            'D' => 22, // Total Engagements
            'E' => 14, // Taux Eng.
            'F' => 18, // Disponible
            'G' => 24, // Total Ordonnancement
            'H' => 16, // Taux Ord./Eng.
            'I' => 16, // Taux Ord./Not.
            'J' => 20, // Total Paiements
            'K' => 16, // Taux Pai./Ord.
            'L' => 16, // Taux Pai./Eng.
            'M' => 16, // Taux Pai./Not.
        ];

        foreach ($colWidths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Freeze pane below row 5 so headers stay fixed during scrolling
        $sheet->freezePane('A6');

        // AutoFilter on data headers
        $sheet->setAutoFilter("A5:M{$currentRow}");

        // Build and stream .xlsx file
        $writer = new Xlsx($spreadsheet);
        $safeDomaine = preg_replace('/[^a-zA-Z0-9_-]/', '_', $domaine);
        $fileName = "Synthese_budgetaire_{$exercice}_{$safeDomaine}.xlsx";

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }
}
