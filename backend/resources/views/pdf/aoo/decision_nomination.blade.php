<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Décision de Nomination - {{ $num_aoo }}</title>
    <style>
        @page {
            margin-top: 8mm;
            margin-bottom: 22mm;
            margin-left: 15mm;
            margin-right: 15mm;
            footer: html_pageFooter;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9.5pt;
            line-height: 1.25;
            color: #000;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .left-header {
            width: 25%;
            text-align: left;
        }
        .center-header {
            width: 50%;
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            border-bottom: 2px solid #000;
            padding-bottom: 3px;
        }
        .right-header {
            width: 25%;
            text-align: right;
        }
        .info-table {
            width: 100%;
            margin-top: 6px;
            margin-bottom: 6px;
            border-collapse: collapse;
        }
        .info-table td {
            vertical-align: top;
            border: none;
            padding: 0;
            font-size: 10.5pt;
        }
        .main-title-block {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            line-height: 1.3;
        }
        .reference-list {
            list-style-type: none;
            padding-left: 0;
            margin-top: 6px;
            margin-bottom: 6px;
            font-size: 8.5pt;
            line-height: 1.2;
            text-align: justify;
        }
        .reference-list li {
            margin-bottom: 2px;
        }
        .decide {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin-top: 6px;
            margin-bottom: 6px;
        }
        .article-title {
            font-weight: bold;
            font-size: 9.5pt;
            margin-top: 5px;
            margin-bottom: 2px;
        }
        .article-content {
            font-size: 9.5pt;
            line-height: 1.25;
            margin-bottom: 6px;
        }
        .commission-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
            border: 1.5px solid #000;
        }
        .commission-table th, .commission-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 9pt;
            text-align: left;
        }
        .commission-table th {
            background-color: #f1f5f9;
            text-align: center;
            font-weight: normal;
        }
        .bold {
            font-weight: bold;
        }
    </style>
</head>
<body>

    <!-- En-tête officiel DRCA-RSK avec logos agrandis -->
    <table class="header-table">
        <tr>
            <td class="left-header">
                @if(file_exists(public_path('images/logo-onca.png')))
                    <img src="{{ public_path('images/logo-onca.png') }}" style="height: 85px; width: auto;" alt="ONCA">
                @endif
            </td>
            <td class="center-header">
                Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
            </td>
            <td class="right-header">
                @if(file_exists(public_path('images/sceau-maroc.png')))
                    <img src="{{ public_path('images/sceau-maroc.png') }}" style="height: 90px; width: auto;" alt="Royaume du Maroc">
                @endif
            </td>
        </tr>
    </table>

    <!-- Ligne N° / Titre / Date -->
    <table class="info-table">
        <tr>
            <td style="text-align: left; width: 25%; font-size: 10.5pt; font-weight: bold; vertical-align: top;">
                N° {{ $num_decision ?? '' }}
            </td>
            <td style="text-align: center; width: 50%;">
                <div class="main-title-block">
                    Decision<br>
                    de nomination de la commision d'ouverture des plis<br>
                    Consultation N°:
                </div>
            </td>
            <td style="text-align: right; width: 25%; font-size: 10.5pt; font-weight: bold; vertical-align: top;">
                Date: {{ $date_decision ?? '' }}
            </td>
        </tr>
    </table>

    <!-- Visas juridiques et administratifs -->
    <ul class="reference-list">
        @if(!empty($references) && count($references) > 0)
            @foreach($references as $ref)
                <li>• {!! htmlspecialchars($ref['texte_complet'] ?? $ref['texte'] ?? 'Vu ...') !!}</li>
            @endforeach
        @else
            <li>•Vu la loi 58-12 portant création de l’Office National du Conseil Agricole (ONCA) promulguée par le Dahir n° 1-12-67 du 16 Janvier 2013 ;</li>
            <li>• Vu le décret n°2.22.431 du 15 chaabane 1444 (8 mars 2023), relatif au marchés publics ;</li>
            <li>•Vu l’arrêté n°2-1269 DESI/DE/ SPC du 10 Avril 2013, portant organisation financière et comptable de l’ONCA ;</li>
            <li>•Vu la décision n°2/1371 DESI/DE/SPC du 17 avril 2013, fixant le seuil des actes d’engagement soumis au préalable au visa du contrôleur de l’Etat de l’ONCA ;</li>
            <li>• Vu la décision N° 1192 du 05 Mars 2025 , portant nomination de Monsieur Errahali hicham au poste de Directeur Général de l’Office National du conseil Agricole ;</li>
            <li>• Vu la décision n°277/ONCA/DRHS/DRH/SGAP du 08 Février 2023, portant nomination de Mr. BOUDRA Abdelâali Directeur Régional de l’ONCA de la région du Rabat-Salé-Kénitra ;</li>
            <li>• Vu la décision n° 567 /ONCA/DRHS/DRH/SGAP du 17 Mars 2025 portant nomination de Mr. BOUDRA Abdelâali Directeur Régional de l’ONCA de la région du Rabat-Salé-Kénitra autant que sous-ordonnateur ;</li>
            <li>• Vu la décision n° 568/ONCA/DRHS/DRH/SGAP du 17 Mars 2025 portant délégation de signature à Mr. BOUDRA Abdelâali le Directeur Régional de l’ONCA de la région du Rabat-Salé-Kénitra ;</li>
            <li>•Vu le budget de fonctionnement de l’ONCA au titre de l’exercice {{ $exercice ?? date('Y') }}.</li>
            <li>•Vu l'avis d'acaht n° {{ $num_aoo }}</li>
            <li>•Vu le budget d'investissement de l'ONCA délegué et notifié à la DRCA-RSK au titre de l’exercice {{ $exercice ?? date('Y') }}.</li>
        @endif
    </ul>

    <!-- DECIDE -->
    <div class="decide">
        DECIDE
    </div>

    <!-- Article Premier -->
    <div class="article-title">Article Premier :</div>
    <div class="article-content">
        Une commission est instituée à la Direction Régionale du Conseil Agricole de Rabat-Salé-Kénitra pour <br><br>
        l’ouverture des plis relatifs a la consultation N° : &nbsp;&nbsp;&nbsp;<span class="bold">{{ $num_aoo }}</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Ayant pour objet:<br>
        <span class="bold">{{ $objet }}</span>
    </div>

    <!-- Article 2 -->
    <div class="article-title">Article 2 :</div>
    <div class="article-content">
        La commission susvisée est composée de :
        <table class="commission-table">
            <thead>
                <tr>
                    <th style="width: 32%;">Nom et Prénom</th>
                    <th style="width: 48%;">Fonction</th>
                    <th style="width: 20%;">Qualité</th>
                </tr>
            </thead>
            <tbody>
                @foreach($membres as $m)
                    <tr>
                        <td>{{ $m['nom_prenom'] ?? ($m['nom'] ?? '') }}</td>
                        <td>{{ $m['fonction'] ?? ($m['role'] ?? '') }}</td>
                        <td>{{ $m['qualite'] ?? ($m['role'] ?? 'Membre') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Article 3 -->
    <div class="article-title">Article 3</div>
    <div class="article-content">
        La-dite commission se réunira le : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span class="bold">{{ $date_reunion ?? '' }}</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; à &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span class="bold">{{ $heure_reunion ?? '10H00' }}</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; au siège de la <span class="bold">{{ $lieu_reunion ?? 'DRCA-RSK' }}</span><br>
        pour proceder à l'ouverture des plis
    </div>

    <!-- Article 4 -->
    <div class="article-title">Article 4</div>
    <div class="article-content">
        Le président de la commission est chargé de l’exécution de la présente décision.
    </div>

    <!-- Footer officiel DRCA-RSK / ONCA -->
    <htmlpagefooter name="pageFooter">
        <div style="text-align: center; border-top: 1px solid #999; padding-top: 3px;">
            @if(file_exists(public_path('images/info_DRCA.png')))
                <img src="{{ public_path('images/info_DRCA.png') }}" style="width: 100%; height: auto;" alt="Footer ONCA">
            @endif
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="pageFooter" value="on" />

</body>
</html>
