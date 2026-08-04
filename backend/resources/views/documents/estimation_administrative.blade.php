<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Estimation administrative</title>
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
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-center {
            text-align: center;
            font-size: 14px;
        }
        .blue-line {
            border-bottom: 2px solid #000080;
            margin-bottom: 20px;
        }
        .meta-table {
            width: 300px;
            float: right;
            margin-bottom: 20px;
        }
        .meta-table td {
            padding: 3px 10px;
            text-align: right;
        }
        .meta-table td:last-child {
            border: 1px solid #000;
            text-align: center;
            min-width: 60px;
        }
        .title-box {
            clear: both;
            width: 60%;
            margin: 20px auto;
            border: 2px solid #000;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            padding: 8px;
        }
        .info-table {
            margin-bottom: 20px;
            border: 1px solid #000;
        }
        .info-table td {
            border: 1px solid #000;
            padding: 8px;
        }
        .info-label {
            width: 130px;
        }
        .data-table {
            margin-top: 20px;
            margin-bottom: 20px;
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
            width: 300px;
            float: right;
            margin-bottom: 20px;
            border: 2px solid #000;
            border-collapse: collapse;
        }
        .totals-table td {
            border: 1px solid #000;
            padding: 5px 10px;
        }
        .totals-table .label {
            text-align: left;
        }
        .totals-table .amount {
            text-align: right;
        }
        .footer-text {
            clear: both;
            font-weight: bold;
            margin-top: 50px;
            margin-bottom: 40px;
        }
        .signature {
            text-align: right;
            font-weight: normal;
            margin-top: 50px;
            margin-right: 50px;
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
        Estimation administrative
    </div>

    <table class="info-table">
        <tr>
            <td class="info-label">Consultations N° :</td>
            <td>{{ $consultation->numero_consultation }}</td>
            <td class="info-label">Ayant pour objet :</td>
            <td>{{ $consultation->objet }}</td>
        </tr>
        <tr>
            <td colspan="4" style="height: 20px; border-left: 1px solid #000; border-right: 1px solid #000; border-bottom: none; border-top: none;"></td>
        </tr>
        <tr>
            <td class="info-label" style="border-top: none;">Délai d'exécution :</td>
            <td style="border-top: none;">{{ $consultation->delai_execution ?? '0' }}</td>
            <td colspan="2" style="border-top: none;">A compter de la date de notification de la commande</td>
        </tr>
        <tr>
            <td colspan="4" style="border-top: none;">Detail de la consultation</td>
        </tr>
    </table>

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
                    <td>{{ number_format($article->prix_unitaire_ht ?? 0, 2, ',', ' ') }}</td>
                    <td>{{ number_format(($article->quantite ?? 1) * ($article->prix_unitaire_ht ?? 0), 2, ',', ' ') }}</td>
                </tr>
                @endforeach
            @else
                <!-- Fallback line -->
                <tr>
                    <td>1</td>
                    <td class="col-desc">Prestation selon la consultation N° {{ $consultation->numero_consultation }}</td>
                    <td>Forfait</td>
                    <td>1</td>
                    <td>{{ number_format($consultation->montant_estime ?? 0, 2, ',', ' ') }}</td>
                    <td>{{ number_format($consultation->montant_estime ?? 0, 2, ',', ' ') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="label">Total Hors Taxe</td>
            <td class="amount">{{ number_format($consultation->montant_estime ?? 0, 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <td class="label">Montant TVA(20%)</td>
            <td class="amount">{{ number_format(($consultation->montant_estime ?? 0) * 0.20, 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <td class="label">Total TTC</td>
            <td class="amount">{{ number_format(($consultation->montant_estime ?? 0) * 1.20, 2, ',', ' ') }}</td>
        </tr>
    </table>

    <div class="footer-text">
        La présente Estimation est arretée à la somme de : <br>
        <span style="font-weight: normal;">En lettre {{ $montantEnLettres ?? '#NOM?' }}</span>
    </div>

    <div class="signature">
        Le Maitre d'ouvrage
    </div>

</body>
</html>
