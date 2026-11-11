<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\RegistreExportController;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class RegistreExportControllerTest extends TestCase
{
    public function test_export_ordonnancement_excel_generates_styled_xlsx_for_filtered_rows(): void
    {
        $request = Request::create('/api/registres/ordonnancement/export-excel', 'POST', [
            'procedure' => 'Bon de commande',
            'exercice' => '2026',
            'statut' => 'Payé',
            'search' => 'Solutions',
            'rows' => [
                [
                    'index' => 1,
                    'num_op' => '12',
                    'date_op' => '25/02/2026',
                    'beneficiaire' => 'Solutions IT Plus EURL',
                    'reference' => 'BC-01/2026/DRCA-RSK',
                    'budget' => 'Fonctionnement',
                    'creance' => 'Reste à payer',
                    'code' => '2101005',
                    'art' => '210',
                    'par' => '10',
                    'lig' => '05',
                    'slig' => '0',
                    'intitule' => 'Acquisition de fournitures de bureau',
                    'paiement_reports' => 0,
                    'paiement_credits_consolides' => 0,
                    'paiement_c_neufs' => 0,
                    'paiement_retenue_source' => 0,
                    'paiement_rap' => 12500.5,
                    'statut' => 'Payé',
                ],
                [
                    'index' => 2,
                    'num_op' => '15',
                    'date_op' => '10/03/2026',
                    'beneficiaire' => 'Alpha Equipement',
                    'reference' => 'BC-02/2026/DRCA-RSK',
                    'budget' => 'Investissement',
                    'creance' => 'Reports',
                    'code' => '225320',
                    'art' => '415',
                    'par' => '20',
                    'lig' => '13',
                    'slig' => '0',
                    'intitule' => 'Matériel informatique',
                    'paiement_reports' => 5000,
                    'paiement_credits_consolides' => 0,
                    'paiement_c_neufs' => 0,
                    'paiement_retenue_source' => 0,
                    'paiement_rap' => 0,
                    'statut' => 'Rejeté',
                ],
            ],
        ]);

        $controller = new RegistreExportController();
        $response = $controller->exportOrdonnancementExcel($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString(
            'Registre_ordonnancements_Bon_de_commande_2026_filtre.xlsx',
            (string) $response->headers->get('Content-Disposition')
        );

        ob_start();
        $response->sendContent();
        $binary = (string) ob_get_clean();

        $this->assertNotEmpty($binary);

        $tmpFile = tempnam(sys_get_temp_dir(), 'ord_xlsx_') . '.xlsx';
        file_put_contents($tmpFile, $binary);

        try {
            $spreadsheet = IOFactory::load($tmpFile);
            $sheet = $spreadsheet->getActiveSheet();

            $this->assertSame('Ordonnancements', $sheet->getTitle());
            $this->assertSame('REGISTRE DES ORDONNANCEMENTS', $sheet->getCell('A2')->getValue());
            $this->assertStringContainsString('Procédure : Bon de commande', (string) $sheet->getCell('A3')->getValue());
            $this->assertStringContainsString('Recherche : "Solutions"', (string) $sheet->getCell('A3')->getValue());

            $this->assertSame('N°', $sheet->getCell('A5')->getValue());
            $this->assertSame('N° OP', $sheet->getCell('B5')->getValue());
            $this->assertSame('BÉNÉFICIAIRE', $sheet->getCell('D5')->getValue());
            $this->assertSame('PAIEMENT RESTE À PAYER', $sheet->getCell('R5')->getValue());
            $this->assertSame('PAYÉ / REJET', $sheet->getCell('S5')->getValue());

            $this->assertSame('FF047857', $sheet->getStyle('A5')->getFill()->getStartColor()->getARGB());
            $this->assertSame('FFFFFFFF', $sheet->getStyle('A5')->getFont()->getColor()->getARGB());

            $this->assertSame('Solutions IT Plus EURL', $sheet->getCell('D6')->getValue());
            $this->assertEquals(12500.5, $sheet->getCell('R6')->getValue());
            $this->assertSame('#,##0.00;-#,##0.00;"-"', $sheet->getStyle('N6')->getNumberFormat()->getFormatCode());
            $this->assertSame('FFD1FAE5', $sheet->getStyle('S6')->getFill()->getStartColor()->getARGB());
            $this->assertSame('FFFFE4E6', $sheet->getStyle('S7')->getFill()->getStartColor()->getARGB());

            $this->assertSame('TOTAL (2 ligne(s))', $sheet->getCell('A8')->getValue());
            $this->assertSame('=SUM(R6:R7)', $sheet->getCell('R8')->getValue());
            $this->assertSame('=SUM(N8:R8)', $sheet->getCell('S8')->getValue());
        } finally {
            @unlink($tmpFile);
        }
    }

    public function test_export_ordonnancement_excel_handles_empty_filtered_rows(): void
    {
        $request = Request::create('/api/registres/ordonnancement/export-excel', 'POST', [
            'procedure' => 'TOUS',
            'exercice' => '2026',
            'statut' => 'TOUS',
            'search' => 'Inexistant',
            'rows' => [],
        ]);

        $controller = new RegistreExportController();
        $response = $controller->exportOrdonnancementExcel($request);

        ob_start();
        $response->sendContent();
        $binary = (string) ob_get_clean();

        $tmpFile = tempnam(sys_get_temp_dir(), 'ord_empty_') . '.xlsx';
        file_put_contents($tmpFile, $binary);

        try {
            $spreadsheet = IOFactory::load($tmpFile);
            $sheet = $spreadsheet->getActiveSheet();

            $this->assertSame('Aucun ordonnancement correspondant à cette recherche.', $sheet->getCell('A6')->getValue());
            $this->assertSame('TOTAL (0 ligne(s))', $sheet->getCell('A7')->getValue());
        } finally {
            @unlink($tmpFile);
        }
    }
}
