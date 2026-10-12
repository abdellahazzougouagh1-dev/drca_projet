<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dossier d'Estimation - {{ $consultation->numero_consultation }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .royaume {
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
        }
        .onca {
            font-weight: bold;
            font-size: 16px;
            margin-top: 5px;
            text-transform: uppercase;
        }
        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 40px 0 20px 0;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .info-box {
            border: 1px solid #000;
            padding: 15px;
            margin-bottom: 30px;
            background-color: #f9f9f9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #eee;
            font-weight: bold;
        }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .page-break { page-break-after: always; }
        .totals {
            width: 50%;
            float: right;
            margin-top: 20px;
        }
        .clear { clear: both; }
        .signature {
            margin-top: 50px;
            text-align: right;
            padding-right: 50px;
        }
    </style>
</head>
<body>

    <!-- ENTÊTE COMMUNE -->
    <div class="header">
        <div class="royaume">Royaume du Maroc</div>
        <div>Ministère de l'Agriculture, de la Pêche Maritime, du Développement Rural et des Eaux et Forêts</div>
        <div class="onca">Office National du Conseil Agricole (ONCA)</div>
    </div>

    <!-- PAGE 1 : ESTIMATION ADMINISTRATIVE -->
    <div class="title">ESTIMATION ADMINISTRATIVE</div>
    <div class="info-box">
        <p><strong>Consultation N° :</strong> {{ $consultation->numero_consultation }}</p>
        <p><strong>Objet :</strong> {{ $consultation->objet_consultation }}</p>
        <p><strong>Date :</strong> {{ $consultation->date_consultation }}</p>
        <p><strong>Mode d'engagement :</strong> {{ $consultation->mode_engagement }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>N° Prix</th>
                <th>Désignation</th>
                <th>Unité</th>
                <th>Quantité (A)</th>
                <th>Prix Unitaire HT (B)</th>
                <th>Montant HT (A x B)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($consultation->prestations as $index => $prestation)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="text-left">{{ $prestation->designation }}</td>
                <td>{{ $prestation->unite }}</td>
                <td>{{ number_format($prestation->quantite, 2, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($prestation->prix_unitaire_ht, 2, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($prestation->montant_ht, 2, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <th class="text-left">Total HT :</th>
                <td class="text-right font-bold">{{ number_format($totalHT, 2, ',', ' ') }} MAD</td>
            </tr>
            <tr>
                <th class="text-left">Total TVA :</th>
                <td class="text-right">{{ number_format($totalTVA, 2, ',', ' ') }} MAD</td>
            </tr>
            <tr>
                <th class="text-left">Total TTC :</th>
                <td class="text-right font-bold" style="font-size:14px;">{{ number_format($totalTTC, 2, ',', ' ') }} MAD</td>
            </tr>
        </table>
    </div>
    <div class="clear"></div>
    <div class="signature">
        <p><strong>Fait à Rabat, le {{ date('d/m/Y') }}</strong></p>
        <p><em>Le Directeur</em></p>
    </div>


    <div class="page-break"></div>


    <!-- PAGE 2 : BORDEREAU DES PRIX UNITAIRES (BPU) -->
    <div class="header">
        <div class="royaume">Royaume du Maroc</div>
        <div>Ministère de l'Agriculture, de la Pêche Maritime, du Développement Rural et des Eaux et Forêts</div>
        <div class="onca">Office National du Conseil Agricole (ONCA)</div>
    </div>

    <div class="title">BORDEREAU DES PRIX UNITAIRES (BPU)</div>
    <div class="info-box">
        <p><strong>Consultation N° :</strong> {{ $consultation->numero_consultation }}</p>
        <p><strong>Objet :</strong> {{ $consultation->objet_consultation }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>N° Prix</th>
                <th>Désignation des Prestations</th>
                <th>Unité de mesure</th>
                <th>Prix unitaire HT (En Chiffres)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($consultation->prestations as $index => $prestation)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="text-left">{{ $prestation->designation }}</td>
                <td>{{ $prestation->unite }}</td>
                <td class="text-right">{{ number_format($prestation->prix_unitaire_ht, 2, ',', ' ') }} MAD</td>
            </tr>
            @endforeach
        </tbody>
    </table>


    <div class="page-break"></div>


    <!-- PAGE 3 : DÉTAIL ESTIMATIF -->
    <div class="header">
        <div class="royaume">Royaume du Maroc</div>
        <div>Ministère de l'Agriculture, de la Pêche Maritime, du Développement Rural et des Eaux et Forêts</div>
        <div class="onca">Office National du Conseil Agricole (ONCA)</div>
    </div>

    <div class="title">DÉTAIL ESTIMATIF</div>
    <div class="info-box">
        <p><strong>Consultation N° :</strong> {{ $consultation->numero_consultation }}</p>
        <p><strong>Objet :</strong> {{ $consultation->objet_consultation }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>N° Prix</th>
                <th>Désignation</th>
                <th>Unité</th>
                <th>Quantité</th>
                <th>Prix Unitaire HT</th>
                <th>Prix Total HT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($consultation->prestations as $index => $prestation)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="text-left">{{ $prestation->designation }}</td>
                <td>{{ $prestation->unite }}</td>
                <td>{{ number_format($prestation->quantite, 2, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($prestation->prix_unitaire_ht, 2, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($prestation->montant_ht, 2, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <th class="text-left">TOTAL HT :</th>
                <td class="text-right">{{ number_format($totalHT, 2, ',', ' ') }} MAD</td>
            </tr>
            <tr>
                <th class="text-left">TVA :</th>
                <td class="text-right">{{ number_format($totalTVA, 2, ',', ' ') }} MAD</td>
            </tr>
            <tr>
                <th class="text-left">TOTAL TTC :</th>
                <td class="text-right font-bold">{{ number_format($totalTTC, 2, ',', ' ') }} MAD</td>
            </tr>
        </table>
    </div>

</body>
</html>
