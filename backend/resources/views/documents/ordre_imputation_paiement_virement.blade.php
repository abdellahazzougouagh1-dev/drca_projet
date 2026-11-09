<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $docTitle }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 102px 36px 96px 36px;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12.5px;
            color: #000;
            line-height: 1.45;
            margin: 0;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: middle; }
        .page { position: relative; }
        .title {
            background: #e5e0d8;
            border: 2px solid #000;
            text-align: center;
            font-weight: bold;
            font-size: 15px;
            line-height: 1.4;
            text-transform: uppercase;
            padding: 10px 14px;
            margin: 8px auto 22px;
            width: 86%;
        }
        .box {
            border: 1.5px solid #000;
            margin-bottom: 20px;
        }
        .box td, .box th {
            border: 1px solid #000;
            padding: 13px 10px;
            vertical-align: middle;
            font-size: 12.5px;
        }
        .box .lbl {
            background: #f5f5f5;
            font-weight: bold;
            width: 22%;
        }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .mt { margin-top: 16px; }
        .signature {
            margin-top: 18px;
            border: 1.5px solid #000;
            page-break-inside: avoid;
        }
        .signature th {
            border: 1px solid #000;
            background: #e5e0d8;
            padding: 8px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .signature td {
            border: 1px solid #000;
            height: 175px;
            text-align: center;
            vertical-align: top;
        }
    </style>
    @include('documents.partials.bc_typography')
</head>
<body>
    @include('documents.partials.bc_header')
    @include('documents.partials.bc_footer')
<div class="page">

    <div class="title">ORDRE D'IMPUTATION, DE PAIEMENT ET DE VIREMENT</div>
    <table class="box">
        <tr><td class="lbl">Exercice</td><td>{{ $consultation->annee ?? date('Y') }}</td><td class="lbl">Budget</td><td>{{ $consultation->type_budget ?? 'Investissement' }}</td></tr>
        <tr><td class="lbl">N° d'ordre</td><td class="bold">{{ $doc['numero_engagement'] }}</td><td class="lbl">Date</td><td>{{ $doc['date_document'] }}</td></tr>
        <tr><td class="lbl">Objet</td><td colspan="3" class="bold">{{ $doc['objet'] }}</td></tr>
        <tr><td class="lbl">Imputation</td><td colspan="3" class="bold">ART {{ $consultation->budget->art ?? '' }} / PAR {{ $consultation->budget->par ?? '' }} / LIG {{ $consultation->budget->lig ?? '' }} / Code {{ $consultation->budget->code_imputation ?? '' }}</td></tr>
        <tr><td class="lbl">Bénéficiaire</td><td class="bold" style="text-transform: uppercase;">{{ $doc['societe'] }}</td><td class="lbl">Montant TTC</td><td class="right bold" style="font-size: 13px;">{{ number_format($doc['total_ttc'], 2, ',', ' ') }} DH</td></tr>
        <tr><td class="lbl">En lettres :</td><td colspan="3" style="font-style: italic;">{{ $doc['montant_en_lettres'] }}</td></tr>
        <tr><td class="lbl">RIB</td><td colspan="3" class="col-num">{{ $doc['rib'] ?: ($doc['fournisseur']->rib ?? '........................................') }}</td></tr>
        <tr><td class="lbl">Facture N°</td><td class="bold">{{ $doc['numero_facture'] }}</td><td class="lbl">Bon à payer</td><td>{{ $doc['date_bon_payer'] }}</td></tr>
    </table>
    <table class="signature mt"><tr><th style="width: 33.33%;">Service fait</th><th style="width: 33.33%;">Ordonnateur</th><th style="width: 33.34%;">Trésorerie</th></tr><tr><td></td><td></td><td></td></tr></table>
</div>
</body>
</html>
