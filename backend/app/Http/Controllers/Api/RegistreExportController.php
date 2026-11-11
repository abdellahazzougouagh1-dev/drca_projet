<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Ordonnancement;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistreExportController extends Controller
{
    private const EMERALD_DARK   = 'FF064E3B';
    private const EMERALD_MAIN   = 'FF047857';
    private const EMERALD_BORDER = 'FF059669';
    private const EMERALD_LIGHT  = 'FFECFDF5';
    private const EMERALD_ZEBRA  = 'FFF0FDF4';
    private const WHITE          = 'FFFFFFFF';
    private const SLATE_900      = 'FF0F172A';
    private const SLATE_800      = 'FF1E293B';
    private const SLATE_700      = 'FF334155';
    private const SLATE_500      = 'FF64748B';
    private const SLATE_400      = 'FF94A3B8';
    private const SLATE_200      = 'FFE2E8F0';
    private const SLATE_100      = 'FFF1F5F9';

    /**
     * Export du registre des ordonnancements (respecte exactement la recherche / filtres actifs).
     */
    public function exportOrdonnancementExcel(Request $request)
    {
        $procedure = trim((string) ($request->input('procedure') ?: 'TOUS'));
        $exercice  = trim((string) ($request->input('exercice') ?: date('Y')));
        $statut    = trim((string) ($request->input('statut') ?: 'TOUS'));
        $search    = trim((string) ($request->input('search') ?: ''));
        $rows      = $request->input('rows');

        if (!is_array($rows)) {
            $rows = $this->buildOrdonnancementRowsFromDb($procedure, $exercice, $statut, $search);
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('DRCA RSK - Système de Suivi Budgétaire ONCA')
            ->setLastModifiedBy('Service Administratif et Financier')
            ->setTitle("Registre des ordonnancements - {$exercice}")
            ->setSubject('Suivi officiel des ordres de paiement (OP)');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ordonnancements');
        $sheet->setShowGridLines(true);

        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

        $procLabel = $procedure === 'TOUS' ? 'Toutes les procédures' : $procedure;
        $exLabel   = $exercice === 'TOUS' ? 'Tous les exercices' : $exercice;
        $stLabel   = $statut === 'TOUS' ? 'Tous' : $statut;
        $metaParts = [
            "Procédure : {$procLabel}",
            "Exercice : {$exLabel}",
            "Statut : {$stLabel}",
        ];
        if ($search !== '') {
            $metaParts[] = "Recherche : \"{$search}\"";
        }
        $metaParts[] = 'Lignes : ' . count($rows);
        $metaParts[] = 'Exporté le : ' . date('d/m/Y à H:i');

        // 1. Institution Banner (A1:S3)
        $sheet->mergeCells('A1:S1');
        $sheet->setCellValue('A1', "ROYAUME DU MAROC  •  OFFICE NATIONAL DU CONSEIL AGRICOLE (ONCA)  •  DRCA RSK");
        $sheet->getStyle('A1')->getFont()->setSize(9)->setBold(true)->setColor(new Color('FF475569'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(20);

        $sheet->mergeCells('A2:S2');
        $sheet->setCellValue('A2', 'REGISTRE DES ORDONNANCEMENTS');
        $sheet->getStyle('A2')->getFont()->setSize(16)->setBold(true)->setColor(new Color(self::EMERALD_MAIN));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(28);

        $sheet->mergeCells('A3:S3');
        $sheet->setCellValue('A3', implode('   |   ', $metaParts));
        $sheet->getStyle('A3')->getFont()->setSize(10)->setItalic(true)->setColor(new Color(self::SLATE_500));
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(3)->setRowHeight(20);

        $sheet->getRowDimension(4)->setRowHeight(8);

        // 2. Table Headers (Row 5) - Exact match with RegistreOrdonnancements table
        $headers = [
            'A' => ['label' => 'N°', 'align' => Alignment::HORIZONTAL_CENTER, 'width' => 7],
            'B' => ['label' => 'N° OP', 'align' => Alignment::HORIZONTAL_CENTER, 'width' => 11],
            'C' => ['label' => 'DATE OP', 'align' => Alignment::HORIZONTAL_CENTER, 'width' => 13],
            'D' => ['label' => 'BÉNÉFICIAIRE', 'align' => Alignment::HORIZONTAL_LEFT, 'width' => 28],
            'E' => ['label' => 'RÉFÉRENCE', 'align' => Alignment::HORIZONTAL_LEFT, 'width' => 23],
            'F' => ['label' => 'BUDGET', 'align' => Alignment::HORIZONTAL_CENTER, 'width' => 16],
            'G' => ['label' => 'CRÉANCE', 'align' => Alignment::HORIZONTAL_CENTER, 'width' => 20],
            'H' => ['label' => 'CODE', 'align' => Alignment::HORIZONTAL_CENTER, 'width' => 11],
            'I' => ['label' => 'ART', 'align' => Alignment::HORIZONTAL_CENTER, 'width' => 8],
            'J' => ['label' => 'PAR', 'align' => Alignment::HORIZONTAL_CENTER, 'width' => 8],
            'K' => ['label' => 'LIG', 'align' => Alignment::HORIZONTAL_CENTER, 'width' => 8],
            'L' => ['label' => 'S.LIG', 'align' => Alignment::HORIZONTAL_CENTER, 'width' => 8],
            'M' => ['label' => 'INTITULÉ', 'align' => Alignment::HORIZONTAL_LEFT, 'width' => 42],
            'N' => ['label' => 'PAIEMENT REPORTS', 'align' => Alignment::HORIZONTAL_RIGHT, 'width' => 19],
            'O' => ['label' => 'PAIEMENT CRÉDITS CONSOLIDÉS', 'align' => Alignment::HORIZONTAL_RIGHT, 'width' => 22],
            'P' => ['label' => 'PAIEMENT C.NEUFS', 'align' => Alignment::HORIZONTAL_RIGHT, 'width' => 18],
            'Q' => ['label' => 'PAIEMENT RETENUE À LA SOURCE', 'align' => Alignment::HORIZONTAL_RIGHT, 'width' => 22],
            'R' => ['label' => 'PAIEMENT RESTE À PAYER', 'align' => Alignment::HORIZONTAL_RIGHT, 'width' => 22],
            'S' => ['label' => 'PAYÉ / REJET', 'align' => Alignment::HORIZONTAL_CENTER, 'width' => 22],
        ];

        foreach ($headers as $col => $cfg) {
            $sheet->setCellValue("{$col}5", $cfg['label']);
            $sheet->getStyle("{$col}5")->getAlignment()->setHorizontal($cfg['align']);
            $sheet->getColumnDimension($col)->setWidth($cfg['width']);
        }

        $sheet->getStyle('A5:S5')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => self::WHITE],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::EMERALD_MAIN],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => self::EMERALD_BORDER],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['argb' => self::EMERALD_DARK],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension(5)->setRowHeight(34);

        // Format displaying "-" for 0 while remaining numeric for SUM()
        $fmtDashAmount = '#,##0.00;-#,##0.00;"-"';
        $fmtCurrency   = '#,##0.00 "DH"';

        $currentRow = 6;

        if (count($rows) === 0) {
            $sheet->mergeCells('A6:S6');
            $sheet->setCellValue('A6', 'Aucun ordonnancement correspondant à cette recherche.');
            $sheet->getStyle('A6:S6')->applyFromArray([
                'font' => ['italic' => true, 'size' => 10.5, 'color' => ['argb' => self::SLATE_500]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::SLATE_200]]],
            ]);
            $sheet->getRowDimension(6)->setRowHeight(32);
            $lastDataRow = 6;
            $totalRow    = 7;
        } else {
            foreach ($rows as $idx => $r) {
                $numIndex    = $r['index'] ?? ($idx + 1);
                $numOp       = (string) ($r['num_op'] ?? ($idx + 1));
                $dateOp      = (string) ($r['date_op'] ?? '-');
                $benef       = (string) ($r['beneficiaire'] ?? '-');
                $ref         = (string) ($r['reference'] ?? '-');
                $budget      = (string) ($r['budget'] ?? 'Investissement');
                $creance     = (string) ($r['creance'] ?? 'Reste à payer');
                $code        = (string) ($r['code'] ?? '-');
                $art         = (string) ($r['art'] ?? '-');
                $par         = (string) ($r['par'] ?? '-');
                $lig         = (string) ($r['lig'] ?? '-');
                $slig        = (string) ($r['slig'] ?? '0');
                $intitule    = (string) ($r['intitule'] ?? '-');

                $mReports    = (float) ($r['paiement_reports'] ?? ($r['montant_reports'] ?? 0));
                $mConsolides = (float) ($r['paiement_credits_consolides'] ?? ($r['montant_credit_consolides'] ?? 0));
                $mNeufs      = (float) ($r['paiement_c_neufs'] ?? ($r['montant_c_neufs'] ?? 0));
                $mRas        = (float) ($r['paiement_retenue_source'] ?? ($r['montant_ras'] ?? 0));
                $mRap        = (float) ($r['paiement_rap'] ?? ($r['montant_rap'] ?? 0));
                $statutVal   = (string) ($r['statut'] ?? 'À payer');

                $sheet->setCellValue("A{$currentRow}", (int) $numIndex);
                $sheet->setCellValueExplicit("B{$currentRow}", $numOp, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("C{$currentRow}", $dateOp, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("D{$currentRow}", $benef, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("E{$currentRow}", $ref, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("F{$currentRow}", $budget, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("G{$currentRow}", $creance, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("H{$currentRow}", $code, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("I{$currentRow}", $art, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("J{$currentRow}", $par, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("K{$currentRow}", $lig, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("L{$currentRow}", $slig, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("M{$currentRow}", $intitule, DataType::TYPE_STRING);

                $sheet->setCellValue("N{$currentRow}", $mReports);
                $sheet->setCellValue("O{$currentRow}", $mConsolides);
                $sheet->setCellValue("P{$currentRow}", $mNeufs);
                $sheet->setCellValue("Q{$currentRow}", $mRas);
                $sheet->setCellValue("R{$currentRow}", $mRap);
                $sheet->setCellValueExplicit("S{$currentRow}", $statutVal, DataType::TYPE_STRING);

                $rowBg = ($idx % 2 === 0) ? self::WHITE : self::EMERALD_ZEBRA;

                $sheet->getStyle("A{$currentRow}:S{$currentRow}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => $rowBg],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => self::SLATE_200],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'font' => [
                        'size' => 10,
                        'color' => ['argb' => self::SLATE_800],
                    ],
                ]);

                // Per-column alignments & typography matching screenshot
                $sheet->getStyle("A{$currentRow}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'font' => ['bold' => true, 'color' => ['argb' => self::SLATE_500]],
                ]);
                $sheet->getStyle("B{$currentRow}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'font' => ['bold' => true, 'color' => ['argb' => 'FF1D4ED8']],
                ]);
                $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D{$currentRow}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'wrapText' => true],
                    'font' => ['bold' => true, 'color' => ['argb' => self::SLATE_900]],
                ]);
                $sheet->getStyle("E{$currentRow}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                    'font' => ['bold' => false, 'color' => ['argb' => self::SLATE_800]],
                ]);
                $sheet->getStyle("F{$currentRow}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::SLATE_100]],
                    'font' => ['bold' => true, 'size' => 9.5, 'color' => ['argb' => self::SLATE_800]],
                ]);

                // Créance badge styling
                [$creanceBg, $creanceText] = $this->resolveCreanceColors($creance);
                $sheet->getStyle("G{$currentRow}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $creanceBg]],
                    'font' => ['bold' => true, 'size' => 9.5, 'color' => ['argb' => $creanceText]],
                ]);

                $sheet->getStyle("H{$currentRow}:L{$currentRow}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'font' => ['bold' => true, 'color' => ['argb' => self::SLATE_700]],
                ]);
                $sheet->getStyle("M{$currentRow}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'wrapText' => true],
                    'font' => ['color' => ['argb' => self::SLATE_800]],
                ]);

                // Financial columns formatting + accent colors matching UI
                $finCols = [
                    'N' => [$mReports, 'FF1E293B'],
                    'O' => [$mConsolides, 'FF3730A3'],
                    'P' => [$mNeufs, 'FF155E75'],
                    'Q' => [$mRas, 'FF92400E'],
                    'R' => [$mRap, 'FF1E3A8A'],
                ];
                foreach ($finCols as $col => [$val, $accentArgb]) {
                    $sheet->getStyle("{$col}{$currentRow}")->getNumberFormat()->setFormatCode($fmtDashAmount);
                    $sheet->getStyle("{$col}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("{$col}{$currentRow}")->getFont()
                        ->setBold($val > 0)
                        ->setColor(new Color($val > 0 ? $accentArgb : self::SLATE_400));
                }

                // Status badge styling
                [$statusBg, $statusText] = $this->resolveStatusColors($statutVal);
                $sheet->getStyle("S{$currentRow}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $statusBg]],
                    'font' => ['bold' => true, 'size' => 9.5, 'color' => ['argb' => $statusText]],
                ]);

                // Dynamic height for wrapped Intitulé / Bénéficiaire
                $intituleLines = (int) ceil(mb_strlen($intitule) / 40);
                $benefLines    = (int) ceil(mb_strlen($benef) / 26);
                $maxLines      = max(1, $intituleLines, $benefLines);
                $sheet->getRowDimension($currentRow)->setRowHeight(max(24, min(72, $maxLines * 15 + 8)));

                $currentRow++;
            }

            $lastDataRow = $currentRow - 1;
            $totalRow    = $currentRow;
        }

        // 3. TOTAL FOOTER ROW
        $sheet->mergeCells("A{$totalRow}:M{$totalRow}");
        $sheet->setCellValue("A{$totalRow}", 'TOTAL (' . count($rows) . ' ligne(s))');

        if (count($rows) > 0) {
            foreach (['N', 'O', 'P', 'Q', 'R'] as $col) {
                $sheet->setCellValue("{$col}{$totalRow}", "=SUM({$col}6:{$col}{$lastDataRow})");
            }
            $sheet->setCellValue("S{$totalRow}", "=SUM(N{$totalRow}:R{$totalRow})");
        } else {
            foreach (['N', 'O', 'P', 'Q', 'R', 'S'] as $col) {
                $sheet->setCellValue("{$col}{$totalRow}", 0);
            }
        }

        $sheet->getStyle("A{$totalRow}:S{$totalRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => self::EMERALD_DARK],
                'size' => 10.5,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::EMERALD_LIGHT],
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['argb' => self::EMERALD_MAIN],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color' => ['argb' => self::EMERALD_DARK],
                ],
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFA7F3D0'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle("A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("N{$totalRow}:S{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("N{$totalRow}:S{$totalRow}")->getNumberFormat()->setFormatCode($fmtCurrency);
        $sheet->getRowDimension($totalRow)->setRowHeight(28);

        // Freeze header & AutoFilter
        $sheet->freezePane('A6');
        if (count($rows) > 0) {
            $sheet->setAutoFilter("A5:S{$lastDataRow}");
        }

        $safeProc = preg_replace('/[^a-zA-Z0-9_-]/', '_', $procedure !== 'TOUS' ? $procedure : 'tous');
        $safeEx   = preg_replace('/[^a-zA-Z0-9_-]/', '_', $exercice);
        $suffix   = ($search !== '' || $statut !== 'TOUS') ? '_filtre' : '';
        $fileName = "Registre_ordonnancements_{$safeProc}_{$safeEx}{$suffix}.xlsx";

        return $this->streamSpreadsheet($spreadsheet, $fileName);
    }

    /**
     * Export du registre des engagements (Investissement / Fonctionnement / Complet).
     */
    public function exportEngagementExcel(Request $request)
    {
        $mode      = (string) ($request->input('mode') ?: 'tous');
        $budget    = (string) ($request->input('budget') ?: 'Investissement');
        $exercice  = (string) ($request->input('exercice') ?: date('Y'));
        $rows      = $request->input('rows', []);
        $rowsInv   = $request->input('rowsInvestissement', []);
        $rowsFnc   = $request->input('rowsFonctionnement', []);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('DRCA RSK - Système de Suivi Budgétaire ONCA')
            ->setTitle("Registre des engagements - {$exercice}");

        if ($budget === 'all') {
            $sheetInv = $spreadsheet->getActiveSheet();
            $this->populateGenericStyledSheet(
                $sheetInv,
                'Investissement',
                "REGISTRE DES ENGAGEMENTS — INVESTISSEMENT",
                "Procédure : {$mode}   |   Exercice : {$exercice}   |   Exporté le : " . date('d/m/Y à H:i'),
                is_array($rowsInv) ? $rowsInv : []
            );

            $sheetFnc = $spreadsheet->createSheet();
            $this->populateGenericStyledSheet(
                $sheetFnc,
                'Fonctionnement',
                "REGISTRE DES ENGAGEMENTS — FONCTIONNEMENT",
                "Procédure : {$mode}   |   Exercice : {$exercice}   |   Exporté le : " . date('d/m/Y à H:i'),
                is_array($rowsFnc) ? $rowsFnc : []
            );
        } else {
            $sheet = $spreadsheet->getActiveSheet();
            $titleBudget = strtoupper($budget ?: 'REGISTRE');
            $this->populateGenericStyledSheet(
                $sheet,
                substr($budget ?: 'Registre', 0, 31),
                "REGISTRE DES ENGAGEMENTS — {$titleBudget}",
                "Procédure : {$mode}   |   Exercice : {$exercice}   |   Exporté le : " . date('d/m/Y à H:i'),
                is_array($rows) ? $rows : []
            );
        }

        $safeMode = preg_replace('/[^a-zA-Z0-9_-]/', '_', $mode);
        $safeEx   = preg_replace('/[^a-zA-Z0-9_-]/', '_', $exercice);
        $fileName = "Registre_engagement_{$safeMode}_{$safeEx}.xlsx";

        return $this->streamSpreadsheet($spreadsheet, $fileName);
    }

    /**
     * Export du registre des consultations.
     */
    public function exportConsultationsExcel(Request $request)
    {
        $procedure = (string) ($request->input('procedure') ?: 'toutes');
        $exercice  = (string) ($request->input('exercice') ?: date('Y'));
        $search    = trim((string) ($request->input('search') ?: ''));
        $rows      = $request->input('rows');

        if (!is_array($rows)) {
            $query = Consultation::with('fournisseur')->orderBy('id', 'desc');
            if ($exercice !== '' && $exercice !== 'TOUS') {
                $query->where('annee', $exercice);
            }
            $rows = $query->get()->map(fn ($c) => [
                'numero_consultation' => $c->numero_consultation ?? '-',
                'date_consultation'   => $c->date_consultation,
                'mode_engagement'     => $c->mode_engagement ?? '-',
                'objet_consultation'  => $c->objet_consultation ?? '-',
                'fournisseur_nom'     => $c->fournisseur?->raison_sociale ?? '-',
                'type_budget'         => $c->type_budget ?? '-',
                'statut_dossier'      => $c->statut_dossier ?? '-',
                'total_estime'        => (float) ($c->total_estime ?? 0),
            ])->all();
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('DRCA RSK - Système de Suivi Budgétaire ONCA')
            ->setTitle("Registre des consultations - {$exercice}");

        $meta = "Procédure : {$procedure}   |   Exercice : {$exercice}";
        if ($search !== '') {
            $meta .= "   |   Recherche : \"{$search}\"";
        }
        $meta .= "   |   Exporté le : " . date('d/m/Y à H:i');

        $this->populateGenericStyledSheet(
            $spreadsheet->getActiveSheet(),
            'Consultations',
            'REGISTRE DES CONSULTATIONS',
            $meta,
            $rows
        );

        $safeProc = preg_replace('/[^a-zA-Z0-9_-]/', '_', $procedure);
        $safeEx   = preg_replace('/[^a-zA-Z0-9_-]/', '_', $exercice);
        $fileName = "Registre_consultations_{$safeProc}_{$safeEx}.xlsx";

        return $this->streamSpreadsheet($spreadsheet, $fileName);
    }

    private function buildOrdonnancementRowsFromDb(string $procedure, string $exercice, string $statut, string $search): array
    {
        $query = Ordonnancement::with(['ordres', 'fournisseur', 'liquidation'])
            ->orderBy('id', 'desc');

        if ($exercice !== '' && $exercice !== 'TOUS') {
            $query->where('exercice', $exercice);
        }
        if ($procedure !== '' && $procedure !== 'TOUS') {
            $query->where('type_procedure', $procedure);
        }

        $rows   = [];
        $seqIdx = 1;

        foreach ($query->get() as $ord) {
            $ordresList = $ord->ordres ?? collect();
            if ($ordresList->count() > 0) {
                foreach ($ordresList as $o) {
                    $creanceRaw = $o->creance ?: (
                        str_contains((string) $o->type_mouvement, 'TVA') || str_contains((string) $o->type_mouvement, 'IS') || str_contains((string) $o->type_mouvement, 'IAS')
                            ? 'Retenue à la source'
                            : (str_contains((string) $o->type_mouvement, 'Reports')
                                ? 'Reports'
                                : (str_contains((string) $o->type_mouvement, 'Consolid')
                                    ? 'Crédit Consolidés'
                                    : (str_contains((string) $o->type_mouvement, 'Neufs') ? 'C.Neufs' : 'Reste à payer')))
                    );
                    $montant = (float) ($o->montant ?? 0);
                    $isRep = $creanceRaw === 'Reports';
                    $isCon = $creanceRaw === 'Crédit Consolidés';
                    $isCnf = $creanceRaw === 'C.Neufs';
                    $isRas = $creanceRaw === 'Retenue à la source';
                    $isRap = $creanceRaw === 'Reste à payer' || (!$isRep && !$isCon && !$isCnf && !$isRas);

                    $idx = $seqIdx++;
                    $rows[] = [
                        'index' => $idx,
                        'num_op' => $this->extractNumOp($o->num_ordre ?: $ord->num_op, $idx),
                        'date_op' => $o->date_ordre ? date('d/m/Y', strtotime((string) $o->date_ordre)) : ($ord->date_ordonnancement ? date('d/m/Y', strtotime((string) $ord->date_ordonnancement)) : '-'),
                        'beneficiaire' => $o->beneficiaire ?: ($ord->beneficiaire_nom ?: ($ord->fournisseur?->raison_sociale ?: 'Bénéficiaire')),
                        'reference' => $ord->reference ?: '-',
                        'budget' => $ord->budget_type ?: 'Investissement',
                        'creance' => $creanceRaw,
                        'code' => $ord->code_imputation ?: '225320',
                        'art' => $ord->article ?: '415',
                        'par' => $ord->paragraphe ?: '20',
                        'lig' => $ord->ligne ?: '13',
                        'slig' => $ord->sous_ligne ?: '0',
                        'intitule' => $ord->intitule_depense ?: ($ord->liquidation?->objet_liquidation ?: '-'),
                        'paiement_reports' => $isRep ? $montant : 0,
                        'paiement_credits_consolides' => $isCon ? $montant : 0,
                        'paiement_c_neufs' => $isCnf ? $montant : 0,
                        'paiement_retenue_source' => $isRas ? $montant : 0,
                        'paiement_rap' => $isRap ? $montant : 0,
                        'statut' => $o->statut ?: ($ord->statut ?: 'À payer'),
                    ];
                }
            } else {
                $brut = (float) ($ord->montant_brut ?: ($ord->net_a_payer ?: 0));
                $ras  = (float) ($ord->ras_total ?: ($ord->retenue_tva ?: 0));
                $net  = (float) ($ord->net_a_payer ?: ($brut - $ras));
                $creanceRaw = $ord->creance ?: 'Reste à payer';
                $idx = $seqIdx++;
                $rows[] = [
                    'index' => $idx,
                    'num_op' => $this->extractNumOp($ord->num_op ?: $ord->num_ordonnancement, $idx),
                    'date_op' => $ord->date_ordonnancement ? date('d/m/Y', strtotime((string) $ord->date_ordonnancement)) : '-',
                    'beneficiaire' => $ord->beneficiaire_nom ?: ($ord->fournisseur?->raison_sociale ?: 'Bénéficiaire'),
                    'reference' => $ord->reference ?: '-',
                    'budget' => $ord->budget_type ?: 'Investissement',
                    'creance' => $creanceRaw,
                    'code' => $ord->code_imputation ?: '225320',
                    'art' => $ord->article ?: '415',
                    'par' => $ord->paragraphe ?: '20',
                    'lig' => $ord->ligne ?: '13',
                    'slig' => $ord->sous_ligne ?: '0',
                    'intitule' => $ord->intitule_depense ?: ($ord->liquidation?->objet_liquidation ?: '-'),
                    'paiement_reports' => $creanceRaw === 'Reports' ? $brut : (float) ($ord->paiement_reports ?? 0),
                    'paiement_credits_consolides' => $creanceRaw === 'Crédit Consolidés' ? $brut : (float) ($ord->credit_consolide ?? 0),
                    'paiement_c_neufs' => $creanceRaw === 'C.Neufs' ? $brut : (float) ($ord->credit_neuf ?? 0),
                    'paiement_retenue_source' => $creanceRaw === 'Retenue à la source' ? $brut : $ras,
                    'paiement_rap' => $creanceRaw === 'Reste à payer' ? $net : ($brut - $ras),
                    'statut' => $ord->statut ?: 'À payer',
                ];
            }
        }

        return array_values(array_filter($rows, function ($r) use ($statut, $search) {
            if ($statut !== '' && $statut !== 'TOUS' && $r['statut'] !== $statut) {
                return false;
            }
            if ($search === '') {
                return true;
            }
            $term = mb_strtolower($search, 'UTF-8');
            $haystack = implode(' ', [
                $r['num_op'],
                $r['date_op'],
                $r['beneficiaire'],
                $r['reference'],
                $r['budget'],
                $r['creance'],
                $r['code'],
                $r['art'],
                $r['par'],
                $r['lig'],
                $r['slig'],
                "{$r['art']}/{$r['par']}/{$r['lig']}",
                $r['intitule'],
                $r['statut'],
            ]);
            return str_contains(mb_strtolower($haystack, 'UTF-8'), $term);
        }));
    }

    private function extractNumOp(?string $val, int $fallback): string
    {
        if ($val === null || trim($val) === '') {
            return (string) $fallback;
        }
        $digits = preg_replace('/\D+/', '', $val);
        if ($digits !== '') {
            return (string) ((int) $digits);
        }
        return trim($val);
    }

    private function resolveCreanceColors(string $creance): array
    {
        return match ($creance) {
            'Retenue à la source' => ['FFFEF3C7', 'FF78350F'],
            'Reports'             => ['FFF3E8FF', 'FF581C87'],
            'Crédit Consolidés'   => ['FFE0E7FF', 'FF312E81'],
            'C.Neufs'             => ['FFCFFAFE', 'FF164E63'],
            default               => ['FFDBEAFE', 'FF1E40AF'],
        };
    }

    private function resolveStatusColors(string $statut): array
    {
        $low = mb_strtolower(trim($statut), 'UTF-8');
        if (str_contains($low, 'payé') || str_contains($low, 'paye') || str_contains($low, 'totalement ordonnanc')) {
            return ['FFD1FAE5', 'FF047857'];
        }
        if (str_contains($low, 'transmis') || str_contains($low, 'partiellement')) {
            return ['FFDBEAFE', 'FF1D4ED8'];
        }
        if (str_contains($low, 'rejet') || str_contains($low, 'annul')) {
            return ['FFFFE4E6', 'FFBE123C'];
        }
        return ['FFFEF3C7', 'FFB45309'];
    }

    private function populateGenericStyledSheet($sheet, string $tabName, string $title, string $meta, array $rows): void
    {
        $sheet->setTitle(mb_substr($tabName, 0, 31));
        $sheet->setShowGridLines(true);
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

        $labelsMap = [
            'numero_consultation'         => 'N° CONSULTATION',
            'date_consultation'           => 'DATE CONSULTATION',
            'mode_engagement'             => 'MODE ENGAGEMENT',
            'objet_consultation'          => 'OBJET CONSULTATION',
            'fournisseur_nom'             => 'FOURNISSEUR',
            'type_budget'                 => 'TYPE BUDGET',
            'statut_dossier'              => 'STATUT DOSSIER',
            'total_estime'                => 'TOTAL ESTIMÉ (DH)',
            'numero_ordre'                => 'N°',
            'numero_rubrique'             => 'N° ENGAGEMENT',
            'date_engagement'             => 'DATE ENGAGEMENT',
            'reference'                   => 'RÉFÉRENCE',
            'reference_2'                 => 'RÉF. SECONDAIRE',
            'budget'                      => 'BUDGET',
            'code'                        => 'CODE',
            'art'                         => 'ART',
            'par'                         => 'PAR',
            'lig'                         => 'LIG',
            's_lig'                       => 'S.LIG',
            'intitule'                    => 'INTITULÉ',
            'credit_ouvert_cp'            => 'CRÉDIT OUVERT CP',
            'credit_ouvert_ce'            => 'CRÉDIT OUVERT CE',
            'depenses_anterieures_ce'     => 'DÉP. ANTÉRIEURES CE',
            'depenses_anterieures_cp'     => 'DÉP. ANTÉRIEURES CP',
            'depenses_credits_engagement' => 'DÉP. CRÉDITS ENG.',
            'depenses_credits_consolides' => 'DÉP. CRÉDITS CONSOLIDÉS',
            'depenses_rap'                => 'DÉP. RESTE À PAYER',
            'montant_depense_neuf'        => 'DÉPENSE NEUVE',
            'interets_moratoires'         => 'INTÉRÊTS MORATOIRES',
            'montant_engager_neuf'        => 'MONTANT À ENGAGER',
            'objet'                       => 'OBJET',
            'beneficiaire'                => 'BÉNÉFICIAIRE',
        ];

        $ignoredKeys = ['id', 'consultation_id', 'marche_id', 'notification_ligne_id', 'created_at', 'updated_at', 'deleted_at'];
        $keys = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            foreach (array_keys($row) as $k) {
                if (!in_array($k, $ignoredKeys, true) && !in_array($k, $keys, true)) {
                    $keys[] = $k;
                }
            }
        }
        if (empty($keys)) {
            $keys = ['information'];
            $rows = [['information' => 'Aucune donnée pour cette sélection.']];
        }

        $colCount = count($keys);
        $lastCol  = Coordinate::stringFromColumnIndex($colCount);

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', "ROYAUME DU MAROC  •  OFFICE NATIONAL DU CONSEIL AGRICOLE (ONCA)  •  DRCA RSK");
        $sheet->getStyle('A1')->getFont()->setSize(9)->setBold(true)->setColor(new Color('FF475569'));
        $sheet->getRowDimension(1)->setRowHeight(20);

        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', $title);
        $sheet->getStyle('A2')->getFont()->setSize(16)->setBold(true)->setColor(new Color(self::EMERALD_MAIN));
        $sheet->getRowDimension(2)->setRowHeight(28);

        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->setCellValue('A3', $meta);
        $sheet->getStyle('A3')->getFont()->setSize(10)->setItalic(true)->setColor(new Color(self::SLATE_500));
        $sheet->getRowDimension(3)->setRowHeight(20);
        $sheet->getRowDimension(4)->setRowHeight(8);

        foreach ($keys as $colIdx => $key) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
            $label     = $labelsMap[$key] ?? mb_strtoupper(str_replace('_', ' ', (string) $key), 'UTF-8');
            $sheet->setCellValue("{$colLetter}5", $label);
            $sheet->getColumnDimension($colLetter)->setWidth(max(14, min(38, mb_strlen($label) + 6)));
        }

        $sheet->getStyle("A5:{$lastCol}5")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => self::WHITE], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => self::EMERALD_MAIN]],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::EMERALD_BORDER]],
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => self::EMERALD_DARK]],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension(5)->setRowHeight(32);

        $numericKeys = [
            'total_estime', 'credit_ouvert_cp', 'credit_ouvert_ce', 'depenses_anterieures_ce',
            'depenses_anterieures_cp', 'depenses_credits_engagement', 'depenses_credits_consolides',
            'depenses_rap', 'montant_depense_neuf', 'interets_moratoires', 'montant_engager_neuf',
        ];

        $currentRow = 6;
        foreach ($rows as $rowIdx => $row) {
            foreach ($keys as $colIdx => $key) {
                $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
                $val       = is_array($row) ? ($row[$key] ?? '') : '';

                if (in_array($key, $numericKeys, true) || (is_numeric($val) && str_contains((string) $key, 'montant'))) {
                    $numVal = (float) $val;
                    $sheet->setCellValue("{$colLetter}{$currentRow}", $numVal);
                    $sheet->getStyle("{$colLetter}{$currentRow}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;"-"');
                    $sheet->getStyle("{$colLetter}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                } else {
                    $strVal = is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : (string) $val;
                    $sheet->setCellValueExplicit("{$colLetter}{$currentRow}", $strVal, DataType::TYPE_STRING);
                    $sheet->getStyle("{$colLetter}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
                }
            }

            $rowBg = ($rowIdx % 2 === 0) ? self::WHITE : self::EMERALD_ZEBRA;
            $sheet->getStyle("A{$currentRow}:{$lastCol}{$currentRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $rowBg]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => self::SLATE_200]]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'font' => ['size' => 10, 'color' => ['argb' => self::SLATE_800]],
            ]);
            $sheet->getRowDimension($currentRow)->setRowHeight(24);
            $currentRow++;
        }

        $sheet->freezePane('A6');
        if ($currentRow > 6) {
            $sheet->setAutoFilter("A5:{$lastCol}" . ($currentRow - 1));
        }
    }

    private function streamSpreadsheet(Spreadsheet $spreadsheet, string $fileName): StreamedResponse
    {
        $writer = new Xlsx($spreadsheet);

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
