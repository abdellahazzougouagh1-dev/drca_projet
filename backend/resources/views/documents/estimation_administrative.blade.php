<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Estimation administrative</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 18px 34px 96px 34px;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11.5px;
            color: #000;
            margin: 0;
            padding: 0;
            line-height: 1.42;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            margin-bottom: 4px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-center {
            text-align: center;
            font-size: 13.5px;
            font-weight: bold;
            line-height: 1.35;
            padding: 0 8px;
        }
        .blue-line {
            border-bottom: 2px solid #000080;
            margin-bottom: 12px;
        }
        .meta-table {
            width: 280px;
            float: right;
            margin-bottom: 10px;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 4px 10px;
            text-align: right;
            font-size: 11px;
            font-weight: bold;
        }
        .meta-table td:last-child {
            border: 1.5px solid #000;
            text-align: center;
            min-width: 70px;
        }
        .title-box {
            clear: both;
            width: 70%;
            margin: 8px auto 14px auto;
            border: 2.5px solid #000;
            text-align: center;
            font-size: 16.5px;
            font-weight: bold;
            padding: 8px 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-table {
            margin-bottom: 14px;
            border: 1.5px solid #000;
        }
        .info-table td {
            border: 1px solid #000;
            padding: 7.5px 9px;
            font-size: 11.5px;
        }
        .info-label {
            width: 140px;
            font-weight: bold;
            background-color: #f2f2f2;
        }
        .data-table {
            margin-top: 8px;
            margin-bottom: 14px;
            border: 1.5px solid #000;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 8px 7px;
            text-align: center;
            font-size: 11.5px;
        }
        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-transform: uppercase;
        }
        .data-table .col-desc {
            text-align: left;
        }
        .empty-row td {
            height: 28px;
        }
        .totals-wrapper {
            width: 100%;
            margin-bottom: 18px;
        }
        .totals-table {
            width: 360px;
            float: right;
            border: 2px solid #000;
            border-collapse: collapse;
        }
        .totals-table td {
            border: 1px solid #000;
            padding: 7.5px 10px;
            font-size: 11.5px;
            font-weight: bold;
        }
        .totals-table .label {
            text-align: left;
            background-color: #f2f2f2;
            width: 60%;
        }
        .totals-table .amount {
            text-align: right;
            width: 40%;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        .closing-table {
            clear: both;
            width: 100%;
            margin-top: 18px;
        }
        .closing-table td {
            vertical-align: top;
            padding: 0;
        }
        .footer-text {
            font-weight: bold;
            font-size: 12px;
            line-height: 1.65;
        }
        .signature {
            text-align: right;
            font-weight: bold;
            font-size: 12.5px;
            padding-top: 14px;
            padding-right: 16px;
        }
    </style>
    @include('documents.partials.bc_typography')
</head>
<body>
    @include('documents.partials.bc_footer')

    <table class="header-table">
        <tr>
            <td style="width: 24%; text-align: left;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo-onca.png'))) }}" alt="Logo ONCA" style="height: 68px; width: auto;">
            </td>
            <td class="header-center" style="width: 52%;">
                Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
            </td>
            <td style="width: 24%; text-align: right;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) }}" alt="Sceau Maroc" style="height: 68px; width: auto;">
            </td>
        </tr>
    </table>
    <div class="blue-line"></div>

    <table class="meta-table">
        <tr>
            <td>Exercice</td>
            <td>{{ $consultation->exercice_budgetaire ?? date('y') }}</td>
        </tr>
        <tr>
            <td>Budget</td>
            <td>{{ $consultation->budget->montant ?? '0' }}</td>
        </tr>
    </table>

    <div class="title-box">
        ESTIMATION ADMINISTRATIVE
    </div>

    <table class="info-table">
        <tr>
            <td class="info-label">Consultation N° :</td>
            <td style="font-weight: bold;">{{ $consultation->numero_consultation }}</td>
            <td class="info-label">Ayant pour objet :</td>
            <td style="font-weight: bold;">{{ $consultation->objet ?: ($consultation->objet_consultation ?? '') }}</td>
        </tr>
        <tr>
            <td class="info-label">Délai d'exécution :</td>
            <td style="font-weight: bold;">{{ $consultation->delai_execution ?? '0' }}</td>
            <td colspan="2">À compter de la date de notification de la commande</td>
        </tr>
        <tr>
            <td colspan="4" style="background: #f2f2f2; font-weight: bold; text-align: center;">Détail de la consultation</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 6%;">N°</th>
                <th style="width: 44%;">Désignation</th>
                <th style="width: 11%;">Unité</th>
                <th style="width: 9%;">Qt</th>
                <th style="width: 15%;">P.U HT</th>
                <th style="width: 15%;">Montant HT</th>
            </tr>
        </thead>
        <tbody>
            @php $lineCount = 0; @endphp
            @if(isset($consultation->articles) && count($consultation->articles) > 0)
                @foreach($consultation->articles as $key => $article)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td class="col-desc">{{ $article->designation ?? '' }}</td>
                    <td>{{ $article->unite ?? 'Forfait' }}</td>
                    <td>{{ $article->quantite ?? 1 }}</td>
                    <td style="text-align: right;">{{ number_format($article->prix_unitaire_ht ?? 0, 2, ',', ' ') }}</td>
                    <td style="text-align: right;">{{ number_format(($article->quantite ?? 1) * ($article->prix_unitaire_ht ?? 0), 2, ',', ' ') }}</td>
                </tr>
                @php $lineCount++; @endphp
                @endforeach
            @else
                <!-- Fallback line -->
                <tr>
                    <td>1</td>
                    <td class="col-desc">Prestation selon la consultation N° {{ $consultation->numero_consultation }}</td>
                    <td>Forfait</td>
                    <td>1</td>
                    <td style="text-align: right;">{{ number_format($consultation->montant_estime ?? 0, 2, ',', ' ') }}</td>
                    <td style="text-align: right;">{{ number_format($consultation->montant_estime ?? 0, 2, ',', ' ') }}</td>
                </tr>
                @php $lineCount = 1; @endphp
            @endif
            @for($i = max(0, 6 - $lineCount); $i > 0; $i--)
                <tr class="empty-row">
                    <td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
            @endfor
        </tbody>
    </table>

    <div class="totals-wrapper clearfix">
        <table class="totals-table">
            <tr>
                <td class="label">Total Hors Taxes (HT)</td>
                <td class="amount">{{ number_format($consultation->montant_estime ?? 0, 2, ',', ' ') }}</td>
            </tr>
            <tr>
                <td class="label">Montant TVA (20%)</td>
                <td class="amount">{{ number_format(($consultation->montant_estime ?? 0) * 0.20, 2, ',', ' ') }}</td>
            </tr>
            <tr>
                <td class="label">Total TTC</td>
                <td class="amount">{{ number_format(($consultation->montant_estime ?? 0) * 1.20, 2, ',', ' ') }}</td>
            </tr>
        </table>
    </div>

    <table class="closing-table">
        <tr>
            <td style="width: 65%;" class="footer-text">
                La présente estimation est arrêtée à la somme de :<br>
                <span style="font-weight: normal;">En lettres : <strong>{{ $montantEnLettres ?? '#NOM?' }}</strong></span>
            </td>
            <td style="width: 35%;" class="signature">
                Le Maître d'ouvrage
            </td>
        </tr>
    </table>

</body>
</html>
