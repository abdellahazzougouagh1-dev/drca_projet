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
    @include('documents.partials.bc_typography')
</head>
<body>
    @include('documents.partials.bc_header')
    @include('documents.partials.bc_footer')
<div class="page">
@php
    $isConvention = (!empty($consultation) && ($consultation->mode_engagement === 'Convention' || str_starts_with(strtoupper($consultation->numero_consultation ?? ''), 'CONV')))
        || str_starts_with(strtoupper($doc['numero_consultation'] ?? ''), 'CONV');

    $lettresRefs = trim((string)($doc['lettres_consultation_refs'] ?? ''));

    if (!$lettresRefs && !empty($doc['lettres_consultation']) && is_array($doc['lettres_consultation'])) {
        $lNums = [];
        $lSuffix = '';
        foreach ($doc['lettres_consultation'] as $lc) {
            $n = trim((string)data_get($lc, 'numero_lettre', ''));
            $n = preg_replace('/^CONV\s*[-_]?\s*/i', '', $n);
            if ($n) {
                $p = explode('/', $n);
                if (count($p) > 1) {
                    $lNums[] = trim($p[0]);
                    if (!$lSuffix) {
                        $lSuffix = '/' . trim(implode('/', array_slice($p, 1)));
                    }
                } else {
                    $lNums[] = $n;
                }
            }
        }
        if (!empty($lNums)) {
            $year = $consultation->annee ?? date('Y');
            $lettresRefs = implode(',', $lNums) . ($lSuffix ?: "/{$year}/DRCA-RSK");
        }
    }

    if (!$lettresRefs) {
        $rawNum = $doc['numero_lettre'] ?? $consultation->numero_consultation ?? '';
        $cleanNum = preg_replace('/^CONV\s*[-_]?\s*/i', '', (string)$rawNum);
        $lettresRefs = $cleanNum ?: ('15,16,17/' . ($consultation->annee ?? date('Y')) . '/DRCA-RSK');
    }

    $lettresRefsClean = preg_replace('/^vu\s+(les|la)\s+lettres?\s*[:]?\s*/i', '', $lettresRefs);
    $lettresRefsClean = preg_replace('/^CONV\s*[-_]?\s*/i', '', $lettresRefsClean);
    $isMultipleLettres = str_contains($lettresRefsClean, ',') || str_contains($lettresRefsClean, 'et');

    $numeroConsultationClean = trim(preg_replace('/\bCONV\s*[-_]?\s*/i', '', (string)($doc['numero_consultation'] ?? '')));
    $numeroConsultationClean = trim(preg_replace('/^[-_\s]+|[-_\s]+$/', '', $numeroConsultationClean));
    $numeroConsultationDisplay = $isConvention ? $numeroConsultationClean : ($doc['numero_consultation'] ?? '');
@endphp

    <table class="meta">
        <tr>
            <td class="bold">N° : {{ $doc['numero_decision'] }}</td>
            <td class="right">Date : {{ $doc['date_document'] }}</td>
        </tr>
    </table>

    <div class="sub-title">Décision</div>
    <div class="header-desc">
        De nomination de la commission d'ouverture des plis<br>
        Consultation N° : {{ $numeroConsultationDisplay }}
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
        @if($isConvention)
        • Vu {{ $isMultipleLettres ? 'les lettres' : 'la lettre' }} <span class="bold">{{ $lettresRefsClean }}</span> ;<br>
        @endif
        • Vu le budget de fonctionnement de l’ONCA au titre de l’exercice 2025 ;<br>
        @if(!$isConvention)
        • Vu l'avis d'achat n° {{ $doc['numero_consultation'] }} ;<br>
        @endif
        • Vu le budget d'investissement de l'ONCA délégué et notifié à la DRCA-RSK au titre de l’exercice 2025.
    </p>

    <h1 class="decide-title">DÉCIDE</h1>

    <p><span class="section">Article 1 :</span>
    <br>
        Une commission est instituée à la Direction Régionale du Conseil Agricole de Rabat-Salé-Kénitra pour l'ouverture des plis relatifs à la consultation
        <span class="bold">{{ $numeroConsultationDisplay }}</span> ayant pour objet :
    </p>
    <p class="bold" style="text-align: justify; font-size: 11px;">{{ $doc['objet'] }}</p>

    <p><span class="section">Article 2 :</span><br>
            La commission est composée de :</p>
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
    <p><span class="section">Article 3 :</span><br>
        La-dite commission se réunira le <span class="bold">{{ $doc['date_reunion'] }}</span>
        à <span class="bold">{{ $doc['heure_reunion'] }}</span> au siège de la DRCA-RSK pour procéder à l’ouverture des plis.
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

    <p><span class="section">Article 4 :</span><br> {{ $titrePresident }} de la commission {{ $estCharge }} de l'exécution de la présente décision.</p>

</div>
</body>
</html>
