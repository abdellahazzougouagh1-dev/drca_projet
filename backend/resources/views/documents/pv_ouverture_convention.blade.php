<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>PV d'ouverture de plis - {{ $doc['numero_consultation'] ?? 'Convention' }}</title>
    <style>
        @page {
            margin: 10mm 12mm 10mm 12mm;
            size: A4 portrait;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5px;
            color: #000;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td, th {
            vertical-align: middle;
        }
        .bold {
            font-weight: bold;
        }
        .center {
            text-align: center;
        }
        .right {
            text-align: right;
        }
        .uppercase {
            text-transform: uppercase;
        }
        .bordered-table {
            width: 100%;
            border: 1px solid #000;
            border-collapse: collapse;
            margin-bottom: 5px;
            font-size: 9.5px;
        }
        .bordered-table th {
            border: 1px solid #000;
            background-color: #f8fafc;
            padding: 3px 4px;
            font-weight: bold;
            text-align: center;
        }
        .bordered-table td {
            border: 1px solid #000;
            padding: 3px 4px;
        }
    </style>
</head>
<body>
@php
    $logoOncaPath = public_path('images/logo-onca.png');
    $sceauMarocPath = public_path('images/sceau-maroc.png');
    $logoOnca = file_exists($logoOncaPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoOncaPath)) : '';
    $sceauMaroc = file_exists($sceauMarocPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($sceauMarocPath)) : '';

    $fournisseursConsultes = data_get($doc, 'fournisseurs_consultes')
        ?: (data_get($doc, 'fournisseurs') ?: []);

    $concurrentsList = data_get($doc, 'concurrents') ?: [];

    $comm = data_get($doc, 'commission', []);
    $president = collect($comm)->first(function($m) {
        $q = strtolower(data_get($m, 'qualite', ''));
        return str_contains($q, 'presid') || str_contains($q, 'présid');
    }) ?: ($comm[0] ?? null);

    $membres = collect($comm)->reject(function($m) use ($president) {
        return $m === $president;
    })->values();

    $presidentName = data_get($president, 'nom', 'Enneddam Wafaa');
    $membre1Name = data_get($membres, '0.nom', 'LAARISI HOUDA');
    $membre2Name = data_get($membres, '1.nom', 'BEKOUCHE Karim');

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
@endphp

    <!-- 1. EN-TÊTE OFFICIEL -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 5px;">
        <tr>
            <td style="width: 25%; text-align: left; vertical-align: middle;">
                @if($logoOnca)
                    <img src="{{ $logoOnca }}" style="height: 48px; width: auto;" alt="Logo ONCA">
                @endif
            </td>
            <td style="width: 50%; text-align: center; vertical-align: middle; font-size: 11px; font-weight: bold; line-height: 1.35;">
                Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
            </td>
            <td style="width: 25%; text-align: right; vertical-align: middle;">
                @if($sceauMaroc)
                    <img src="{{ $sceauMaroc }}" style="height: 48px; width: auto;" alt="Royaume du Maroc">
                @endif
            </td>
        </tr>
    </table>

    <!-- 2. TITRE PRINCIPAL ENCADRE -->
    <div style="border: 2px solid #000; padding: 4px 6px; text-align: center; font-weight: bold; font-size: 12.5px; text-transform: uppercase; margin-bottom: 4px;">
        PROCES VERBAL D'OUVERTURE DE PLIS
    </div>

    <!-- 3. REFERENCE DE LA CONSULTATION -->
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-bottom: 5px;">
        <tr>
            <td style="width: 50%; text-align: right; padding: 3px 12px; font-size: 10px; font-weight: bold; border-right: 1px solid #000;">
                Consultation
            </td>
            <td style="width: 50%; text-align: left; padding: 3px 12px; font-size: 10px; font-weight: bold;">
                {{ $doc['numero_consultation'] }}
            </td>
        </tr>
    </table>

    <!-- 4. TEXTE DE PRESENTATION DE LA COMMISSION -->
    <div style="font-size: 9.5px; line-height: 1.35; margin-bottom: 4px;">
        En date du <strong>{{ $doc['date_reunion'] }}</strong> &nbsp; à &nbsp; <strong>{{ $doc['heure_reunion'] }}</strong> &nbsp; la commission d’ouverture des plis instituée par la décision N° &nbsp; <strong>{{ $doc['numero_decision'] }}</strong> &nbsp; en date du &nbsp; <strong>{{ $doc['date_decision'] }}</strong> &nbsp; composée de :
    </div>

    <!-- 5. TABLEAU DE LA COMMISSION -->
    <table class="bordered-table" style="margin-bottom: 5px;">
        <thead>
            <tr>
                <th style="width: 35%; text-align: left;">Nom et Prénom</th>
                <th style="width: 50%; text-align: left;">Fonction</th>
                <th style="width: 15%; text-align: left;">Qualité</th>
            </tr>
        </thead>
        <tbody>
            @foreach($comm as $m)
            <tr>
                <td style="padding: 2.5px 5px;">{{ data_get($m, 'nom') }}</td>
                <td style="padding: 2.5px 5px;">{{ data_get($m, 'fonction') }}</td>
                <td style="padding: 2.5px 5px;">{{ data_get($m, 'qualite', 'Membre') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- 6. DETAILS DE LA PRESTATION ET DES LETTRES -->
    <div style="font-size: 9.5px; line-height: 1.35; margin-bottom: 2px;">
        S’est réunie au siège de la DRCA-RSK en vue de procéder à l’ouverture des plis concernant l’offre de prix N° &nbsp; <strong>{{ $doc['numero_consultation'] }}</strong> &nbsp; relative au prestation de :
    </div>
    <div style="font-size: 9.5px; font-weight: bold; margin: 2px 0 2px 10px;">
        {{ $doc['objet'] }}
    </div>
    <div style="font-size: 9.5px; margin-bottom: 2px;">
        {{ $isMultipleLettres ? 'Numéros des lettres de consultation' : 'Numéro de la lettre de consultation' }} : &nbsp; <strong>{{ $lettresRefsClean }}</strong>
    </div>
    <div style="font-size: 9.5px; margin-bottom: 4px;">
        La liste des fournisseurs qui ont été respectivement consultés sont:
    </div>

    <!-- 7. TABLEAU DES FOURNISSEURS CONSULTES -->
    <table class="bordered-table" style="margin-bottom: 5px;">
        <thead>
            <tr>
                <th style="width: 6%; text-align: center;">N°</th>
                <th style="width: 38%; text-align: center;">Les Participants</th>
                <th style="width: 42%; text-align: center;">Adresses</th>
                <th style="width: 14%; text-align: center;">Ville</th>
            </tr>
        </thead>
        <tbody>
            @php $countF = max(3, count($fournisseursConsultes)); @endphp
            @for($i = 0; $i < $countF; $i++)
            @php $f = $fournisseursConsultes[$i] ?? null; @endphp
            <tr style="height: 22px;">
                <td style="text-align: center;">{{ $i + 1 }}</td>
                <td style="text-align: center; font-weight: bold; text-transform: uppercase;">
                    {{ $f ? data_get($f, 'nom', data_get($f, 'raison_sociale', '')) : '' }}
                </td>
                <td style="text-align: center; font-size: 8.5px;">
                    {{ $f ? data_get($f, 'adresse', '') : '' }}
                </td>
                <td style="text-align: center;">
                    {{ $f ? data_get($f, 'ville', '') : '' }}
                </td>
            </tr>
            @endfor
        </tbody>
    </table>

    <!-- 8. PHRASE INTRODUCTIVE DES OFFRES REÇUES -->
    <div style="font-size: 9.5px; line-height: 1.35; margin-bottom: 4px;">
        L’ouverture des plis contenant les propositions faites par les concurrents et parvenus par courrier ou déposés auprès de la DRCA-RSK a donné les résultats suivants:
    </div>

    <!-- 9. TABLEAU DES RESULTATS DES CONCURRENTS -->
    <table class="bordered-table" style="margin-bottom: 5px;">
        <thead>
            <tr>
                <th rowspan="2" style="width: 6%; text-align: center;">N°</th>
                <th rowspan="2" style="width: 44%; text-align: center;">Nom des concurrents</th>
                <th colspan="2" style="width: 50%; text-align: center;">Montant de l'offre (TTC)</th>
            </tr>
            <tr>
                <th style="width: 25%; text-align: center;">Avant vérification</th>
                <th style="width: 25%; text-align: center;">Après vérification</th>
            </tr>
        </thead>
        <tbody>
            @php $countC = max(3, count($concurrentsList)); @endphp
            @for($i = 0; $i < $countC; $i++)
            @php $c = $concurrentsList[$i] ?? null; @endphp
            <tr style="height: 20px;">
                <td style="text-align: center;">{{ $i + 1 }}</td>
                <td style="font-weight: bold; text-transform: uppercase; padding-left: 6px;">
                    {{ $c ? data_get($c, 'nom', '') : '' }}
                </td>
                <td style="text-align: center;">
                    @if($c && data_get($c, 'montant_avant') !== null && data_get($c, 'montant_avant') !== '')
                        {{ number_format((float) data_get($c, 'montant_avant'), 2, ',', ' ') }}
                    @else
                        -
                    @endif
                </td>
                <td style="text-align: center;">
                    @if($c && data_get($c, 'montant_apres') !== null && data_get($c, 'montant_apres') !== '')
                        {{ number_format((float) data_get($c, 'montant_apres'), 2, ',', ' ') }}
                    @elseif($c && (float) data_get($c, 'montant', 0) > 0)
                        {{ number_format((float) data_get($c, 'montant'), 2, ',', ' ') }}
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endfor
        </tbody>
    </table>

    <!-- 10. DECISION D'ATTRIBUTION ET MONTANT -->
    <div style="font-size: 9.5px; line-height: 1.35; margin-bottom: 2px;">
        Enfin, la commission a décidé de proposer à l’autorité compétente de retenir l’offre qu’elle juge la plus avantageuse pour l’administration présentée ici par la société :
    </div>
    <table class="bordered-table" style="margin-bottom: 4px;">
        <tr>
            <td colspan="2" style="text-align: center; font-weight: bold; font-size: 10.5px; text-transform: uppercase; padding: 3px 5px; background-color: #ffffff;">
                {{ $doc['attributaire'] ?? '' }}
            </td>
        </tr>
        <tr>
            <td style="width: 45%; padding: 2.5px 6px;">
                Pour un montant global (TTC) de :
            </td>
            <td style="width: 55%; padding: 2.5px 6px; font-weight: bold; text-align: center;">
                {{ (float)($doc['montant_retenu'] ?? 0) > 0 ? number_format((float)$doc['montant_retenu'], 2, ',', ' ') . ' DH TTC' : '-' }}
            </td>
        </tr>
        <tr>
            <td style="width: 45%; padding: 2.5px 6px;">
                En lettres :
            </td>
            <td style="width: 55%; padding: 2.5px 6px; font-weight: bold;">
                {{ (float)($doc['montant_retenu'] ?? 0) > 0 ? ($doc['montant_en_lettres'] ?? '-') : '-' }}
            </td>
        </tr>
    </table>

    <!-- 11. CLÔTURE DE SEANCE ET DATE -->
    <table style="width: 100%; margin-bottom: 4px; font-size: 9.5px;">
        <tr>
            <td style="width: 50%; vertical-align: middle;">
                La séance est levée le même jour &nbsp;&nbsp; à &nbsp;&nbsp; <strong>{{ $doc['heure_fin'] ?? '13:15' }}</strong>
            </td>
            <td style="width: 50%; text-align: right; vertical-align: middle;">
                Fait à Kénitra le: &nbsp;&nbsp; <strong>{{ $doc['date_document'] ?? $doc['date_reunion'] }}</strong>
            </td>
        </tr>
    </table>

    <!-- 12. TABLEAU DES SIGNATURES -->
    <table class="bordered-table" style="text-align: center; margin-bottom: 0;">
        <thead>
            <tr style="background-color: #f8fafc; font-weight: bold;">
                <th style="width: 33.33%; text-align: center; padding: 2.5px 4px;">Président</th>
                <th style="width: 33.33%; text-align: center; padding: 2.5px 4px;">Membre 1</th>
                <th style="width: 33.33%; text-align: center; padding: 2.5px 4px;">Membre 2</th>
            </tr>
        </thead>
        <tbody>
            <tr style="font-weight: bold;">
                <td style="padding: 3px 4px;">{{ $presidentName }}</td>
                <td style="padding: 3px 4px;">{{ $membre1Name }}</td>
                <td style="padding: 3px 4px;">{{ $membre2Name }}</td>
            </tr>
            <tr style="height: 48px;">
                <td style="vertical-align: bottom; padding-bottom: 3px; font-size: 7.5px; color: #888;">(Signature)</td>
                <td style="vertical-align: bottom; padding-bottom: 3px; font-size: 7.5px; color: #888;">(Signature)</td>
                <td style="vertical-align: bottom; padding-bottom: 3px; font-size: 7.5px; color: #888;">(Signature)</td>
            </tr>
        </tbody>
    </table>

</body>
</html>

