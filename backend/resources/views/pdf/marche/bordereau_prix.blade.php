<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau des prix - {{ $doc['num_marche'] }}</title>
    <style>
        @page { margin: 12mm 10mm; size: A4 portrait; }
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #000; padding: 5px 6px; vertical-align: middle; }

        .header-table td { border: none; padding: 0 4px; vertical-align: middle; }
        .header-center {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            line-height: 1.35;
        }
        .header-logo { height: 58px; width: auto; max-width: 120px; }
        .header-line { border-bottom: 2px solid #1f4e79; margin: 6px 0 12px; }

        .doc-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin: 8px 0 4px;
        }
        .doc-title .article-num { color: #c65911; }
        .marche-ref {
            text-align: right;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 4px;
        }
        .aoo-ref {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 10px;
        }
        .aoo-ref .right { float: right; }

        .objet-box td {
            padding: 8px 10px;
            vertical-align: middle;
        }
        .objet-label {
            width: 22%;
            text-align: center;
            font-weight: bold;
        }
        .objet-text {
            text-align: left;
            font-weight: bold;
        }

        .section-title {
            text-align: center;
            font-weight: bold;
            font-style: italic;
            margin: 12px 0 6px;
            font-size: 11px;
        }

        .data-table th {
            font-weight: bold;
            text-align: center;
            background-color: #f7f7f7;
        }
        .data-table .col-desc { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .totals-wrap { margin-top: 8px; }
        .totals-table {
            width: 240px;
            float: right;
            border: 2px solid #000;
        }
        .totals-table td {
            padding: 6px 8px;
        }
        .totals-table .label {
            text-align: center;
            font-weight: bold;
        }
        .totals-table .amount {
            text-align: center;
            font-weight: bold;
            width: 90px;
        }

        .footer-text {
            clear: both;
            margin-top: 28px;
            font-weight: bold;
            font-size: 11px;
            line-height: 1.6;
        }
        .footer-letters {
            margin-top: 6px;
            font-weight: bold;
            font-style: italic;
        }
    </style>
</head>
<body>

@php
    $logoOnca = \App\Support\AooDocumentHelper::embedImage('images/logo-onca.png')
        ?? \App\Support\AooDocumentHelper::embedImage('images/logo_onca.png');
    $sceauMaroc = \App\Support\AooDocumentHelper::embedImage('images/sceau-maroc.png')
        ?? \App\Support\AooDocumentHelper::embedImage('images/logo_royaume.png');
@endphp

<table class="header-table">
    <tr>
        <td style="width: 22%; text-align: left;">
            @if($logoOnca)<img src="{{ $logoOnca }}" alt="ONCA" class="header-logo">@endif
        </td>
        <td class="header-center" style="width: 56%;">
            Direction Régionale du Conseil Agricole<br>Rabat-Salé-Kénitra
        </td>
        <td style="width: 22%; text-align: right;">
            @if($sceauMaroc)<img src="{{ $sceauMaroc }}" alt="Royaume du Maroc" class="header-logo">@endif
        </td>
    </tr>
</table>
<div class="header-line"></div>

<div class="doc-title">
    <span class="article-num">Article 33.</span> Bordereau des prix - Detail estimatif
</div>

<div class="marche-ref">Marché N° {{ $doc['num_marche'] }}</div>

<div class="aoo-ref">
    Issu de la consultation N°: <strong>{{ $doc['num_aoo'] }}</strong>
    <span class="right">du {{ $doc['date_aoo'] ?: '................' }}</span>
</div>

<table class="objet-box">
    <tr>
        <td class="objet-label">Objet du présent Marché</td>
        <td class="objet-text">{{ $doc['objet_marche'] ?: '—' }}</td>
    </tr>
</table>

<div class="section-title">Detail du bordereau</div>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 6%;">N°</th>
            <th style="width: 44%;">Designation</th>
            <th style="width: 10%;">Unité</th>
            <th style="width: 10%;">Qt</th>
            <th style="width: 15%;">P.U HT</th>
            <th style="width: 15%;">Montant HT</th>
        </tr>
    </thead>
    <tbody>
        @forelse($doc['lignes'] as $ligne)
            <tr>
                <td class="text-center">{{ $ligne['numero'] }}</td>
                <td class="col-desc">{{ $ligne['designation'] }}</td>
                <td class="text-center">{{ $ligne['unite'] }}</td>
                <td class="text-center">{{ $ligne['quantite_formate'] }}</td>
                <td class="text-right">{{ $ligne['prix_unitaire_formate'] }}</td>
                <td class="text-right">{{ $ligne['montant_ht_formate'] }}</td>
            </tr>
        @empty
            <tr>
                <td class="text-center">—</td>
                <td class="col-desc">Aucune ligne enregistrée</td>
                <td></td><td></td><td></td><td></td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="totals-wrap">
    <table class="totals-table">
        <tr>
            <td class="label">Total Hors Taxe</td>
            <td class="amount">{{ $doc['total_ht_formate'] }}</td>
        </tr>
        <tr>
            <td class="label">Montant TVA (20%)</td>
            <td class="amount">{{ $doc['tva_formate'] }}</td>
        </tr>
        <tr>
            <td class="label">Total TTC</td>
            <td class="amount">{{ $doc['total_ttc_formate'] }}</td>
        </tr>
    </table>
</div>

<div class="footer-text">
    Le présent Bordereau est arrêté à la somme de : {{ $doc['total_ttc_formate'] }} MAD
    <div class="footer-letters">En lettre : {{ $doc['total_ttc_lettres'] }}</div>
</div>

</body>
</html>
