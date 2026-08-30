<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Lettre d'écartement - {{ $entreprise_nom }}</title>
    <style>
        @page {
            margin: 12mm 15mm 15mm 15mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10.5pt;
            line-height: 1.45;
            color: #000;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: top;
            border: none;
            padding: 0;
        }
        .onca-header {
            width: 35%;
            text-align: left;
        }
        .center-header {
            width: 30%;
            text-align: center;
            font-size: 9.5pt;
            font-weight: bold;
            line-height: 1.3;
        }
        .royaume-header {
            width: 35%;
            text-align: right;
        }
        .destinataire-block {
            float: right;
            width: 55%;
            text-align: center;
            margin-top: 15px;
            margin-bottom: 25px;
        }
        .destinataire-title {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 4px;
        }
        .destinataire-a {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 4px;
        }
        .destinataire-company {
            font-weight: bold;
            font-size: 11pt;
        }
        .clearfix {
            clear: both;
        }
        .subject-block {
            margin-top: 10px;
            margin-bottom: 25px;
            width: 80%;
        }
        .subject-row {
            margin-bottom: 6px;
        }
        .subject-label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
            text-decoration: underline;
        }
        .salutation {
            margin-top: 15px;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .paragraph {
            text-align: justify;
            margin-bottom: 12px;
            font-size: 10.5pt;
            line-height: 1.5;
        }
        .objet-aoo-box {
            font-weight: bold;
            margin: 8px 0 14px 0;
            text-transform: uppercase;
        }
        .motif-box {
            background-color: #fef2f2;
            border-left: 4px solid #dc2626;
            padding: 10px 14px;
            font-weight: bold;
            color: #991b1b;
            margin: 12px 0 18px 0;
            font-size: 10.5pt;
        }
        .closing {
            margin-top: 25px;
            margin-bottom: 35px;
            text-align: center;
            font-size: 10.5pt;
        }
        .nb-block {
            margin-top: 30px;
            font-size: 9.5pt;
            line-height: 1.4;
        }
        .nb-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
        }
        .nb-subtitle {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 6px;
        }
        .nb-list {
            padding-left: 0;
            list-style-type: none;
            margin-top: 4px;
        }
        .nb-list li {
            margin-bottom: 6px;
            padding-left: 14px;
            text-indent: -14px;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            font-size: 7.5pt;
            color: #475569;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Header Logos & Direction -->
    <table class="header-table">
        <tr>
            <td class="onca-header">
                @if(file_exists(public_path('images/logo-onca.png')))
                    <img src="{{ public_path('images/logo-onca.png') }}" style="height: 50px; width: auto;" alt="ONCA">
                @endif
            </td>
            <td class="center-header">
                Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
            </td>
            <td class="royaume-header">
                @if(file_exists(public_path('images/sceau-maroc.png')))
                    <img src="{{ public_path('images/sceau-maroc.png') }}" style="height: 50px; width: auto;" alt="Royaume du Maroc">
                @endif
            </td>
        </tr>
    </table>

    <!-- Destinataire Block -->
    <div class="destinataire-block">
        <div class="destinataire-title">Le Président de la commission d'appel d'offre</div>
        <div class="destinataire-a">A</div>
        <div class="destinataire-company">Mme/Mr Le Gérant de la Société</div>
        <div style="font-size: 11.5pt; font-weight: bold; margin-top: 4px;">{{ $entreprise_nom }}</div>
        @if(!empty($adresse))
            <div style="font-size: 9.5pt; font-weight: normal; color: #334155; margin-top: 2px;">{{ $adresse }} {{ !empty($ville) ? '- ' . $ville : '' }}</div>
        @endif
    </div>
    <div class="clearfix"></div>

    <!-- Subject & Reference Block -->
    <div class="subject-block">
        <div class="subject-row">
            <span class="subject-label">Objet</span> : <strong>Lettre d'écartement</strong>
        </div>
        <div class="subject-row">
            <span class="subject-label">Reference</span> : Appel d'offre N° &nbsp; <strong>{{ $num_aoo }}</strong> &nbsp; du &nbsp; <strong>{{ $date_publication }}</strong>
        </div>
    </div>

    <!-- Salutation -->
    <div class="salutation">
        Monsieur / Madame
    </div>

    <!-- Legal Paragraph -->
    <div class="paragraph">
        En application des dispositions de l’article 41 du Décret n°2-12-349 du 8 joumada I 1433 (20 Mars 2013) relatif aux marchés publics publié au bulletin officiel n° 6140 du 4 avril 2013,
    </div>

    <div class="paragraph">
        j’ai le regret de vous informer sur l'écartement de votre offre concernant l'appel d'offres ouvert N°: &nbsp; <strong>{{ $num_aoo }}</strong> &nbsp; du &nbsp; <strong>{{ $date_publication }}</strong>
    </div>

    <div class="paragraph">
        Ayant pour objet:
    </div>
    <div class="objet-aoo-box">
        {{ $objet }}
    </div>

    <!-- Motif d'écartement -->
    <div class="paragraph">
        Il est à signaler que votre offre a été écartée pour le (s) motif (s) ci-dessous :
    </div>

    <div class="motif-box">
        {{ $motif_ecartement }}
    </div>

    <!-- Closing -->
    <div class="closing">
        Veuillez agréer, Messieurs, mes salutations.
    </div>

    <!-- NB Block: Required Documents -->
    <div class="nb-block">
        <div class="nb-title">NB</div>
        <div class="nb-subtitle">Les pièces à fournir</div>
        <ul class="nb-list">
            <li>* Une attestation ou sa copie certifiée conforme délivrée depuis moins d'un an par la CNSS certifiant que le concurrent est en situation régulière envers cet organisme.</li>
            <li>* La ou les pièces justifiant les pouvoirs conférés à la personne agissant au nom de la Société. Ces pièces varient selon la forme juridique du concurrent.</li>
            <li>* Une attestation ou sa copie certifiée conforme délivrée depuis moins d'un an par l’Administration compétente du lieu d’imposition certifiant que le concurrent est en situation fiscale régulière.</li>
            <li>* Un certificat d'immatriculation au registre de commerce.</li>
        </ul>
    </div>

    <!-- Footer Coordonnées -->
    <table class="footer-table">
        <tr>
            <td>
                Direction Régionale du Conseil Agricole de la Région de Rabat Salé Kénitra, Angle Rue Sebta - Bd Mohamed V (à côté de Bank Al-Maghreb) - Kénitra<br>
                Tél. : +212 (0) 537 32 55 99 - Fax : +212 (0) 537 36 13 20 - Site web : www.onca.gov.ma
            </td>
        </tr>
    </table>

</body>
</html>
