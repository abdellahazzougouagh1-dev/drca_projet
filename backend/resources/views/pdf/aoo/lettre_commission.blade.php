<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Convocation Commission Appel d'Offres</title>
    <style>
        @page {
            margin: 85px 40px 100px 40px;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #000;
            line-height: 1.35;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
            margin-top: 10px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-center {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            padding: 0 8px;
        }
        .header-logo {
            height: 70px;
            width: auto;
        }
        .header-line {
            border-bottom: 2px solid #000080;
            margin-bottom: 18px;
        }

        footer {
            position: fixed;
            bottom: -90px;
            left: 0;
            right: 0;
            border-top: 1px solid #999;
            padding-top: 8px;
            font-size: 8px;
            color: #333;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-table td {
            vertical-align: top;
            border: none;
        }
        .footer-left {
            width: 22%;
            font-weight: bold;
            font-size: 9px;
            line-height: 1.3;
        }
        .footer-center {
            width: 56%;
            text-align: center;
            font-size: 8px;
            line-height: 1.35;
        }
        .footer-right {
            width: 22%;
            text-align: right;
        }
        .footer-logo {
            height: 48px;
            width: auto;
        }

        .ref-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .ref-table td {
            vertical-align: top;
        }
        .main-title-block {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin-top: 15px;
            margin-bottom: 25px;
            line-height: 1.5;
        }
        .a-separator {
            font-size: 18px;
            margin: 10px 0;
            display: block;
        }
        .objet-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .objet-table td {
            padding: 8px 4px;
            border: none;
            vertical-align: top;
        }
        .objet-label {
            font-weight: bold;
            width: 150px;
        }
        .salutation {
            margin-top: 15px;
            margin-bottom: 10px;
        }
        .intro-text {
            text-align: justify;
            margin-bottom: 15px;
            line-height: 1.45;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .details-table td {
            border: 1px solid #000;
            padding: 10px;
            vertical-align: top;
        }
        .details-label {
            width: 35%;
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .pj-block {
            margin-top: 15px;
            font-weight: bold;
        }
        .pj-block ul {
            list-style-type: none;
            padding-left: 20px;
            margin-top: 5px;
        }
        .pj-block ul li {
            margin-bottom: 8px;
        }
        .pj-block ul li:before {
            content: "* ";
            font-weight: bold;
        }
    </style>
</head>
<body>

@php
    $logoOnca = \App\Support\AooDocumentHelper::embedImage('images/logo-onca.png');
    $sceauMaroc = \App\Support\AooDocumentHelper::embedImage('images/sceau-maroc.png');
    $logoGreen = \App\Support\AooDocumentHelper::embedImage('images/generation-green.png');
@endphp

    <table class="header-table">
        <tr>
            <td style="width: 24%;">
                @if($logoOnca)
                    <img src="{{ $logoOnca }}" alt="Logo ONCA" class="header-logo">
                @endif
            </td>
            <td class="header-center" style="width: 52%;">
                Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
            </td>
            <td style="width: 24%; text-align: right;">
                @if($sceauMaroc)
                    <img src="{{ $sceauMaroc }}" alt="Sceau Maroc" class="header-logo">
                @endif
            </td>
        </tr>
    </table>
    <div class="header-line"></div>

    <table class="ref-table">
        <tr>
            <td style="width: 50%;">
                N° : {{ $doc['ref_numero'] }}
            </td>
            <td style="width: 50%; text-align: right;">
                Kénitra le : {{ $doc['date_lettre'] }}
            </td>
        </tr>
    </table>

    <div class="main-title-block">
        Le Directeur Régional du Conseil Agricole Rabat-Salé-Kénitra
        <span class="a-separator">A</span>
        Madame, Messieurs les membres de la commission d'appel d'offres<br>
        Relevant de la direction régionale Rabat-Salé-Kénitra
    </div>

    <table class="objet-table">
        <tr>
            <td class="objet-label">Objet</td>
            <td style="width: 10px;">:</td>
            <td>Convocation pour assister aux travaux de la commission d'Appel d'offres</td>
        </tr>
        <tr>
            <td class="objet-label">Référence</td>
            <td>:</td>
            <td>Appel d'offres Ouvert simplifié sur offre de prix N° : <strong>{{ $aoo->num_aoo ?? '................' }}</strong></td>
        </tr>
        <tr>
            <td colspan="3" style="padding-top: 10px; font-weight: bold;">
                Décision de nomination de la commission d'appel d'offre : {{ $aoo->num_decision_nomination ?: '................' }}
            </td>
        </tr>
    </table>

    <div class="salutation">Madame, Messieurs,</div>

    <div class="intro-text">
        Conformément aux dispositions du décret N° 2-22-431 du 15 chaabane 1444 (8 mars 2023) relatif aux marchés publics, j'ai l'honneur de vous demander de bien vouloir participer aux travaux de la commission d'appel d'offres suivant :
    </div>

    <table class="details-table">
        <tr>
            <td class="details-label">Numéro de l'appel d'offres :</td>
            <td style="font-weight: bold;">{{ $aoo->num_aoo ?? '................' }}</td>
        </tr>
        <tr>
            <td class="details-label">* Objet de l'appel d'offre :</td>
            <td style="font-weight: bold; text-transform: uppercase;">{{ $aoo->objet ?? '................................................................' }}</td>
        </tr>
        <tr>
            <td class="details-label">* Date de l'ouverture des plis :</td>
            <td style="font-weight: bold;">{{ $doc['date_ouverture'] }}</td>
        </tr>
        <tr>
            <td class="details-label">* Heure de l'ouverture des plis :</td>
            <td style="font-weight: bold;">{{ $doc['heure_ouverture'] }}</td>
        </tr>
        <tr>
            <td class="details-label">* Lieu d'ouverture des plis :</td>
            <td style="font-weight: bold;">{{ $doc['lieu_ouverture'] }}</td>
        </tr>
    </table>

    <div class="pj-block">
        PJ
        <ul>
            <li>l'avis d'appel d'offre en français</li>
            <li>l'avis d'appel d'offre en arabe</li>
            <li>CPS</li>
            <li>Règlement de consultation et modèles en annexes</li>
        </ul>
    </div>

    <footer>
        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    @if($logoGreen)
                        <img src="{{ $logoGreen }}" alt="Génération Green" class="footer-logo"><br>
                    @endif
                    <strong>Génération Green<br>2020-2030</strong>
                </td>
                <td class="footer-center">
                    Direction Régionale du Conseil Agricole de la Région de Rabat Salé Kénitra,<br>
                    Angle Rue Sebta - Bd Mohamed V (à côté de Bank Al-Maghrib) - Kénitra<br>
                    Tél. : +212 (0) 537 32 55 99 - Fax : +212 (0) 537 36 13 20 - Site web : www.onca.gov.ma
                </td>
                <td class="footer-right">
                    @if($sceauMaroc)
                        <img src="{{ $sceauMaroc }}" alt="Ministère" class="footer-logo">
                    @endif
                </td>
            </tr>
        </table>
    </footer>

</body>
</html>
