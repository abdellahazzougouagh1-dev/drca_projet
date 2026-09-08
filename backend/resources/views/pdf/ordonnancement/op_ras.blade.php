<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordre de Paiement (RAS) - {{ $ordre->num_ordre ?? $ordonnancement->num_op ?? 'OP' }}</title>
    <style>
        @page {
            margin: 18px 22px 18px 22px;
            size: A4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5px;
            color: #000;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }

        /* HEADER */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
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
            font-size: 11px;
            font-weight: bold;
            color: #111;
        }
        .header-right {
            text-align: right;
        }

        /* BUDGET & DECLARATION BOX */
        .budget-box {
            width: 100%;
            margin-bottom: 8px;
        }
        .budget-table {
            width: 44%;
            margin-left: auto;
            border-collapse: collapse;
            font-size: 9.5px;
        }
        .budget-table td {
            border: 1.2px solid #000;
            padding: 3px 8px;
            font-weight: bold;
        }
        .budget-table .lbl {
            text-align: center;
            width: 48%;
            background-color: #ffffff;
        }
        .budget-table .val {
            text-align: center;
            width: 52%;
        }

        /* TITRE OP */
        .title-container {
            text-align: center;
            margin: 6px 0 6px 0;
        }
        .title-container h1 {
            display: inline-block;
            margin: 0;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            text-decoration: underline;
        }

        /* OP N° & DATE BOX */
        .op-ref-table {
            width: 44%;
            margin-left: auto;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 9.5px;
            font-weight: bold;
        }
        .op-ref-table td {
            border: 1.2px solid #000;
            padding: 3px 6px;
        }

        /* GENERAL TABLES */
        .table-section {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #000;
            margin-bottom: 8px;
        }
        .table-section th, .table-section td {
            border: 1.2px solid #000;
            padding: 4px 6px;
            font-size: 9.5px;
        }
        .section-header {
            background-color: #e5e0d8;
            text-align: center;
            font-weight: bold;
            font-size: 10.5px;
            padding: 4px 0;
            text-transform: uppercase;
        }
        .cell-label {
            font-weight: bold;
            width: 20%;
            text-align: center;
            background-color: #ffffff;
            vertical-align: middle;
        }
        .cell-value {
            vertical-align: middle;
            font-weight: bold;
        }

        /* PIECES JOINTES TABLE */
        .table-pj {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #000;
            margin-bottom: 8px;
        }
        .table-pj th {
            border: 1.2px solid #000;
            padding: 4px;
            font-size: 9.5px;
            font-weight: bold;
            text-align: center;
            background-color: #ffffff;
        }
        .table-pj td {
            border: 1.2px solid #000;
            padding: 4px 6px;
            font-size: 9px;
            vertical-align: middle;
        }

        /* IMPUTATION TABLE */
        .table-imput {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #000;
            margin-bottom: 8px;
            text-align: center;
        }
        .table-imput th {
            background-color: #e5e0d8;
            border: 1.2px solid #000;
            padding: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .table-imput td {
            border: 1.2px solid #000;
            padding: 4px;
            font-size: 9.5px;
            font-weight: bold;
        }

        /* VISA SIGNATURE BOX */
        .visa-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #000;
            margin-top: 4px;
        }
        .visa-table th {
            background-color: #e5e0d8;
            text-align: center;
            font-weight: bold;
            font-size: 9.5px;
            padding: 4px 0;
            border: 1.2px solid #000;
            width: 50%;
        }
        .visa-table td {
            height: 80px;
            border: 1.2px solid #000;
            vertical-align: top;
        }
    </style>
</head>
<body>

@php
    $logoOncaSrc   = file_exists(public_path('images/logo-onca.png'))   ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/logo-onca.png')))   : '';
    $sceauMarocSrc = file_exists(public_path('images/sceau-maroc.png')) ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) : '';

    $budgetType = $ordonnancement->budget_type ?? 'Investissement';
    $exercice   = $ordonnancement->exercice ?? date('Y');
    
    // Détermination de la nature de la RAS (IS ou TVA)
    $typeMvt = $ordre?->type_mouvement ?? '';
    $creance = $ordre?->creance ?? '';
    $reqNature = request('nature') ?: request('type_ras');
    if (stripos($typeMvt, 'TVA') !== false || stripos($creance, 'TVA') !== false || $reqNature === 'tva' || request('type') === 'op_ras_tva') {
        $natureRas = 'RAS / TVA';
        $isTva = true;
    } else {
        $natureRas = 'RAS / IS';
        $isTva = false;
    }

    $numOpClean = $ordre?->num_ordre ?: ($ordonnancement->num_op ?: '49');
    $numOpVal = preg_replace('/[^0-9]/', '', $numOpClean) ?: $numOpClean;
    $suffixeOp = '/DRCA-RSK/' . $exercice;

    $dateOp = $ordre?->date_ordre 
        ? \Carbon\Carbon::parse($ordre->date_ordre)->format('d/m/Y')
        : ($ordonnancement->date_ordonnancement ? \Carbon\Carbon::parse($ordonnancement->date_ordonnancement)->format('d/m/Y') : date('d/m/Y'));

    $beneficiaireFisc = $ordre?->beneficiaire ?: 'Receveur de l’administration fiscale';
    $societeFournisseur = $ordonnancement->fournisseur?->raison_sociale ?: $ordonnancement->beneficiaire_nom;
    $adresseFournisseur = $ordonnancement->fournisseur?->adresse ?: 'APPT N°1 Imm 10 Bloc G Al Majd Partie 04 Beni Mellal';

    $modePaiement = $ordre?->mode_paiement ?: 'TELEPAIEMENT';

    if ($isTva) {
        $montantRas = (float)($montant ?: ($ordre?->montant ?: ($ordonnancement->retenue_tva ?: $ordonnancement->ras_total)));
    } else {
        $montantRas = (float)($montant ?: ($ordre?->montant ?: ($ordonnancement->retenue_ias ?: $ordonnancement->ras_total)));
    }
    
    $montantHt = (float)($ordonnancement->liquidation?->montant_brut_ht ?: ($ordonnancement->liquidation?->montant_ht ?: ($ordonnancement->montant_brut / 1.2)));

    $numMarche = $ordonnancement->reference ?: 'Marché N°06/2024/DRCA-RSK';
    $objetMarche = $ordonnancement->intitule_depense ?: ($ordonnancement->liquidation?->objet_liquidation ?: 'l’organisation de voyages d’agriculteurs et de techniciens, en lot unique');

    $factureRef = $ordonnancement->liquidation?->num_facture 
        ? 'Facture N° ' . $ordonnancement->liquidation->num_facture . ($ordonnancement->liquidation->date_facture ? ' du ' . \Carbon\Carbon::parse($ordonnancement->liquidation->date_facture)->format('d-m-Y') : '')
        : 'Facture N° 04-2024 du 23-12-2024';

    $oiRef = $isTva ? ('OI N°' . ($numOpVal ? ($numOpVal - 10 > 0 ? $numOpVal - 10 : 39) : '39') . '/' . $exercice . '/DRCA-RSK') : ('OI N°26/' . $exercice . '/DRCA-RSK');
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
            <td style="width: 25%; text-align: right;" class="header-right">
                @if($sceauMarocSrc)
                    <img src="{{ $sceauMarocSrc }}" class="header-logo" alt="Royaume du Maroc">
                @endif
            </td>
        </tr>
    </table>

    <!-- 2. BUDGET & NATURE DE LA RAS -->
    <div class="budget-box">
        <table class="budget-table">
            <tr>
                <td class="lbl">Budget</td>
                <td class="val">{{ $budgetType }}</td>
            </tr>
            <tr>
                <td class="lbl">Déclaration</td>
                <td class="val">Retenue à la source</td>
            </tr>
            <tr>
                <td class="lbl">Exercice</td>
                <td class="val">{{ $exercice }}</td>
            </tr>
            <tr>
                <td class="lbl">Nature de la RAS</td>
                <td class="val">{{ $natureRas }}</td>
            </tr>
        </table>
    </div>

    <!-- 3. TITRE -->
    <div class="title-container">
        <h1>ORDRE DE PAIEMENT (OP)</h1>
    </div>

    <!-- 4. OP N° & DATE -->
    <table class="op-ref-table">
        <tr>
            <td style="width: 28%; text-align: center;">OP N°</td>
            <td style="width: 20%; text-align: center; font-size: 11px;">{{ $numOpVal }}</td>
            <td style="width: 52%; text-align: center;">{{ $suffixeOp }}</td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: center;">DATE</td>
            <td style="text-align: center;">{{ $dateOp }}</td>
        </tr>
    </table>

    <!-- 5. TABLEAU RENSEIGNEMENTS SUR LA DÉPENSE -->
    <table class="table-section">
        <tr>
            <th colspan="4" class="section-header">RENSEIGNEMENTS SUR LA DEPENSE</th>
        </tr>
        <tr>
            <td class="cell-label" style="width: 22%;">BENEFICIAIRE</td>
            <td colspan="3" class="cell-value" style="font-size: 10px;">{{ $beneficiaireFisc }}</td>
        </tr>
        @if($isTva)
        <tr>
            <td class="cell-label" style="line-height: 1.15;">OPERATEUR<br>CHARGE DE<br>TELEPAIEMENT</td>
            <td style="width: 38%; font-weight: bold; font-size: 9px; text-transform: uppercase; vertical-align: middle;">
                OFFICE NATIONAL DU CONSEIL AGRICOLE
            </td>
            <td colspan="2" style="font-weight: bold; font-size: 10px; font-family: monospace; text-align: center; vertical-align: middle;">
                310 810 100 002 470 105 200 152
            </td>
        </tr>
        @else
        <tr>
            <td class="cell-label">Société soumise à la retenue à la source</td>
            <td colspan="3" class="cell-value">
                <div style="font-size: 10px; text-transform: uppercase;">{{ $societeFournisseur }}</div>
                <div style="font-weight: normal; font-size: 9px; margin-top: 2px;">{{ $adresseFournisseur }}</div>
            </td>
        </tr>
        @endif
        <tr>
            <td class="cell-label">MODE DE PAIEMENT</td>
            <td colspan="3" class="cell-value" style="text-transform: uppercase;">{{ $modePaiement }}</td>
        </tr>
        <tr>
            <td class="cell-label" rowspan="2">OBJET</td>
            <td style="width: 38%; text-align: center; font-weight: bold; background-color: #ffffff;">
                Retenue a la source concernants
            </td>
            <td colspan="2" style="font-weight: bold; text-align: center;">
                {{ $numMarche }}
            </td>
        </tr>
        <tr>
            <td style="text-align: center; font-weight: bold; background-color: #ffffff;">
                Pour objet de :
            </td>
            <td colspan="2" style="font-weight: bold; text-align: justify; font-size: 9px;">
                {{ $objetMarche }}
            </td>
        </tr>
        <tr>
            <td class="cell-label">Référence</td>
            <td colspan="3" class="cell-value">{{ $numMarche }}</td>
        </tr>
    </table>

    <!-- 6. TABLEAU MONTANT / PIÈCES JOINTES / MODE DE PAIEMENT -->
    <table class="table-pj">
        <thead>
            <tr>
                <th style="width: 20%;">MONTANT (DH)</th>
                <th style="width: 58%;">PIECES JOINTES:</th>
                <th style="width: 22%;">MODE DE PAIEMENT</th>
            </tr>
        </thead>
        <tbody>
            @if($isTva)
            <tr>
                <td rowspan="3" style="text-align: center; font-size: 12px; font-weight: bold; vertical-align: middle;">
                    {{ number_format($montantRas, 2, ',', ' ') }}
                </td>
                <td>
                    <span style="font-weight: bold; margin-right: 6px;">1</span>
                    {{ $factureRef }}
                </td>
                <td rowspan="3" style="text-align: center; font-size: 10px; font-weight: bold; vertical-align: middle; text-transform: uppercase;">
                    VIREMENT
                </td>
            </tr>
            <tr>
                <td>
                    <span style="font-weight: bold; margin-right: 6px;">2</span>
                    {{ $oiRef }}
                </td>
            </tr>
            <tr>
                <td>
                    <span style="font-weight: bold; margin-right: 6px;">3</span>
                    Etat de liquidation
                </td>
            </tr>
            @else
            <tr>
                <td rowspan="4" style="text-align: center; font-size: 12px; font-weight: bold; vertical-align: middle;">
                    {{ number_format($montantRas, 2, ',', ' ') }}
                </td>
                <td>
                    <span style="font-weight: bold; margin-right: 6px;">1</span>
                    {{ $factureRef }}
                </td>
                <td rowspan="4" style="text-align: center; font-size: 10px; font-weight: bold; vertical-align: middle; text-transform: uppercase;">
                    VIREMENT
                </td>
            </tr>
            <tr>
                <td>
                    <span style="font-weight: bold; margin-right: 6px;">2</span>
                    {{ $oiRef }}
                </td>
            </tr>
            <tr>
                <td>
                    <span style="font-weight: bold; margin-right: 6px;">3</span>
                    Etat de liquidation
                </td>
            </tr>
            <tr>
                <td>
                    <span style="font-weight: bold; margin-right: 6px;">4</span>
                    Autorisation de TELEPAIEMENT
                </td>
            </tr>
            @endif
            <tr>
                <td style="font-weight: bold; text-align: center;">SOMME A PAYER<br><small>(en lettres)</small></td>
                <td colspan="2" style="font-weight: bold; text-transform: uppercase; font-size: 9.5px; padding: 5px 8px;">
                    {{ $montant_lettres ?: \App\Support\MontantEnLettres::convert($montantRas) }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- 7. TABLEAU IMPUTATION COMPTABLE -->
    <table class="table-imput">
        <thead>
            <tr>
                <th colspan="6" class="section-header" style="font-size: 9.5px; padding: 3px;">IMPUTATION COMPTABLE</th>
            </tr>
            <tr>
                <th style="width: 14%;">Chap</th>
                <th style="width: 14%;">Art</th>
                <th style="width: 14%;">Parag</th>
                <th style="width: 14%;">Ligne</th>
                <th style="width: 22%;">Montant HT</th>
                <th style="width: 22%;">Montant de la RAS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $ordonnancement->chapitre ?? '' }}</td>
                <td>{{ $ordonnancement->article ?? '415' }}</td>
                <td>{{ $ordonnancement->paragraphe ?? '20' }}</td>
                <td>{{ $ordonnancement->ligne ?? '12' }}</td>
                <td>{{ number_format($montantHt, 2, ',', ' ') }}</td>
                <td>{{ number_format($montantRas, 2, ',', ' ') }}</td>
            </tr>
            <tr>
                <td colspan="4" style="background-color: #e5e0d8; font-size: 8.5px; font-weight: bold;">
                    INTITULE DE LA RUBRIQUE
                </td>
                <td colspan="2" style="background-color: #ffffff;"></td>
            </tr>
            <tr>
                <td colspan="4" style="text-align: left; font-size: 8.5px; font-weight: normal; padding: 4px 6px;">
                    {{ $ordonnancement->intitule_depense ?: 'Frais d\'organisation et de participation aux journées de sensibilisation, de formation, d\'information et de conseil agricole' }}
                </td>
                <td colspan="2" style="text-align: center;">-</td>
            </tr>
        </tbody>
    </table>

    <!-- 8. VISA & SIGNATURES -->
    <table class="visa-table">
        <tr>
            <th>VISA DU SOUS-ORDONNATEUR</th>
            <th>VISA DU FONDE DE POUVOIRS</th>
        </tr>
        <tr>
            <td></td>
            <td></td>
        </tr>
    </table>

</body>
</html>
