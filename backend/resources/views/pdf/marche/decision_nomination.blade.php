<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Décision de Nomination - Commission de Réception</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }
        .page {
            width: 100%;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .header h2 {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .header h3 {
            font-size: 11px;
            font-weight: bold;
        }
        .decision-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
            text-decoration: underline;
        }
        .decision-number {
            text-align: right;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .visa-section {
            margin: 15px 0;
            text-align: justify;
        }
        .visa-section p {
            margin-bottom: 5px;
            text-indent: 30px;
        }
        .article {
            margin: 20px 0;
        }
        .article-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .article-content {
            text-align: justify;
            margin-bottom: 10px;
        }
        .article-content p {
            margin-bottom: 8px;
            text-indent: 20px;
        }
        .commission-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .commission-table th,
        .commission-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        .commission-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .reunion-details {
            margin: 15px 0;
        }
        .reunion-details p {
            margin-bottom: 5px;
        }
        .signature-section {
            margin-top: 40px;
            text-align: right;
            padding-right: 50px;
        }
        .signature-section p {
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            border-top: 1px solid #000;
            padding-top: 10px;
        }
        .footer p {
            margin-bottom: 5px;
        }
        .highlight {
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="page">
        <div class="header">
            <h1>Royaume du Maroc</h1>
            <h2>Office National du Conseil Agricole</h2>
            <h3>Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra</h3>
        </div>

        <div class="decision-title">
            DÉCISION
        </div>

        <div class="decision-number">
            N° {{ $numDecision }} du {{ $dateDecisionFr }}
        </div>

        <div class="visa-section">
            <p><strong>Le Directeur Régional du Conseil Agricole Rabat-Salé-Kénitra,</strong></p>
            <p><strong>Vu</strong> la loi n° 58-12 portant création de l'Office National du Conseil Agricole, promulguée par le dahir n° 1-13-02 du 30 chaoual 1434 (7 septembre 2013);</p>
            <p><strong>Vu</strong> le décret n° 2-12-349 du 20 safar 1434 (24 décembre 2012) relatif aux marchés publics;</p>
            <p><strong>Vu</strong> le décret n° 2-14-316 du 14 ramadan 1435 (11 juillet 2014) fixant les modalités d'application de la loi n° 58-12;</p>
            <p><strong>Vu</strong> la décision du Directeur Général de l'ONCA n° 01/DG/2014 du 10 janvier 2014 relative à l'organisation administrative de l'Office;</p>
            <p><strong>Vu</strong> la décision n° 12/DR/2024 du Directeur Régional du Conseil Agricole Rabat-Salé-Kénitra du 15 janvier 2024 portant délégation de signature;</p>
            <p><strong>Vu</strong> le marché public n° {{ $marche->num_marche }} ayant pour objet : {{ $marche->objet_marche }};</p>
            <p><strong>Vu</strong> la nécessité de procéder à la réception partielle desdites prestations;</p>
            <p><strong>Décide :</strong></p>
        </div>

        <div class="article">
            <div class="article-title">Article Premier</div>
            <div class="article-content">
                <p>Il est institué une commission de réception partielle des prestations objet du marché public n° {{ $marche->num_marche }} ayant pour objet : {{ $marche->objet_marche }}.</p>
            </div>
        </div>

        <div class="article">
            <div class="article-title">Article 2 : Composition de la commission de réception</div>
            <div class="article-content">
                <p>La commission de réception est composée comme suit :</p>
                <table class="commission-table">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Nom et Prénom</th>
                            <th style="width: 35%;">Fonction</th>
                            <th style="width: 30%;">Qualité</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($membresCommission as $membre)
                        <tr>
                            <td>{{ $membre['nom'] }}</td>
                            <td>{{ $membre['fonction'] }}</td>
                            <td>{{ $membre['qualite'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="article">
            <div class="article-title">Article 3 : Réunion de la commission</div>
            <div class="article-content">
                <p>La commission de réception se réunira le {{ $dateReunionFr }} à {{ $heureReunion }} {{ $lieuReunion }} pour procéder à la réception partielle desdites prestations.</p>
            </div>
        </div>

        <div class="article">
            <div class="article-title">Article 4 : Exécution de la décision</div>
            <div class="article-content">
                <p>La présidente de la commission est chargée de l'exécution de la présente décision.</p>
            </div>
        </div>

        <div class="signature-section">
            <p>Le Directeur Régional</p>
        </div>

        <div class="footer">
            <p>Direction Régionale du Conseil Agricole de la Région de Rabat Salé Kénitra</p>
            <p>Angle Rue Sebta - Bd Mohamed V (à côté de Bank Al-Maghreb) - Kénitra</p>
            <p>Tél.: +212 (0) 537 32 55 99 - Fax: +212 (0) 537 36 13 20 - Site web: www.onca.gov.ma</p>
        </div>
    </div>

</body>
</html>
