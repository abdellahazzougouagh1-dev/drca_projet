<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Décision de Nomination - AOO N° {{ $aoo->num_aoo }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 15px;
            line-height: 1.4;
        }

        /* En-tête Institutionnel */
        .header-inst {
            width: 100%;
            margin-bottom: 10px;
        }
        .header-inst td {
            vertical-align: middle;
        }
        .header-inst-center {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }
        .header-inst img {
            height: 50px;
            width: auto;
        }

        /* Tableau d'en-tête */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid black;
            margin-bottom: 15px;
        }
        .info-table td {
            border: 1px solid black;
            padding: 5px 8px;
            vertical-align: middle;
        }

        /* Liste des Visas */
        .visas-list {
            list-style-type: none;
            padding: 0;
            margin: 0 0 15px 0;
        }
        .visas-list li {
            margin-bottom: 4px;
            text-align: justify;
        }
        .visas-list li::before {
            content: "• ";
        }

        /* Titres Articles */
        .decide-title {
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .article-title {
            font-weight: bold;
            font-style: italic;
            text-decoration: underline;
            margin-top: 10px;
            margin-bottom: 5px;
        }

        /* Tableau des membres */
        .members-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid black;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        .members-table th, .members-table td {
            border: 1px solid black;
            padding: 6px;
            vertical-align: middle;
        }
        .members-table th {
            font-weight: bold;
            text-align: center;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-justify { text-align: justify; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>

    <!-- 1. EN-TÊTE INSTITUTIONNEL -->
    <table class="header-inst">
        <tr>
            <td style="width: 20%; text-align: left;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo-onca.png'))) }}" alt="Logo ONCA">
            </td>
            <td style="width: 60%;" class="header-inst-center">
                Direction régionale de l'Office national du Conseil agricole Rabat-Salé-Kénitra
            </td>
            <td style="width: 20%; text-align: right;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) }}" alt="Royaume du Maroc">
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td style="width: 20%;" class="bold">
                N° &nbsp;&nbsp;&nbsp;&nbsp; {{ $aoo->num_decision_nomination }}
            </td>
            <td style="width: 60%;" class="text-center bold">
                Decision<br>
                de nomination de la commision d'ouverture des plis<br>
                Appel d'Offres Ouvert National Sur Offres De Prix Numéro : &nbsp;&nbsp;&nbsp;&nbsp; {{ $aoo->num_aoo }}
            </td>
            <td style="width: 20%;" class="text-center">
                Date : {{ isset($aoo->date_lettre) ? \Carbon\Carbon::parse($aoo->date_lettre)->format('d/m/Y') : '' }}
            </td>
        </tr>
    </table>

    <!-- 2. LES VISAS -->
    <ul class="visas-list">
        <li>Vu la loi 58-12 portant création de l'Office National du Conseil Agricole (ONCA) promulguée par le Dahir n° 1-12-67 du 16 Janvier 2013 ;</li>
        <li>Vu le décret n°2-13-374 du 23 chaabane 1434 (20 juin 2013) pris pour l'application de la loi 58-12 portant création de l'Office National du Conseil Agricole ;</li>
        <li>Vu le décret N° 2-22-431 du 15 chaabane 1444 (8 mars 2023) relatif aux marchés publics ;</li>
        <li>Vu l'arrêté n°2-1269 DESI/DE/ SPC du 10 Avril 2013, portant organisation financière et comptable de l'ONCA ;</li>
        <li>Vu la décision n°2/1371 DESI/DE/SPC du 17 avril 2013, fixant le seuil des actes d'engagement soumis au préalable au visa du contrôleur de l'Etat de l'ONCA ;</li>
        <li><span style="color: red;">Vu la décision N° 1192 du 05 Mars 2025, portant nomination de Monsieur Errahali hicham au poste de Directeur Général de l'Office National du conseil Agricole ;</span></li>
        <li>Vu la décision n°277/ONCA/DRHS/DRH/SGAP du 08 Février 2023, portant nomination de Mr. BOUDRA Abdelâali Directeur Régional de l'ONCA de la région du Rabat-Salé-Kénitra ;</li>
        <li>Vu la décision n° 454/ONCA/DRHS/DRH/SGAP du 02 Mars 2023 portant nomination de Mr. BOUDRA Abdelâali Directeur Régional de l'ONCA de la région du Rabat-Salé-Kénitra autant que sous-ordonnateur</li>
        <li>Vu la décision n° 455/ONCA/DRHS/DRH/SGAP du 02 Mars 2023 portant délégation de signature à Mr. BOUDRA Abdelâali le Directeur Régional de l'ONCA de la région du Rabat-Salé-Kénitra ;</li>
        <li>Vu l'avis d'Appel d'Offres Ouvert National Sur Offres De Prix Numéro : {{ $aoo->num_aoo }}</li>
    </ul>

    <!-- 3. LE CORPS DU TEXTE ("DECIDE") -->
    <div class="decide-title">DECIDE</div>

    <div class="article-title">Premier Article :</div>
    <div class="text-justify">
        Une commission est instituée à la Direction Régionale du Conseil Agricole du Rabat-Salé-Kénitra pour examiner les offres de prix relatives à l'Appel d'Offres Ouvert National Sur Offres De Prix N° <strong>{{ $aoo->num_aoo }}</strong> du <strong>{{ isset($aoo->date_ouverture) ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') : '' }}</strong> ayant pour objet : <strong>{{ $aoo->objet }}</strong>.
    </div>

    <div class="article-title">2ème Article :</div>
    <div>La commission est composée de :</div>

    <table class="members-table">
        <thead>
            <tr>
                <th style="width: 5%;">N°</th>
                <th style="width: 35%;">Nom et Prenom</th>
                <th style="width: 35%;">Fonction</th>
                <th style="width: 25%;">Qualité</th>
            </tr>
        </thead>
        <tbody>
            @if($aoo->membres_commission && is_array($aoo->membres_commission))
                @foreach ($aoo->membres_commission as $index => $membre)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="bold">{{ $membre['nom_prenom'] ?? '' }}</td>
                        <td>{{ $membre['fonction'] ?? '' }}</td>
                        <td class="text-center">{{ $membre['qualite'] ?? '' }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="4" class="text-center">Aucun membre assigné</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="text-justify">
        Cette commission se réunira au siège de la dircetion régionale du conseil agricole Rabat-Salé-Kénitra à Kénitra à la date du <strong>{{ isset($aoo->date_ouverture) ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') : '' }}</strong> à <strong>{{ $aoo->heure_ouverture }}</strong>
    </div>

    <div class="article-title">3ème Article :</div>
    <div>
        La présidente de la commission est chargée de l'exécution de la présente décision.
    </div>

</body>
</html>
