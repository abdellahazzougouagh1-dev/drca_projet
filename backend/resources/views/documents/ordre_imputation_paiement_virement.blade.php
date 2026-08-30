<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $docTitle }}</title>
    <style>
        @page { margin: 115px 40px 70px 40px; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            color: #000;
            line-height: 1.45;
            margin: 0;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: middle; }
        .page { position: relative; }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
            margin-top: 10px;
        }
        .header-table td { vertical-align: middle; }
        .header-center {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            padding: 0 8px;
        }
        .header-logo { height: 45px; width: auto; }
        .header-line {
            border-bottom: 2px solid #000080;
            margin-bottom: 18px;
        }
        footer {
            position: fixed;
            bottom: -90px;
            left: 0;
            right: 0;
            border-top: 1px solid #999;
            padding-top: 8px;
            font-size: 8px;
            color: #333;
        }
        .footer-table { width: 100%; border-collapse: collapse; }
        .footer-table td { vertical-align: top; border: none; }
        .footer-left { width: 22%; font-weight: bold; font-size: 9px; line-height: 1.3; }
        .footer-center { width: 56%; text-align: center; font-size: 8px; line-height: 1.35; }
        .footer-right { width: 22%; text-align: right; }
        .footer-logo { height: 48px; width: auto; }
        .footer-green-logo { height: 28px; width: auto; }
        .meta { width: 100%; margin-bottom: 15px; }
        .meta td { vertical-align: top; padding: 3px 4px; }
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            line-height: 1.45;
            text-transform: uppercase;
            margin: 18px auto 24px;
        }
        .sub-title {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin: 18px 0 8px;
            line-height: 1.5;
        }
        .box td, .box th,
        .details-table td,
        .info-grid td,
        .data-table th,
        .data-table td,
        .totals-table td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
        }
        .box th,
        .data-table th,
        .details-label {
            background: #f4f4f4;
            font-weight: bold;
        }
        .tight td, .tight th { padding: 6px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .underline { text-decoration: underline; }
        .mt { margin-top: 15px; }
        .mb { margin-bottom: 15px; }
        .small { font-size: 10px; }
        .section { font-weight: bold; text-decoration: underline; margin-top: 8px; }
        .article-text { text-align: justify; line-height: 1.45; }
        .signature { margin-top: 20px; page-break-inside: avoid; }
        .signature td { border: 1px solid #000; height: 90px; text-align: center; }
        .signature th { border: 1px solid #000; background: #f4f4f4; padding: 6px; }
        .objet-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .objet-table td { padding: 8px 4px; border: none; vertical-align: top; }
        .objet-label { font-weight: bold; width: 150px; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .details-label { width: 35%; }
        .info-grid { margin-bottom: 20px; }
        .info-grid .label { font-weight: bold; width: 15%; background: #f4f4f4; }
        .imputation-table td {
            border: none;
            border-bottom: 1px solid #000;
            border-left: 1px solid #000;
            text-align: center;
            padding: 4px;
        }
        .imputation-table tr:last-child td { border-bottom: none; }
        .data-table { margin-bottom: 18px; }
        .data-table .col-left { text-align: left; }
        .totals-table { width: 360px; margin-left: auto; margin-bottom: 14px; border: 2px solid #000; }
        .date-box { border: 1px solid #000; padding: 6px 10px; width: 250px; font-weight: bold; margin-top: 28px; }
    </style>
</head>
<body>
    @include('documents.partials.bc_header')
    @include('documents.partials.bc_footer')
<div class="page">

    <div class="title">Ordre d'imputation, paiement et virement</div>
    <table class="box">
        <tr><td class="bold">Exercice</td><td>{{ $consultation->annee ?? date('Y') }}</td><td class="bold">Budget</td><td>{{ $consultation->type_budget ?? 'Investissement' }}</td></tr>
        <tr><td class="bold">N° d'ordre</td><td>{{ $doc['numero_engagement'] }}</td><td class="bold">Date</td><td>{{ $doc['date_document'] }}</td></tr>
        <tr><td class="bold">Objet</td><td colspan="3">{{ $doc['objet'] }}</td></tr>
        <tr><td class="bold">Imputation</td><td colspan="3">ART {{ $consultation->budget->art ?? '' }} / PAR {{ $consultation->budget->par ?? '' }} / LIG {{ $consultation->budget->lig ?? '' }} / Code {{ $consultation->budget->code_imputation ?? '' }}</td></tr>
        <tr><td class="bold">Bénéficiaire</td><td>{{ $doc['societe'] }}</td><td class="bold">Montant TTC</td><td class="right bold">{{ number_format($doc['total_ttc'], 2, ',', ' ') }}</td></tr>
        <tr><td class="bold">En lettres</td><td colspan="3">{{ $doc['montant_en_lettres'] }}</td></tr>
        <tr><td class="bold">RIB</td><td colspan="3">{{ $doc['rib'] ?: ($doc['fournisseur']->rib ?? '........................................') }}</td></tr>
        <tr><td class="bold">Facture N°</td><td>{{ $doc['numero_facture'] }}</td><td class="bold">Bon à payer</td><td>{{ $doc['date_bon_payer'] }}</td></tr>
    </table>
    <table class="signature mt"><tr><th>Service fait</th><th>Ordonnateur</th><th>Trésorerie</th></tr><tr><td></td><td></td><td></td></tr></table>
</div>
</body>
</html>
