<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordre de Virement - {{ $ordre->num_ordre ?? 'OV' }}</title>
    <style>
        @page {
            margin: 22px 28px 22px 28px;
            size: A4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10.5px;
            color: #000;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }

        /* HEADER */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .header-logo {
            height: 72px;
            width: auto;
            max-height: 80px;
        }
        .header-center {
            text-align: center;
            font-size: 11.5px;
            font-weight: bold;
            color: #000;
        }
        .header-divider {
            border-bottom: 1.5px solid #000;
            margin-top: 4px;
            margin-bottom: 14px;
        }

        /* REFERENCE & DATE */
        .ref-date-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
            font-size: 11px;
            font-weight: bold;
        }
        .ref-date-table td {
            border: none;
            padding: 0;
        }

        /* TITLE BLOCK */
        .title-block {
            text-align: center;
            margin-bottom: 26px;
        }
        .doc-title {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .prep-a {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .recipient-title {
            font-size: 12.5px;
            font-weight: bold;
            line-height: 1.35;
            text-transform: uppercase;
        }

        /* INTRO PARAGRAPH */
        .intro-text {
            font-size: 11px;
            text-align: justify;
            line-height: 1.45;
            margin-bottom: 18px;
        }

        /* DETAILS TABLE */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
            font-size: 11px;
        }
        .details-table td {
            padding: 5px 0;
            vertical-align: top;
            border: none;
        }
        .col-lbl {
            width: 28%;
            font-weight: normal;
        }
        .col-sep {
            width: 3%;
            text-align: center;
            font-weight: bold;
        }
        .col-val {
            width: 69%;
            font-weight: bold;
        }

        /* CLOSING TEXT */
        .closing-text {
            font-size: 11px;
            line-height: 1.45;
            text-align: justify;
            margin-top: 15px;
            margin-bottom: 35px;
        }

        /* SIGNATURES TABLE */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            margin-top: 20px;
        }
        .sig-header-cell {
            background-color: #a3e635; /* Bright institutional light green */
            border: 1.5px solid #000;
            padding: 8px 6px;
            text-align: center;
            font-size: 9.5px;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            width: 50%;
            line-height: 1.25;
        }
        .sig-body-cell {
            border: 1.5px solid #000;
            height: 110px;
            vertical-align: top;
            background-color: #ffffff;
        }
    </style>
</head>
<body>

@php
    $logoOncaSrc   = file_exists(public_path('images/logo-onca.png'))   ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/logo-onca.png')))   : '';
    $sceauMarocSrc = file_exists(public_path('images/sceau-maroc.png')) ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) : '';

    $budgetType = strtoupper($ordonnancement->budget_type ?? 'INVESTISSEMENT');
    $exercice   = $ordonnancement->exercice ?? date('Y');

    $numOvClean = $ordre?->num_ordre ?: '42';
    $numOvVal   = preg_replace('/[^0-9]/', '', $numOvClean) ?: $numOvClean;

    $numOpClean = $ordonnancement->num_op ?: '49';
    $numOpVal   = preg_replace('/[^0-9]/', '', $numOpClean) ?: $numOpClean;

    $dateOv = $ordre?->date_ordre 
        ? \Carbon\Carbon::parse($ordre->date_ordre)->format('d/m/Y')
        : ($ordonnancement->date_ordonnancement ? \Carbon\Carbon::parse($ordonnancement->date_ordonnancement)->format('d/m/Y') : date('d/m/Y'));

    $montantOv = (float)($montant ?: ($ordre?->montant ?: ($ordonnancement->retenue_tva ?: 2730)));
    $beneficiaire = $ordre?->beneficiaire ?: 'OFFICE NATIONAL DU CONSEIL AGRICOLE';
    $ribBeneficiaire = $ordre?->rib_compte ?: '310 810 100 002 470 105 200 152';
    $refMarche = $ordonnancement->reference ?: 'Marché N°06/2024/DRCA-RSK';
@endphp

    <!-- 1. EN-TÊTE LOGOS -->
    <table class="header-table">
        <tr>
            <td style="width: 25%; text-align: left;">
                @if($logoOncaSrc)
                    <img src="{{ $logoOncaSrc }}" class="header-logo" alt="Logo ONCA">
                @endif
            </td>
            <td style="width: 50%; text-align: center;" class="header-center">
                Direction regionale du Conseil agricole Rabat-Salé-Kénitra
            </td>
            <td style="width: 25%; text-align: right;">
                @if($sceauMarocSrc)
                    <img src="{{ $sceauMarocSrc }}" class="header-logo" alt="Royaume du Maroc">
                @endif
            </td>
        </tr>
    </table>
    
    <div class="header-divider"></div>

    <!-- 2. RÉFÉRENCE & DATE -->
    <table class="ref-date-table">
        <tr>
            <td style="width: 65%;">
                /Référence: OV N° &nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ $numOvVal }}</strong>&nbsp;&nbsp;&nbsp;&nbsp; /DRCA-RSK/{{ $exercice }}
            </td>
            <td style="width: 35%; text-align: right;">
                Date: &nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ $dateOv }}</strong>
            </td>
        </tr>
    </table>

    <!-- 3. TITRE & DESTINATAIRE -->
    <div class="title-block">
        <div class="doc-title">ORDRE DE VIREMENT DE FOND</div>
        <div class="prep-a">A</div>
        <div class="recipient-title">
            MONSIEUR LE TRESORIER GENERAL DU ROYAUME<br>
            CHEF DE L'AGENCE BANCAIRE DE KENITRA
        </div>
    </div>

    <!-- 4. PARAGRAPHE INTRODUCTIF -->
    <div class="intro-text">
        Par le débit de notre compte courant N° <strong>310330100602470154760181</strong> , ONCA DR RABAT-SALE-KENITRA {{ $budgetType }},ouvert dans vos livres, veuillez virer :
    </div>

    <!-- 5. TABLEAU DES DETAILS -->
    <table class="details-table">
        <tr>
            <td class="col-lbl">La somme de</td>
            <td class="col-sep">:</td>
            <td class="col-val" style="font-size: 12px;">{{ number_format($montantOv, 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <td class="col-lbl">En lettres</td>
            <td class="col-sep">:</td>
            <td class="col-val" style="text-transform: uppercase;">
                {{ $montant_lettres ?: \App\Support\MontantEnLettres::convert($montantOv) }}
            </td>
        </tr>
        <tr>
            <td class="col-lbl">Au profit de</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $beneficiaire }}</td>
        </tr>
        <tr>
            <td class="col-lbl">Titulaire du compte</td>
            <td class="col-sep">:</td>
            <td class="col-val" style="font-family: monospace; font-size: 12px; letter-spacing: 0.5px;">
                {{ $ribBeneficiaire }}
            </td>
        </tr>
        <tr>
            <td class="col-lbl">Pour fin de règlement de</td>
            <td class="col-sep">:</td>
            <td class="col-val">{{ $refMarche }}</td>
        </tr>
        <tr>
            <td class="col-lbl" style="padding-top: 10px; font-weight: bold;">OP N°</td>
            <td class="col-sep" style="padding-top: 10px;"></td>
            <td class="col-val" style="padding-top: 10px;">
                <strong>{{ $numOpVal }}</strong> &nbsp;&nbsp;&nbsp;&nbsp; /DRCA-RSK/{{ $exercice }}
            </td>
        </tr>
    </table>

    <!-- 6. SALUTATIONS -->
    <div class="closing-text">
        Dans l’attente de votre avis de débit, veuillez agréer, Monsieur le Trésorier Général Du Royaume, l’expression de nos salutations distinguées.
    </div>

    <!-- 7. CADRE DE SIGNATURES -->
    <table class="signatures-table">
        <tr>
            <th class="sig-header-cell">
                LE DIRECTEUR REGIONAL DE L’OFFICE NATIONALE DU CONSEIL AGRICOLE DU Rabat-Salé-Kénitra
            </th>
            <th class="sig-header-cell">
                LE FONDE DE POUVOIRS DU TRESORIER PAYEUR DE L’ONCA AUPRES DE LA DIRECTION REGIONALE DU Rabat-Salé-Kénitra
            </th>
        </tr>
        <tr>
            <td class="sig-body-cell"></td>
            <td class="sig-body-cell"></td>
        </tr>
    </table>

</body>
</html>
