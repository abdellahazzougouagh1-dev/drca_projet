<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Décision d'Attribution</title>
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
        .decision-box {
            background-color: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 20px 0;
            font-weight: bold;
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
        <div class="title">DECISION D'ATTRIBUTION</div>
        <div>Appel d'Offres: {{ $aoo->num_aoo }}</div>
    </div>

    <div class="content">
        <p>Vu les résultats de l'analyse des offres reçues pour l'Appel d'Offres numéro <strong>{{ $aoo->num_aoo }}</strong>, objet: <strong>{{ $aoo->objet_aoo }}</strong>,</p>
        <p>Vu le rapport de la Commission d'Évaluation,</p>
        <p>Vu la réglementation applicable en matière de marchés publics,</p>
    </div>

    <div class="decision-box">
        Il est décidé d'attribuer le marché conformément aux dispositions de la présente décision.
    </div>

    <div class="section">
        <div class="section-title">I. CARACTERISTIQUES DE L'APPEL D'OFFRES</div>
        <table>
            <tr>
                <td><strong>Numéro d'AOO:</strong></td>
                <td>{{ $aoo->num_aoo }}</td>
            </tr>
            <tr>
                <td><strong>Objet:</strong></td>
                <td>{{ $aoo->objet_aoo }}</td>
            </tr>
            <tr>
                <td><strong>Montant estimé:</strong></td>
                <td>{{ number_format($aoo->montant_estime, 2, ',', ' ') }} DH</td>
            </tr>
            <tr>
                <td><strong>Date de la décision:</strong></td>
                <td>{{ \Carbon\Carbon::now('Africa/Casablanca')->format('d/m/Y') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">II. MARCHES ATTRIBUES</div>
        @php
            $attributions = [];
            foreach ($aoo->lots as $lot) {
                $attributions[$lot->num_lot] = ['lot' => $lot, 'fournisseur' => null, 'montant' => 0];
            }
        @endphp
        <table>
            <thead>
                <tr>
                    <th>N° Lot</th>
                    <th>Description du Lot</th>
                    <th>Fournisseur Attributaire</th>
                    <th>Montant Attribué (DH)</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($aoo->lots as $lot)
                <tr>
                    <td>{{ $lot->num_lot }}</td>
                    <td>{{ $lot->objet_lot }}</td>
                    <td>À compléter</td>
                    <td style="text-align: right;">À compléter</td>
                    <td>En attente</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center;">Aucun lot défini</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">III. CONDITIONS D'ATTRIBUTION</div>
        <ul>
            <li>L'attribution est conditionnelle à la signature du marché par les parties;</li>
            <li>Le fournisseur devra fournir les garanties exigées (caution, assurance de responsabilité civile, etc.);</li>
            <li>Le délai d'exécution démarre à partir de la notification formelle de l'attribution;</li>
            <li>Les conditions stipulées au cahier des charges demeurent applicables;</li>
            <li>Cette décision est valable pour une période de {{ $aoo->duree_validite ?? '90' }} jours à partir de sa date d'émission.</li>
        </ul>
    </div>

    <div class="section">
        <div class="section-title">IV. NOTIFICATIONS</div>
        <p>Les fournisseurs seront notifiés du résultat de l'appel d'offres selon les dispositions réglementaires applicables. Les fournisseurs évincés disposeront d'un droit de recours conformément à la législation en vigueur.</p>
    </div>

    <div class="section">
        <div class="section-title">V. EFFET DE LA DECISION</div>
        <p>Cette décision entre en vigueur à compter de sa date et demeure exécutoire jusqu'à la signature du marché par les parties.</p>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div style="height: 70px;"></div>
            <div style="border-top: 1px solid #000; margin-top: 10px;">
                <strong>Autorité Responsable</strong><br>
                Signature
            </div>
        </div>
        <div class="signature-box">
            {{ \Carbon\Carbon::now('Africa/Casablanca')->format('d/m/Y') }}
            <div style="height: 50px;"></div>
            <div style="border-top: 1px solid #000; margin-top: 10px;">
                Date
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-top: 50px; font-size: 12px; border-top: 1px solid #ddd; padding-top: 20px;">
        <p style="color: #666;">Document officiel de décision d'attribution<br>Généré le {{ \Carbon\Carbon::now('Africa/Casablanca')->format('d/m/Y à H:i') }}</p>
    </div>
</body>
</html>
