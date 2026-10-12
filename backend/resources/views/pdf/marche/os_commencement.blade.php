<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordre de Service de Commencement</title>
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
        .title-section {
            text-align: center;
            margin: 20px 0;
        }
        .title-section p {
            font-weight: bold;
            font-size: 12px;
        }
        .info-row {
            margin: 10px 0;
        }
        .info-row label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        .info-row span {
            display: inline-block;
        }
        .recipient {
            margin: 15px 0;
        }
        .recipient p {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .recipient .company {
            font-weight: normal;
        }
        .body-text {
            margin: 20px 0;
            text-align: justify;
        }
        .body-text p {
            margin-bottom: 10px;
            text-indent: 20px;
        }
        .body-text p:first-child {
            text-indent: 0;
        }
        .signature {
            margin-top: 40px;
            text-align: right;
            padding-right: 50px;
        }
        .signature p {
            font-weight: bold;
        }
        .page-break {
            page-break-before: always;
        }
        .accuse-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
            text-decoration: underline;
        }
        .accuse-content {
            margin: 20px 0;
        }
        .accuse-content p {
            margin-bottom: 10px;
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

    <!-- PAGE 1: ORDRE DE SERVICE DE COMMENCEMENT -->
    <div class="page">
        <div class="header">
            <h1>Royaume du Maroc</h1>
            <h2>Office National du Conseil Agricole</h2>
            <h3>Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra</h3>
        </div>

        <div class="title-section">
            <p>Le Directeur Régional du Conseil Agricole Rabat-Salé-Kénitra</p>
        </div>

        <table style="width: 100%; margin: 20px 0;">
            <tr>
                <td style="width: 150px;"><strong>Kénitra le :</strong></td>
                <td>{{ $dateSignatureFr }}</td>
            </tr>
        </table>

        <div class="recipient">
            <p>A Monsieur le gérant de la société</p>
            <p class="company">{{ $societe }}</p>
        </div>

        <table style="width: 100%; margin: 15px 0;">
            <tr>
                <td style="width: 150px;"><strong>Objet :</strong></td>
                <td>Ordre de service de commencement</td>
            </tr>
            <tr>
                <td style="width: 150px;"><strong>Référence :</strong></td>
                <td>Marché N° {{ $marche->num_marche }}</td>
            </tr>
        </table>

        <div class="body-text">
            <p><strong>Monsieur,</strong></p>
            <p>J'ai l'honneur de vous demander de commencer l'exécution du marché :</p>
            <p class="highlight">{{ $marche->num_marche }} ayant pour objet {{ $marche->objet_marche }}</p>
            <p>Le commencement de l'exécution du dit marché prendra effet -suite à son approbation par l'autorité compétente- à compter de la date de la réception du présent ordre de service,</p>
            <p>conformément aux dispositions de l'article 50 du décret n° 2-12-349 du 20 safar 1434 (24 décembre 2012) relatif aux marchés publics.</p>
        </div>

        <table style="width: 100%; margin: 20px 0;">
            <tr>
                <td style="width: 250px;"><strong>Date de notification du marché :</strong></td>
                <td>{{ $dateNotificationFr }}</td>
            </tr>
            <tr>
                <td style="width: 250px;"><strong>Date d'effet :</strong></td>
                <td>{{ $dateEffetFr }}</td>
            </tr>
        </table>

        <div class="signature">
            <p>Le Directeur Régional</p>
        </div>
    </div>

    <!-- PAGE 2: ACCUSÉ DE RÉCEPTION -->
    <div class="page page-break">
        <div class="accuse-title">
            Accusé de réception
        </div>

        <div class="accuse-content">
            <p>Je soussigné, Monsieur <strong>{{ $gerantNom }}</strong></p>
            <p>{{ $gerantQualite }} / agissant au nom et pour le compte de la société : <strong>{{ $societe }}</strong></p>
            <p>Faisant élection de domicile à : {{ $adresse }}</p>
            <p style="margin-top: 20px;">Atteste avoir reçu du directeur régional du conseil agricole Rabat-Salé-Kénitra l'ordre de commencement de l'exécution du marché :</p>
            <p class="highlight">{{ $marche->num_marche }}</p>
            <p>Ayant pour objet : {{ $marche->objet_marche }}</p>
            <p style="margin-top: 20px;">J'accuse, par la présente, la réception de l'ordre de service de commencement de l'exécution du marché sus-cité.</p>
        </div>

        <div class="signature" style="text-align: left; padding-left: 0;">
            <p>Fait à Kénitra, le {{ $dateSignatureFr }}</p>
            <p style="margin-top: 30px;">Signature</p>
        </div>

        <div class="footer">
            <p>Direction Régionale du Conseil Agricole de la Région de Rabat Salé Kénitra</p>
            <p>Angle Rue Sebta - Bd Mohamed V (à côté de Bank Al-Maghreb) - Kénitra</p>
            <p>Tél.: +212 (0) 537 32 55 99 - Fax: +212 (0) 537 36 13 20 - Site web: www.onca.gov.ma</p>
        </div>
    </div>

</body>
</html>
