<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Convocation des Membres</title>
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
            font-size: 16px;
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
        .convocation-box {
            border: 2px solid #000;
            padding: 15px;
            margin: 20px 0;
            background-color: #fafafa;
        }
        .meeting-details {
            margin: 15px 0;
            padding: 10px;
            background-color: #e8f4f8;
            border-left: 3px solid #0084d1;
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
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        .signature-box {
            width: 200px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">CONVOCATION - COMMISSION D'EVALUATION</div>
        <div>Dossier: {{ $aoo->num_aoo }}</div>
    </div>

    <div class="convocation-box">
        <p style="text-align: center; margin: 0; font-weight: bold; color: #d9534f;">CONVOCATION OFFICIELLE</p>
    </div>

    <div class="content">
        <p>Vous êtes par la présente convoqué(e) pour assister à la séance de la Commission d'Évaluation des offres relatives à l'Appel d'Offres numéro <strong>{{ $aoo->num_aoo }}</strong>.</p>
    </div>

    <div class="section">
        <div class="section-title">DETAILS DE LA REUNION</div>
        <div class="meeting-details">
            <p><strong>Objet:</strong> Évaluation des offres - {{ $aoo->objet_aoo }}</p>
            <p><strong>Montant estimé:</strong> {{ number_format($aoo->montant_estime, 2, ',', ' ') }} DH</p>
            <p><strong>Nombre de lots:</strong> {{ $aoo->lots ? count($aoo->lots) : 0 }}</p>
            <p><strong>Date de la réunion:</strong> À définir</p>
            <p><strong>Lieu de la réunion:</strong> À définir</p>
            <p><strong>Heure:</strong> À définir</p>
        </div>
    </div>

    <div class="section">
        <div class="section-title">LOTS A EVALUER</div>
        <table>
            <thead>
                <tr>
                    <th>N° Lot</th>
                    <th>Description</th>
                    <th>Nombre Articles</th>
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
                    <td colspan="3" style="text-align: center;">Aucun lot</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">RECOMMANDATIONS IMPORTANTES</div>
        <ul>
            <li>Assurez-vous d'être présent à l'heure prévue</li>
            <li>Apportez tous les documents nécessaires pour l'évaluation</li>
            <li>Le respect de la confidentialité est obligatoire</li>
            <li>Toute absence doit être justifiée préalablement</li>
            <li>Signalez votre présence au responsable de la réunion</li>
        </ul>
    </div>

    <div class="section">
        <div class="section-title">CADRE REGLEMENTAIRE</div>
        <p>Cette réunion se déroule conformément aux dispositions réglementaires applicables et au cahier des charges de l'appel d'offres.</p>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div style="height: 60px;"></div>
            <div style="border-top: 1px solid #000; margin-top: 10px;">
                Signature du convoqué(e)<br>
                Date: _______________
            </div>
        </div>
        <div class="signature-box">
            <div style="height: 60px;"></div>
            <div style="border-top: 1px solid #000; margin-top: 10px;">
                Autorité responsable<br>
                {{ \Carbon\Carbon::now('Africa/Casablanca')->format('d/m/Y') }}
            </div>
        </div>
    </div>
</body>
</html>
