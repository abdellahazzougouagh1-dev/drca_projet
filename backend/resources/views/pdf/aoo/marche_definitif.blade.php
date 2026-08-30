<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Marché N° {{ $num_marche }} - {{ $titulaire_nom }}</title>
    <style>
        @page {
            margin: 12mm 15mm 15mm 15mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
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
        .center-header {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            line-height: 1.3;
            margin-top: 15px;
            margin-bottom: 20px;
        }
        .marche-title-box {
            border: 2px solid #000;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            margin: 20px 0;
        }
        .marche-num {
            font-size: 16pt;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 6px;
        }
        .marche-sub {
            font-size: 11pt;
            font-weight: bold;
        }
        .section-heading {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin-top: 25px;
            margin-bottom: 12px;
            background-color: #f1f5f9;
            padding: 6px;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
        }
        .article-title {
            font-size: 10.5pt;
            font-weight: bold;
            margin-top: 14px;
            margin-bottom: 4px;
        }
        .preamble-block {
            line-height: 1.6;
            text-align: justify;
            margin-bottom: 20px;
        }
        .table-dqe {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            margin-bottom: 15px;
            font-size: 9pt;
        }
        .table-dqe th, .table-dqe td {
            border: 1px solid #000;
            padding: 6px 8px;
        }
        .table-dqe th {
            background-color: #f8fafc;
            font-weight: bold;
            text-align: center;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 35px;
        }
        .signature-table td {
            width: 50%;
            border: 1px solid #000;
            padding: 12px;
            vertical-align: top;
            height: 100px;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>

    <!-- PAGE DE GARDE -->
    <table class="header-table">
        <tr>
            <td style="width: 35%;">
                @if(file_exists(public_path('images/logo-onca.png')))
                    <img src="{{ public_path('images/logo-onca.png') }}" style="height: 45px;" alt="ONCA">
                @endif
            </td>
            <td style="width: 30%; text-align: center; font-size: 8.5pt; font-weight: bold;">
                Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
            </td>
            <td style="width: 35%; text-align: right;">
                @if(file_exists(public_path('images/sceau-maroc.png')))
                    <img src="{{ public_path('images/sceau-maroc.png') }}" style="height: 45px;" alt="Royaume du Maroc">
                @endif
            </td>
        </tr>
    </table>

    <div class="center-header">
        DIRECTION RÉGIONALE DU CONSEIL AGRICOLE DE RABAT-SALÉ-KÉNITRA
    </div>

    <div class="marche-title-box">
        <div class="marche-num">MARCHÉ N° {{ $num_marche }}</div>
        <div class="marche-sub">Issu de l'APPEL D'OFFRES OUVERT N° {{ $num_aoo }}</div>
        <div style="margin-top: 10px; font-weight: bold; text-transform: uppercase;">
            {{ $objet_marche }}
        </div>
    </div>

    <div style="text-align: center; font-size: 13pt; font-weight: bold; margin-top: 25px; margin-bottom: 20px;">
        CAHIER DES PRESCRIPTIONS SPÉCIALES
    </div>

    <div style="text-align: justify; font-size: 9.5pt; margin-top: 20px; line-height: 1.5;">
        Passé en application de l'alinéa 1 du paragraphe 1, l'alinéa a) du paragraphe 3 de la partie I) de l'article 19 et du paragraphe 1 de l'article 20 et de l'alinéa b du paragraphe 3 de l'article 20 du décret n° 2-22-431 du 15 Chaabane 1444 (08 mars 2023) relatif aux marchés publics.
    </div>

    <!-- PREAMBULE -->
    <div class="page-break"></div>

    <div class="section-heading">PRÉAMBULE DU CAHIER DES PRESCRIPTIONS SPÉCIALES</div>

    <div class="preamble-block">
        Le présent marché est passé par appel d'offres ouvert sur offres de prix en application du décret n° 2-22-431 du 15 Chaabane 1444 (08 mars 2023) relatif aux marchés publics.
    </div>

    <div class="preamble-block">
        <strong>ENTRE :</strong><br>
        L'Office National du Conseil Agricole représenté par le Directeur Régional du Conseil Agricole de Rabat-Salé-Kénitra, en sa qualité de sous-ordonnateur, agissant au nom et pour le compte de l'ONCA, désigné ci-après par le terme <strong>« DRCA-RSK »</strong> ou <strong>« Maître d'ouvrage »</strong>.
        <div style="text-align: right; font-weight: bold;">D'UNE PART</div>
    </div>

    <div class="preamble-block">
        <strong>ET :</strong><br>
        <strong>M. {{ $representant_nom }}</strong> en qualité de <strong>{{ $qualite_gerant ?: 'Gérant' }}</strong>,<br>
        Agissant au nom et pour le compte de <strong>{{ $titulaire_nom }}</strong>,<br>
        @if(!empty($capital)) Au capital social de : {{ $capital }} DH,<br> @endif
        @if(!empty($patente)) Patente n° : {{ $patente }}, @endif
        @if(!empty($rc)) Registre de commerce de {{ $ville_rc ?: $ville }} sous le n° : {{ $rc }}, @endif<br>
        @if(!empty($cnss)) Affilié à la CNSS sous le n° : {{ $cnss }}, @endif
        @if(!empty($ice)) ICE n° : {{ $ice }}, @endif<br>
        Faisant élection de domicile au : {{ $adresse }} {{ !empty($ville) ? '- ' . $ville : '' }},<br>
        Compte bancaire (RIB) n° : {{ $rib ?: 'N/A' }} ouvert auprès de {{ $banque ?: 'Banque Populaire' }}.<br>
        Désigné ci-après par le terme <strong>« Prestataire »</strong> ou <strong>« Titulaire du marché »</strong>.
        <div style="text-align: right; font-weight: bold;">D'AUTRE PART</div>
    </div>

    <div style="text-align: center; font-weight: bold; margin: 20px 0;">
        « IL A ÉTÉ ARRÊTÉ ET CONVENU CE QUI SUIT »
    </div>

    <!-- CHAPITRE I -->
    <div class="section-heading">CHAPITRE I : CLAUSES ADMINISTRATIVES ET FINANCIÈRES</div>

    <div class="article-title">ARTICLE 1. OBJET DU MARCHÉ</div>
    <div class="preamble-block">
        Le présent marché a pour objet : <strong>{{ $marche->aoo->objet ?? $objet_marche }}</strong>.
        @if($marche->lot && strtolower(trim($marche->lot)) !== 'lot unique' && strtolower(trim($marche->lot)) !== 'unique')
        <br><strong>{{ stripos($marche->lot, 'lot') !== false ? $marche->lot : 'Lot ' . $marche->lot }} : {{ $objet_marche }}</strong>
        @endif
    </div>

    <div class="article-title">ARTICLE 2. MODE DE PASSATION</div>
    <div class="preamble-block">
        Marché passé par Appel d'Offres Ouvert N° <strong>{{ $num_aoo }}</strong> en application des dispositions du décret n° 2-22-431 relatif aux marchés publics.
    </div>

    <div class="article-title">ARTICLE 3. DÉLAI D'EXÉCUTION</div>
    <div class="preamble-block">
        Le délai d'exécution des prestations est fixé à <strong>{{ $delai_execution ?: '12' }} mois</strong> à compter de la date d'effet fixée par l'Ordre de Service de commencement.
    </div>

    <div class="article-title">ARTICLE 4. CAUTIONNEMENT DEFINITIF & RETENUE DE GARANTIE</div>
    <div class="preamble-block">
        Le montant du cautionnement définitif est fixé à <strong>3% (trois pour cent)</strong> du montant initial du marché TTC, soit la somme de <strong>{{ number_format($montant_ttc * 0.03, 2, ',', ' ') }} DH</strong>.
    </div>

    <div class="article-title">ARTICLE 5. MONTANT DU MARCHÉ</div>
    <div class="preamble-block">
        Le montant du présent marché est arrêté à la somme de :
        <ul>
            <li>Montant Hors TVA : <strong>{{ number_format($montant_ht, 2, ',', ' ') }} DH</strong></li>
            <li>TVA (20%) : <strong>{{ number_format($montant_tva, 2, ',', ' ') }} DH</strong></li>
            <li>Montant Total TTC : <strong>{{ number_format($montant_ttc, 2, ',', ' ') }} DH</strong> (<em>{{ $montant_ttc_lettres }}</em>)</li>
        </ul>
    </div>

    <!-- DQE TABLE -->
    <div class="section-heading">BORDEREAU DES PRIX - DÉTAIL ESTIMATIF</div>

    <table class="table-dqe">
        <thead>
            <tr>
                <th style="width: 8%;">N°</th>
                <th style="width: 45%;">Désignation des Prestations</th>
                <th style="width: 12%;">Unité</th>
                <th style="width: 10%;">Qté</th>
                <th style="width: 12.5%;">P.U HT (DH)</th>
                <th style="width: 12.5%;">Total HT (DH)</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($items) && count($items) > 0)
                @foreach($items as $idx => $item)
                    <tr>
                        <td style="text-align: center;">{{ $item['numero'] ?? ($idx + 1) }}</td>
                        <td>{{ $item['designation'] ?? '' }}</td>
                        <td style="text-align: center;">{{ $item['unite'] ?? 'U' }}</td>
                        <td style="text-align: center;">{{ $item['quantite'] ?? 1 }}</td>
                        <td style="text-align: right;">{{ number_format($item['pu_ht'] ?? 0, 2, ',', ' ') }}</td>
                        <td style="text-align: right;">{{ number_format(($item['quantite'] ?? 1) * ($item['pu_ht'] ?? 0), 2, ',', ' ') }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td style="text-align: center;">1</td>
                    <td>
                        {{ $marche->aoo->objet ?? $objet_marche }}<br>
                        @if($marche->lot && strtolower(trim($marche->lot)) !== 'lot unique' && strtolower(trim($marche->lot)) !== 'unique')
                        {{ stripos($marche->lot, 'lot') !== false ? $marche->lot : 'Lot ' . $marche->lot }} : {{ $objet_marche }}
                        @endif
                    </td>
                    <td style="text-align: center;">Forfait</td>
                    <td style="text-align: center;">1</td>
                    <td style="text-align: right;">{{ number_format($montant_ht, 2, ',', ' ') }}</td>
                    <td style="text-align: right;">{{ number_format($montant_ht, 2, ',', ' ') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- SIGNATURES -->
    <table class="signature-table">
        <tr>
            <td>
                <strong>LE MAÎTRE D'OUVRAGE</strong><br><br>
                A Kénitra, le .........................
            </td>
            <td>
                <strong>LE TITULAIRE DU MARCHÉ</strong><br>
                {{ $marche->aoo->objet ?? $objet_marche }}<br>
                @if($marche->lot && strtolower(trim($marche->lot)) !== 'lot unique' && strtolower(trim($marche->lot)) !== 'unique')
                {{ stripos($marche->lot, 'lot') !== false ? $marche->lot : 'Lot ' . $marche->lot }} : {{ $objet_marche }}
                @endif
                <span style="font-size: 8pt; color: #475569;">« Précédé par la mention Lu et Accepté »</span><br><br>
                A ........................., le .........................
            </td>
        </tr>
    </table>

</body>
</html>
