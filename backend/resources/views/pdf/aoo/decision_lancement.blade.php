<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Décision de Lancement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .content {
            margin: 20px 0;
            text-align: justify;
        }
        .section {
            margin: 20px 0;
        }
        .section-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
            background-color: #f0f0f0;
            padding: 8px;
        }
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        .signature-box {
            width: 200px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">DECISION DE LANCEMENT D'APPEL D'OFFRES</div>
        <div>Dossier: {{ $aoo->num_aoo }}</div>
    </div>

    <div class="content">
        <p><strong>Objet:</strong> {{ $aoo->objet_aoo }}</p>
    </div>

    <div class="section">
        <div class="section-title">I. CARACTERISTIQUES DE L'APPEL D'OFFRES</div>
        <table>
            <tr>
                <th>Élément</th>
                <th>Valeur</th>
            </tr>
            <tr>
                <td>Numéro d'AOO</td>
                <td>{{ $aoo->num_aoo }}</td>
            </tr>
            <tr>
                <td>Objet</td>
                <td>{{ $aoo->objet_aoo }}</td>
            </tr>
            <tr>
                <td>Montant Estimé</td>
                <td>{{ number_format($aoo->montant_estime, 2, ',', ' ') }} DH</td>
            </tr>
            <tr>
                <td>Type de Marché</td>
                <td>{{ $aoo->type_marche }}</td>
            </tr>
            <tr>
                <td>Date de Lancement</td>
                <td>{{ \Carbon\Carbon::now('Africa/Casablanca')->format('d/m/Y') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">II. LOTS</div>
        <table>
            <thead>
                <tr>
                    <th>N° Lot</th>
                    <th>Objet</th>
                    <th>Nombre Items</th>
                </tr>
            </thead>
            <tbody>
                @forelse($aoo->lots as $lot)
                <tr>
                    <td>{{ $lot->num_lot }}</td>
                    <td>{{ $lot->objet_lot }}</td>
                    <td>{{ $lot->items ? count($lot->items) : 0 }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" style="text-align: center;">Aucun lot défini</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">III. DECISION</div>
        <p>Sur la base de la présente décision, l'Appel d'Offres Ouvert numéro <strong>{{ $aoo->num_aoo }}</strong> est lancé à partir du {{ \Carbon\Carbon::now('Africa/Casablanca')->format('d/m/Y') }}.</p>
        <p>L'appel d'offres est ouvert à tous les fournisseurs qualifiés intéressés, dans le respect des conditions fixées par le cahier des charges.</p>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div style="height: 80px;"></div>
            <div style="border-top: 1px solid #000; margin-top: 10px;">
                <strong>Signature</strong><br>
                Autorité Responsable
            </div>
        </div>
        <div class="signature-box">
            <div>{{ \Carbon\Carbon::now('Africa/Casablanca')->format('d/m/Y') }}</div>
            <div style="height: 60px;"></div>
            <div style="border-top: 1px solid #000; margin-top: 10px;">
                Date
            </div>
        </div>
    </div>
</body>
</html>
