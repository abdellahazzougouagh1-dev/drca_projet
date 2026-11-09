<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau des prix</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 18px 34px 96px 34px;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 0;
            line-height: 1.45;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            margin-bottom: 4px;
            width: 100%;
            border-collapse: collapse;
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
            margin-bottom: 14px;
        }
        .title-box {
            border: 2.5px solid #000;
            text-align: center;
            padding: 9px 16px;
            margin: 4px auto 16px auto;
            width: 72%;
        }
        .doc-title {
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        .doc-subtitle {
            font-size: 12.5px;
            font-weight: bold;
        }
        .objet-box {
            border: 1.5px solid #000;
            padding: 10px 12px;
            margin-bottom: 16px;
            min-height: 46px;
        }
        .objet-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 6px;
            font-size: 12px;
        }
        .data-table {
            margin-bottom: 18px;
            border: 1.5px solid #000;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 8.5px 7px;
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
            height: 29px;
        }
        .totals-wrapper {
            width: 100%;
            margin-bottom: 24px;
        }
        .totals-table {
            width: 380px;
            float: right;
            border: 2px solid #000;
            border-collapse: collapse;
        }
        .totals-table td {
            border: 1px solid #000;
            padding: 8px 10px;
            font-size: 12px;
            font-weight: bold;
        }
        .totals-table .label {
            text-align: left;
            background-color: #f2f2f2;
            width: 65%;
        }
        .totals-table .amount {
            text-align: center;
            width: 35%;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        .footer-text {
            clear: both;
            font-weight: bold;
            margin-top: 28px;
            line-height: 2.0;
            font-size: 12.5px;
        }
        .footer-text .dots {
            display: inline-block;
            width: 180px;
            border-bottom: 1px dotted #000;
        }
        .footer-text .long-dots {
            display: inline-block;
            width: 340px;
            border-bottom: 1px dotted #000;
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

    <div class="title-box">
        <div class="doc-title">Bordereau des prix</div>
        <div class="doc-subtitle">
            Issu de la consultation N° : {{ $consultation->numero_consultation }} du {{ \Carbon\Carbon::parse($consultation->date_consultation ?? now())->format('d/m/Y') }}
        </div>
    </div>

    <div class="objet-box">
        <div class="objet-title">Objet de la convention</div>
        <div style="font-weight: bold;">{{ $consultation->objet ?: ($consultation->objet_consultation ?? '') }}</div>
    </div>

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
                    <td>-</td>
                    <td>-</td>
                </tr>
                @php $lineCount++; @endphp
                @endforeach
            @else
                <!-- Ligne de secours pour affichage structurel -->
                <tr>
                    <td>1</td>
                    <td class="col-desc">Prestations demandées</td>
                    <td>Forfait</td>
                    <td>1</td>
                    <td>-</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td class="col-desc"></td>
                    <td></td>
                    <td></td>
                    <td>-</td>
                    <td>-</td>
                </tr>
                @php $lineCount = 2; @endphp
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
                <td class="amount">-</td>
            </tr>
            <tr>
                <td class="label">TVA ({{ $consultation->taux_tva ?? 20 }}%)</td>
                <td class="amount">-</td>
            </tr>
            <tr>
                <td class="label">Total TTC</td>
                <td class="amount">-</td>
            </tr>
        </table>
    </div>

    <div class="footer-text">
        Le présent bordereau est arrêté à la somme de : <span class="dots"></span> <br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;En lettres : <span class="long-dots"></span>
    </div>

</body>
</html>
