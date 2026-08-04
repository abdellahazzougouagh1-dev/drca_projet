<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport d'Analyse des Offres</title>
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
        .analysis-box {
            background-color: #f9f9f9;
            border-left: 3px solid #0084d1;
            padding: 10px;
            margin: 10px 0;
        }
        .criteria {
            margin: 15px 0;
        }
        .criteria-title {
            font-weight: bold;
            color: #0084d1;
            margin-bottom: 5px;
        }
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        .signature-box {
            width: 180px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">RAPPORT D'ANALYSE DES OFFRES</div>
        <div>Appel d'Offres: {{ $aoo->num_aoo }}</div>
    </div>

    <div class="content">
        <p>Le rapport ci-après présente l'analyse des offres reçues pour l'Appel d'Offres numéro <strong>{{ $aoo->num_aoo }}</strong>, objet: <strong>{{ $aoo->objet_aoo }}</strong>.</p>
    </div>

    <div class="section">
        <div class="section-title">I. INFORMATIONS GENERALES</div>
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
                <td><strong>Date de rapport:</strong></td>
                <td>{{ \Carbon\Carbon::now('Africa/Casablanca')->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td><strong>Nombre d'offres reçues:</strong></td>
                <td>{{ $aoo->concurrents ? count($aoo->concurrents) : 0 }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">II. RESUME ANALYTIQUE</div>
        <div class="analysis-box">
            <p><strong>Nombre de candidats ayant déposé une offre:</strong> {{ $aoo->concurrents ? count($aoo->concurrents) : 0 }}</p>
            <p><strong>Offres conformes:</strong> En cours d'analyse</p>
            <p><strong>Offres non conformes:</strong> En cours d'analyse</p>
        </div>
    </div>

    <div class="section">
        <div class="section-title">III. ANALYSE DES OFFRES PAR LOT</div>
        @forelse($aoo->lots as $lot)
        <div class="section">
            <div class="criteria-title">Lot {{ $lot->num_lot }}: {{ $lot->objet_lot }}</div>
            <table>
                <thead>
                    <tr>
                        <th>Fournisseur</th>
                        <th>Montant HT (DH)</th>
                        <th>Conformité</th>
                        <th>Observations</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" style="text-align: center; color: #999;">Analyse en cours</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @empty
        <p style="text-align: center; color: #999;">Aucun lot défini</p>
        @endforelse
    </div>

    <div class="section">
        <div class="section-title">IV. CRITERES D'EVALUATION</div>
        <div class="criteria">
            <div class="criteria-title">A. Conformité administrative et technique</div>
            <p>Vérification de la présence de tous les documents exigés et conformité aux spécifications techniques du cahier des charges.</p>
        </div>
        <div class="criteria">
            <div class="criteria-title">B. Capacités financières</div>
            <p>Analyse de la stabilité financière et de la capacité du fournisseur à réaliser le marché.</p>
        </div>
        <div class="criteria">
            <div class="criteria-title">C. Evaluation économique</div>
            <p>Comparaison des prix proposés avec le montant estimé et analyse du rapport qualité-prix.</p>
        </div>
    </div>

    <div class="section">
        <div class="section-title">V. CONCLUSIONS ET RECOMMANDATIONS</div>
        <div class="analysis-box">
            <p>Sur la base de l'analyse effectuée, la Commission d'Évaluation recommande:</p>
            <ul>
                <li>De procéder à l'attribution du marché conformément aux dispositions réglementaires</li>
                <li>De respecter les délais de notification aux fournisseurs</li>
                <li>De mettre en place des mécanismes de suivi et de contrôle appropriés</li>
            </ul>
        </div>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div style="height: 50px;"></div>
            <div style="border-top: 1px solid #000; margin-top: 10px;">
                Président<br>Commission
            </div>
        </div>
        <div class="signature-box">
            <div style="height: 50px;"></div>
            <div style="border-top: 1px solid #000; margin-top: 10px;">
                Rapporteur<br>Commission
            </div>
        </div>
    </div>
</body>
</html>
