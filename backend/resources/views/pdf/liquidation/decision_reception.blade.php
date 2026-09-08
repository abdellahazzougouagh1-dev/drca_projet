<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Décision de Nomination de la Commission de Réception - {{ $marche->num_marche }}</title>
    <style>
        @page {
            margin: 20px 32px 20px 32px;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12.5px;
            color: #000;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-center {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            line-height: 1.4;
        }
        .header-logo {
            height: 78px;
            width: auto;
        }
        .divider {
            border-bottom: 2px solid #000;
            margin-bottom: 12px;
        }
        .ref-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 13px;
            font-weight: bold;
        }
        .title-block {
            text-align: center;
            margin-bottom: 16px;
        }
        .title-main {
            font-size: 17px;
            font-weight: bold;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        .title-sub {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .title-marche {
            font-size: 13.5px;
            font-weight: bold;
        }
        .visas-list {
            list-style-type: none;
            padding-left: 0;
            margin: 0 0 14px 0;
            font-size: 12px;
            line-height: 1.5;
        }
        .visas-list li {
            margin-bottom: 5px;
            text-align: justify;
        }
        .decide-title {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin: 12px 0 12px 0;
            letter-spacing: 3px;
        }
        .article-title {
            font-weight: bold;
            font-size: 13.5px;
            margin-top: 10px;
            margin-bottom: 5px;
        }
        .members-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            margin: 10px 0 14px 0;
            font-size: 12px;
        }
        .members-table th, .members-table td {
            border: 1px solid #000;
            padding: 6px 10px;
            vertical-align: middle;
        }
        .members-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }
        .text-justify {
            text-align: justify;
        }
        .bold {
            font-weight: bold;
        }
    </style>
</head>
<body>

    <!-- EN-TÊTE -->
    <table class="header-table">
        <tr>
            <td style="width: 25%; text-align: left;">
                @if(file_exists(public_path('images/logo-onca.png')))
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo-onca.png'))) }}" class="header-logo" alt="Logo ONCA">
                @endif
            </td>
            <td style="width: 50%;" class="header-center">
                Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
            </td>
            <td style="width: 25%; text-align: right;">
                @if(file_exists(public_path('images/sceau-maroc.png')))
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) }}" class="header-logo" alt="Royaume du Maroc">
                @endif
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- RÉFÉRENCE N° ET DATE -->
    <table class="ref-table">
        <tr>
            <td style="text-align: left; width: 50%;">
                N° {{ $num_decision ?? ($liquidation->num_decision ?? ($marche->num_decision_nomination ?? ($marche->num_decision ?? ('30/DR/'.date('Y'))))) }}
            </td>
            <td style="text-align: right; width: 50%;">
                Date : {{ $date_decision ?? (isset($liquidation->date_decision) && $liquidation->date_decision ? \Carbon\Carbon::parse($liquidation->date_decision)->format('d/m/Y') : (isset($marche->date_decision_nomination) && $marche->date_decision_nomination ? \Carbon\Carbon::parse($marche->date_decision_nomination)->format('d/m/Y') : (isset($marche->date_approbation) && $marche->date_approbation ? \Carbon\Carbon::parse($marche->date_approbation)->format('d/m/Y') : date('d/m/Y')))) }}
            </td>
        </tr>
    </table>

    <!-- TITRE -->
    <div class="title-block">
        <div class="title-main">Decision</div>
        <div class="title-sub">Nomination de la commission de réception {{ $type_reception ?? (isset($liquidation->type_reception) ? strtolower($liquidation->type_reception) : 'partielle') }}</div>
        <div class="title-marche">Marché Numéro {{ $marche->num_marche }}</div>
    </div>

    <!-- VISAS -->
    <ul class="visas-list">
        <li>• Vu la loi 58-12 portant création de l'Office National du Conseil Agricole (ONCA) promulguée par le Dahir n° 1-12-67 du 16 Janvier 2013 ;</li>
        <li>• Vu le décret n°2.22.431 du 15 chaabane 1444 (8 mars 2023), relatif au marchés publics ;</li>
        <li>• Vu l'arrêté n°2-1269 DESI/DE/ SPC du 10 Avril 2013, portant organisation financière et comptable de l'ONCA ;</li>
        <li>• Vu la décision n°2/1371 DESI/DE/SPC du 17 avril 2013, fixant le seuil des actes d'engagement soumis au préalable au visa du contrôleur de l'Etat de l'ONCA ;</li>
        <li>• Vu le decret n° 2-17-453 du 03 Aout 2017, portant nomination de Monsieur Jaouad BAHAJI au poste de Directeur Général de l'Office National du conseil Agricole ;</li>
        <li>• Vu la décision n°277/ONCA/DRHS/DRH/SGAP du 08 Février 2023, portant nomination de Mr. BOUDRA Abdelâali Directeur Régional de l'ONCA de la région du Rabat-Salé-Kénitra ;</li>
        <li>• Vu la décision n° 454/ONCA/DRHS/DRH/SGAP du 02 Mars 2023 portant nomination de Mr. BOUDRA Abdelâali Directeur Régional de l'ONCA de la région du Rabat-Salé-Kénitra autant que sous-ordonnateur</li>
        <li>• Vu la décision n° 455/ONCA/DRHS/DRH/SGAP du 02 Mars 2023 portant délégation de signature à Mr. BOUDRA Abdelâali le Directeur Régional de l'ONCA de la région du Rabat-Salé-Kénitra ;</li>
        <li style="margin-top: 4px;"><strong>Vu le marché numéro: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $marche->num_marche }}</strong></li>
    </ul>

    <!-- DECIDE -->
    <div class="decide-title">DECIDE</div>

    <!-- ARTICLE 1 -->
    @php
        $rawObjetDecision = $marche->objet_marche 
            ?: ($marche->aoo->objet 
            ?: ($marche->aoo->objet_marche 
            ?: ($marche->lot->objet_lot 
            ?: ($marche->notificationLigne->intitule 
            ?: ($marche->aoo->notificationLigne->intitule 
            ?: '')))));
        
        $fullObjetDecision = !empty($rawObjetDecision) ? $rawObjetDecision : 'Prestations liées au marché';
    @endphp

    <div class="article-title">Article Premier :</div>
    <div class="text-justify" style="margin-bottom: 8px;">
        Une commission est instituée à la Direction Régionale du Conseil Agricole du Rabat-Salé-Kénitra pour la réception des prestations liées au Marché numéro: <strong>{{ $marche->num_marche }}</strong> &nbsp;&nbsp;&nbsp;&nbsp; Ayant pour objet:
        <div class="bold" style="margin-top: 4px;">
            {{ $fullObjetDecision }}
        </div>
    </div>

    <!-- ARTICLE 2 -->
    <div class="article-title">Article 2 :</div>
    <div>La commission de réception est composée de :</div>

    <table class="members-table">
        <thead>
            <tr>
                <th style="width: 35%;">Nom et Prenom</th>
                <th style="width: 45%;">Fonction</th>
                <th style="width: 20%;">Qualité</th>
            </tr>
        </thead>
        <tbody>
            @php
                $commMembres = [];
                if (isset($liquidation->commission_reception) && !empty($liquidation->commission_reception)) {
                    $commMembres = is_string($liquidation->commission_reception) ? json_decode($liquidation->commission_reception, true) : $liquidation->commission_reception;
                } elseif (isset($marche->aoo->membres_commission) && !empty($marche->aoo->membres_commission)) {
                    $commMembres = is_string($marche->aoo->membres_commission) ? json_decode($marche->aoo->membres_commission, true) : $marche->aoo->membres_commission;
                }
            @endphp

            @if(!empty($commMembres) && is_array($commMembres) && count($commMembres) > 0)
                @foreach($commMembres as $m)
                    <tr>
                        <td class="bold">{{ $m['nom_prenom'] ?? ($m['nom'] ?? '') }}</td>
                        <td>{{ $m['fonction'] ?? '' }}</td>
                        <td style="text-align: center;">{{ $m['qualite'] ?? ($m['role'] ?? 'Membre') }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td class="bold">Enneddam Wafaa</td>
                    <td>Chef de Service de la Programmation du conseil agricole</td>
                    <td style="text-align: center;">Presidente</td>
                </tr>
                <tr>
                    <td class="bold">Ramah Mohamed</td>
                    <td>Chef de SPMOCA KHEMISSAT</td>
                    <td style="text-align: center;">Membre</td>
                </tr>
                <tr>
                    <td class="bold">ABDELHAKIM Mohsine</td>
                    <td>Chef de CCA Had Kourt</td>
                    <td style="text-align: center;">Membre</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- ARTICLE 3 -->
    <div class="article-title">Article 3</div>
    <div style="margin-bottom: 8px;">
        La-dite commission se réunira le : &nbsp;&nbsp;&nbsp;&nbsp; <strong>{{ $date_reunion ?? (isset($liquidation->date_reunion_commission) && $liquidation->date_reunion_commission ? \Carbon\Carbon::parse($liquidation->date_reunion_commission)->format('d/m/Y') : (isset($marche->date_reunion_commission) && $marche->date_reunion_commission ? \Carbon\Carbon::parse($marche->date_reunion_commission)->format('d/m/Y') : '11/12/2024')) }}</strong> &nbsp;&nbsp;&nbsp;&nbsp; à &nbsp;&nbsp;&nbsp;&nbsp; <strong>{{ $heure_reunion ?? ($liquidation->heure_reunion_commission ?? ($marche->heure_reunion_commission ?? '9:00')) }}</strong> &nbsp;&nbsp;&nbsp;&nbsp; au siège de la DRCA-RSK<br>
        pour procéder à &nbsp;&nbsp;&nbsp;&nbsp; la réception {{ $type_reception ?? (isset($liquidation->type_reception) ? strtolower($liquidation->type_reception) : 'partielle') }} desdites prestations
    </div>

    <!-- ARTICLE 4 -->
    <div class="article-title">Article 4</div>
    <div>
        La présidente de la commission est chargée de l'exécution de la présente décision.
    </div>

</body>
</html>
