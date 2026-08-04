<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau des prix</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #000;
            margin: 20px;
            line-height: 1.4;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            margin-bottom: 5px;
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-center {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
        }
        .blue-line {
            border-bottom: 2px solid #000080;
            margin-bottom: 20px;
        }
        .doc-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .doc-subtitle {
            font-size: 14px;
            margin-bottom: 20px;
        }
        .objet-box {
            border: 2px solid #000;
            padding: 10px;
            margin-bottom: 30px;
            min-height: 50px;
        }
        .objet-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 10px;
        }
        .data-table {
            margin-bottom: 30px;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        .data-table th {
            font-weight: bold;
        }
        .data-table .col-desc {
            text-align: left;
        }
        .totals-table {
            width: 350px;
            float: right;
            margin-bottom: 30px;
            border: 2px solid #000;
            border-collapse: collapse;
        }
        .totals-table td {
            border: 1px solid #000;
            padding: 8px 10px;
        }
        .totals-table .label {
            text-align: center;
            font-weight: normal;
        }
        .totals-table .amount {
            text-align: center;
            width: 80px;
        }
        .footer-text {
            clear: both;
            font-weight: bold;
            margin-top: 50px;
        }
        .footer-text .dots {
            display: inline-block;
            width: 150px;
            border-bottom: 1px dotted #000;
        }
        .footer-text .long-dots {
            display: inline-block;
            width: 300px;
            border-bottom: 1px dotted #000;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 25%;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo-onca.png'))) }}" alt="Logo ONCA" style="height: 70px; width: auto;">
            </td>
            <td class="header-center" style="width: 50%;">
                Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
            </td>
            <td style="width: 25%; text-align: right;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) }}" alt="Sceau Maroc" style="height: 70px; width: auto;">
            </td>
        </tr>
    </table>
    <div class="blue-line"></div>

    <div class="doc-title">
        Bordereau des prix
    </div>
    
    <div class="doc-subtitle">
        Issu de la consultation N°: {{ $consultation->numero_consultation }} du {{ \Carbon\Carbon::parse($consultation->date_consultation ?? now())->format('d/m/Y') }}
    </div>

    <div class="objet-box">
        <div class="objet-title">Objet de la convention</div>
        <div>{{ $consultation->objet }}</div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">N°</th>
                <th style="width: 45%;">Designation</th>
                <th style="width: 10%;">Unité</th>
                <th style="width: 10%;">Qt</th>
                <th style="width: 15%;">P.U HT</th>
                <th style="width: 15%;">Montant HT</th>
            </tr>
        </thead>
        <tbody>
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
            @endif
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="label">Total Hors Taxe</td>
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

    <div class="footer-text">
        Le présent Bordereau est arreté à la somme de: <span class="dots"></span> <br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;En lettre <span class="long-dots"></span>
    </div>

</body>
</html>
