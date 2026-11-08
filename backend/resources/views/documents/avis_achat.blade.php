<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $docTitle }}</title>
    <style>
        @page { margin: 105px 38px 62px 38px; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 16px;
            color: #000;
            line-height: 1.65;
            margin: 0;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: middle; }
        .page { position: relative; }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            margin-top: 14px;
        }
        .header-table td { vertical-align: middle; }
        .header-center {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            padding: 0 8px;
        }
        .header-logo { height: 45px; width: auto; }
        .header-line {
            border-bottom: 2px solid #000080;
            margin-bottom: 24px;
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
            font-size: 17px;
            line-height: 1.55;
            text-transform: uppercase;
            margin: 22px auto 30px;
        }
        .sub-title {
            text-align: center;
            font-weight: bold;
            font-size: 17px;
            margin: 22px 0 12px;
            line-height: 1.6;
        }
        .box td, .box th,
        .details-table td,
        .info-grid td,
        .data-table th,
        .data-table td,
        .totals-table td {
            border: 1px solid #000;
            padding: 11px 9px;
            vertical-align: top;
        }
        .box th,
        .data-table th,
        .details-label {
            background: #f4f4f4;
            font-weight: bold;
        }
        .tight td, .tight th { padding: 9px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .underline { text-decoration: underline; }
        .mt { margin-top: 22px; }
        .mb { margin-bottom: 22px; }
        .small { font-size: 12px; }
        .section { font-weight: bold; text-decoration: underline; margin-top: 12px; }
        .article-text { text-align: justify; line-height: 1.6; }
        .signature { margin-top: 28px; page-break-inside: avoid; }
        .signature td { border: 1px solid #000; height: 110px; text-align: center; }
        .signature th { border: 1px solid #000; background: #f4f4f4; padding: 9px; }
        .objet-table { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
        .objet-table td { padding: 11px 6px; border: none; vertical-align: top; }
        .objet-label { font-weight: bold; width: 150px; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
        .details-label { width: 35%; }
        .info-grid { margin-bottom: 28px; }
        .info-grid .label { font-weight: bold; width: 15%; background: #f4f4f4; }
        .imputation-table td {
            border: none;
            border-bottom: 1px solid #000;
            border-left: 1px solid #000;
            text-align: center;
            padding: 7px;
        }
        .imputation-table tr:last-child td { border-bottom: none; }
        .data-table { margin-bottom: 26px; }
        .data-table .col-left { text-align: left; }
        .totals-table { width: 360px; margin-left: auto; margin-bottom: 14px; border: 2px solid #000; }
        .date-box { border: 1px solid #000; padding: 8px 12px; width: 250px; font-weight: bold; margin-top: 34px; }
    </style>
    @include('documents.partials.bc_typography')
    <style>
        body {
            font-size: 16px;
            line-height: 1.65;
        }
    </style>
</head>
<body>
    @include('documents.partials.bc_header')
    @include('documents.partials.bc_footer')
<div class="page">

    <table class="meta mt">
        <tr>
            <td style="width: 7%;">N°</td>
            @php
                $numVal = $doc['numero_consultation'] ?? $doc['numero_avis'] ?? '';
            @endphp
            @if(str_contains($numVal, '/'))
                <td class="bold" style="width: 29%;">{{ $numVal }}</td>
            @else
                <td class="bold" style="width: 5%;">{{ $numVal }}</td>
                <td class="bold" style="width: 24%;">/DRCA-RSK/SA</td>
            @endif
            <td class="center bold">AVIS D'ACHAT SUR BON DE COMMANDE N° {{ $doc['numero_bc'] }}</td>
        </tr>
    </table>

    <table class="box mb">
        <tr>
            <td class="bold center" style="width: 13%;">Objet de la<br>prestation</td>
            <td class="center bold">{{ $doc['objet'] }}</td>
        </tr>
    </table>

    <p class="bold" style="margin-left: 7%;">Bordereau des prix :</p>
    <table class="box">
        <tr>
            <th style="width: 7%;">Prix N°</th>
            <th>Désignation</th>
            <th>Caractéristiques et spécifications</th>
            <th style="width: 12%;">Unité de<br>mesure</th>
            <th style="width: 10%;">Qt</th>
            <th style="width: 8%;">Garanties<br>exigées</th>
        </tr>
        @if(!empty($doc['consistance_lignes']) && is_array($doc['consistance_lignes']))
            @foreach($doc['consistance_lignes'] as $index => $ligne)
                <tr>
                    <td class="center bold">{{ data_get($ligne, 'numero_prix', $index + 1) }}</td>
                    <td class="center">{{ data_get($ligne, 'designation') ?: $doc['objet'] }}</td>
                    <td class="center">{{ data_get($ligne, 'specification') ?: data_get($ligne, 'designation') ?: $doc['objet'] }}</td>
                    <td class="center">{{ data_get($ligne, 'unite_mesure', 'Unité') }}</td>
                    <td class="center">{{ data_get($ligne, 'quantite', 1) }}</td>
                    <td class="center">{{ data_get($ligne, 'garantie_exigee') ?: '-' }}</td>
                </tr>
            @endforeach
        @else
            @forelse($doc['articles'] as $index => $article)
                <tr>
                    <td class="center bold">{{ $doc['numero_prix'] ?: ($index + 1) }}</td>
                    <td class="center">{{ $doc['designation'] ?: $article->designation }}</td>
                    <td class="center">{{ $doc['specification'] ?: $article->designation }}</td>
                    <td class="center">{{ $doc['unite_mesure'] ?: ($article->unite ?? 'Unité') }}</td>
                    <td class="center">{{ $doc['quantite'] ?: ($article->quantite ?? 1) }}</td>
                    <td class="center">{{ $doc['garantie_exigee'] ?: ($article->garantie_exigee ?? '-') }}</td>
                </tr>
            @empty
                <tr>
                    <td class="center bold">{{ $doc['numero_prix'] ?: 1 }}</td>
                    <td class="center">{{ $doc['designation'] ?: $doc['objet'] }}</td>
                    <td class="center">{{ $doc['specification'] ?: $doc['objet'] }}</td>
                    <td class="center">{{ $doc['unite_mesure'] ?: 'Unité' }}</td>
                    <td class="center">{{ $doc['quantite'] ?: 1 }}</td>
                    <td class="center">{{ $doc['garantie_exigee'] ?: '-' }}</td>
                </tr>
            @endforelse
        @endif
    </table>

    <table class="meta mt">
        <tr><td style="width: 28%;">Lieu d'exécution :</td><td class="bold">{{ $doc['lieu_execution'] }}</td></tr>
        <tr><td>Délai de livraison :</td><td class="bold">{{ $doc['delai_livraison'] ?? '' }}{{ !empty($doc['delai_livraison_jours']) ? ' (' . $doc['delai_livraison_jours'] . ')' : '' }}</td></tr>
        <tr><td>Date et heure limites de réception des devis :</td><td class="right bold">{{ $doc['date_limite'] }} à {{ $doc['heure_limite'] }}</td></tr>
    </table>

    <p class="small">Les plis des concurrents sont déposés par voie électronique sur le portail des marchés publics accessible à l'adresse : www.marchespublics.gov.ma</p>

    <table class="meta mt">
        <tr>
            <td class="bold">Le Directeur Régional du Conseil Agricole de Rabat-Salé-Kénitra</td>
            <td class="center bold" style="width: 15%;">Date</td>
            <td class="center bold" style="width: 18%;">{{ $doc['date_document'] }}</td>
        </tr>
    </table>
</div>
</body>
</html>
