<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $docTitle }}</title>
    <style>
        @page { margin: 95px 40px 45px 40px; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 14px;
            color: #000;
            line-height: 1.5;
            margin: 0;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: middle; }
        .page { position: relative; }
        .bold { font-weight: bold; }
        .center { text-align: center; }
        .right { text-align: right; }
        .meta { width: 100%; margin-bottom: 12px; font-size: 14px; }
        .sub-title {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin: 10px 0 4px;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .header-desc {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 14px;
            line-height: 1.5;
        }
        .article-text {
            text-align: justify;
            line-height: 1.5;
            font-size: 13px;
            margin: 10px 0 14px 0;
        }
        .decide-title {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin: 14px 0 10px;
            text-transform: uppercase;
            text-decoration: underline;
        }
        p { margin: 9px 0; font-size: 14px; line-height: 1.5; }
        .section { font-weight: bold; font-size: 14px; text-decoration: underline; }
        .box td, .box th {
            border: 1px solid #000;
            padding: 7px 8px;
            vertical-align: middle;
            font-size: 13px;
        }
        .box th {
            background: #f4f4f4;
            font-weight: bold;
            font-size: 13px;
        }
        .box { margin: 10px 0 14px 0; }
    </style>
</head>
<body>
    @include('documents.partials.bc_header')
    @include('documents.partials.bc_footer')
<div class="page">

    <table class="meta">
        <tr>
            <td class="bold">N° : {{ $doc['numero_decision'] }}</td>
            <td class="right">Date : {{ $doc['date_document'] }}</td>
        </tr>
    </table>

    <div class="sub-title">Décision</div>
    <div class="header-desc">
        Nomination de la commission de réception {{ strtolower($doc['type_reception'] ?? 'définitive') }}<br>
        Bon de Commande N° : {{ $doc['numero_bc'] }}
    </div>

    <p class="article-text">
        • Vu la loi 58-12 portant création de l’Office National du Conseil Agricole (ONCA) promulguée par le Dahir n° 1-12-67 du 16 Janvier 2013 ;<br>
        • Vu le décret n°2.22.431 du 15 chaabane 1444 (8 mars 2023), relatif aux marchés publics ;<br>
        • Vu l’arrêté n°2-1269 DESI/DE/SPC du 10 Avril 2013, portant organisation financière et comptable de l’ONCA ; <br>
        • Vu la décision n°2/1371 DESI/DE/SPC du 17 avril 2013, fixant le seuil des actes d’engagement soumis au préalable au visa du contrôleur de l’État de l’ONCA ;<br>
        • Vu la décision N° 1192 du 05 Mars 2025 , portant nomination de Monsieur Errahali Hicham au poste de Directeur Général de l’Office National du Conseil Agricole ;<br>
        • Vu la décision n°277/ONCA/DRHS/DRH/SGAP du 08 Février 2023, portant nomination de M. BOUDRA Abdelâali Directeur Régional de l’ONCA de la région de Rabat-Salé-Kénitra ;<br>
        • Vu la décision n° 567 /ONCA/DRHS/DRH/SGAP du 17 Mars 2025 portant nomination de M. BOUDRA Abdelâali Directeur Régional de l’ONCA de la région de Rabat-Salé-Kénitra en tant que sous-ordonnateur ;<br>
        • Vu la décision n° 568/ONCA/DRHS/DRH/SGAP du 17 Mars 2025 portant délégation de signature à M. BOUDRA Abdelâali, Directeur Régional de l’ONCA de la région de Rabat-Salé-Kénitra ; <br>
        • Vu le budget de fonctionnement de l’ONCA au titre de l’exercice 2025 ;<br>
        • Vu l'avis d'achat n° {{ $doc['numero_consultation'] }} ;<br>
        • Vu le budget d'investissement de l'ONCA délégué et notifié à la DRCA-RSK au titre de l’exercice 2025 ;<br>
        • Vu le bon de commande N° {{ $doc['numero_bc'] }} ;
    </p>

    <div class="decide-title">DÉCIDE</div>

    <p><span class="section">Article 1 :</span>
        Une commission est instituée pour la réception des prestations liées au Bon de Commande
        <span class="bold">{{ $doc['numero_bc'] }}</span> ayant pour objet :
    </p>
    <p class="bold" style="text-align: justify; font-size: 11px;">{{ $doc['objet'] }}</p>

    <p><span class="section">Article 2 :</span> La commission est composée de :</p>
    <table class="box">
        <tr><th>Nom et Prénom</th><th>Fonction</th><th style="width: 20%;">Qualité</th></tr>
        @foreach($doc['commission'] as $membre)
            <tr>
                <td>{{ data_get($membre, 'nom') }}</td>
                <td>{{ data_get($membre, 'fonction') }}</td>
                <td class="center">{{ data_get($membre, 'qualite') }}</td>
            </tr>
        @endforeach
    </table>

    <p><span class="section">Article 3 :</span>
        La-dite commission se réunira le <span class="bold">{{ $doc['date_reunion'] }}</span>
        à <span class="bold">{{ $doc['heure_reunion'] }}</span> au siège de la DRCA-RSK pour procéder à la réception {{ strtolower($doc['type_reception'] ?? 'définitive') }} desdites prestations.
    </p>
@php
    $presidentMember = null;
    $commissionList = data_get($doc, 'commission', []);
    if (is_array($commissionList)) {
        foreach ($commissionList as $m) {
            $q = mb_strtolower(data_get($m, 'qualite', ''));
            if (str_contains($q, 'président') || str_contains($q, 'president')) {
                $presidentMember = $m;
                break;
            }
        }
        if (!$presidentMember && !empty($commissionList)) {
            $presidentMember = $commissionList[0];
        }
    }

    $presQualite = mb_strtolower(data_get($presidentMember, 'qualite', ''));
    $presNom = mb_strtolower(data_get($presidentMember, 'nom', ''));

    $isFeminin = str_contains($presQualite, 'présidente') 
        || str_contains($presQualite, 'presidente') 
        || str_contains($presNom, 'mme') 
        || str_contains($presNom, 'mlle');

    $titrePresident = $isFeminin ? 'La présidente' : 'Le président';
    $estCharge = $isFeminin ? 'est chargée' : 'est chargé';
@endphp

    <p><span class="section">Article 4 :</span> {{ $titrePresident }} de la commission {{ $estCharge }} de l'exécution de la présente décision.</p>
</div>
</body>
</html>
