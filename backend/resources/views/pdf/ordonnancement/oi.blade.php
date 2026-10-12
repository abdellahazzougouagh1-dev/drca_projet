<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordre d'Imputation - {{ $ordre->num_ordre ?? 'OI' }}</title>
    <style>
        @page {
            margin: 18px 24px 18px 24px;
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
            font-size: 11.5px;
            font-weight: bold;
            color: #000;
        }
        .header-right {
            text-align: right;
        }
        .header-divider {
            border-bottom: 1.5px solid #000;
            margin-top: 4px;
            margin-bottom: 10px;
        }

        /* BUDGET TOP RIGHT BOX */
        .budget-box-table {
            width: 36%;
            margin-left: auto;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 9.5px;
            font-weight: bold;
        }
        .budget-box-table td {
            border: 1.2px solid #000;
            padding: 3px 6px;
        }
        .budget-box-table .lbl {
            width: 48%;
            background-color: #ffffff;
            text-align: center;
        }
        .budget-box-table .val {
            width: 52%;
            text-align: center;
        }

        /* TITLE BANNER */
        .title-banner {
            background-color: #e5e0d8;
            border: 1.2px solid #000;
            text-align: center;
            padding: 5px 0;
            margin-bottom: 8px;
        }
        .title-banner h1 {
            margin: 0;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* OI REF & DATE TABLE */
        .oi-ref-table {
            width: 36%;
            margin-left: auto;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 9.5px;
            font-weight: bold;
        }
        .oi-ref-table td {
            border: 1.2px solid #000;
            padding: 3px 6px;
        }

        /* SECTION TABLE */
        .table-section {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #000;
            margin-bottom: 8px;
        }
        .table-section td, .table-section th {
            border: 1.2px solid #000;
            padding: 4px 6px;
            font-size: 9.5px;
        }
        .section-header {
            background-color: #e5e0d8;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            padding: 4px 0;
            text-transform: uppercase;
        }
        .lbl-cell {
            font-weight: bold;
            width: 24%;
            background-color: #ffffff;
            vertical-align: middle;
        }
        .val-cell {
            vertical-align: middle;
            font-weight: bold;
        }

        /* IMPUTATION COMPTABLE TABLE */
        .table-imput {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #000;
            margin-bottom: 8px;
            text-align: center;
        }
        .table-imput th, .table-imput td {
            border: 1.2px solid #000;
            padding: 3.5px 4px;
            font-size: 9px;
            font-weight: bold;
        }
        .table-imput thead th {
            background-color: #e5e0d8;
        }

        /* VISA SOUS-ORDONNATEUR */
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
            font-size: 10px;
            padding: 4px 0;
            border: 1.2px solid #000;
        }
        .visa-table td {
            height: 90px;
            border: 1.2px solid #000;
            vertical-align: top;
            background-color: #ffffff;
        }
    </style>
</head>
<body>

@php
    $logoOncaSrc   = file_exists(public_path('images/logo-onca.png'))   ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/logo-onca.png')))   : '';
    $sceauMarocSrc = file_exists(public_path('images/sceau-maroc.png')) ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) : '';

    $budgetType = $ordonnancement->budget_type ?? 'Investissement';
    $exercice   = $ordonnancement->exercice ?? date('Y');

    $numOiClean = $ordre?->num_ordre ?: '41';
    $numOiVal   = preg_replace('/[^0-9]/', '', $numOiClean) ?: $numOiClean;
    $suffixeOi  = '/DRCA-RSK/' . $exercice;

    $dateOi = $ordre?->date_ordre 
        ? \Carbon\Carbon::parse($ordre->date_ordre)->format('d/m/Y')
        : ($ordonnancement->date_ordonnancement ? \Carbon\Carbon::parse($ordonnancement->date_ordonnancement)->format('d/m/Y') : date('d/m/Y'));

    $beneficiaire = 'DIRECTION GENERALE DES IMPOTS';
    $formeEngagement = $ordonnancement->type_procedure ?? 'Convention';
    if (stripos($ordonnancement->reference, 'Convention') !== false) {
        $formeEngagement = 'Convention';
    } elseif (stripos($ordonnancement->reference, 'Marché') !== false) {
        $formeEngagement = 'Marché';
    } elseif (stripos($ordonnancement->reference, 'BC') !== false || stripos($ordonnancement->reference, 'Bon de commande') !== false) {
        $formeEngagement = 'Bon de commande';
    }

    $refMarche = $ordonnancement->reference ?: 'Convention N°04/2024/DRCA-RSK';
    $objetDepense = $ordonnancement->intitule_depense ?: ($ordonnancement->liquidation?->objet_liquidation ?: 'Prestation de transport des agriculteurs pratiquant le semis direct pour assurer leurs participation aux ateliers provinciaux organisés par la DRCA de RSK');

    $montantOi = (float)($montant ?: ($ordre?->montant ?: ($ordonnancement->retenue_tva ?: ($ordonnancement->retenue_ias ?: 3666))));
    $modeReglement = $ordre?->mode_paiement ?: 'Virement';

    $ribDebit = '310330100602470154760181';
    $agenceDebit = 'T.P KENITRA';
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

    <div class="header-divider"></div>

    <!-- 2. BUDGET TOP RIGHT BOX -->
    <table class="budget-box-table">
        <tr>
            <td class="lbl">BUDGET</td>
            <td class="val">{{ $budgetType }}</td>
        </tr>
        <tr>
            <td class="lbl">EXERCICE :</td>
            <td class="val">{{ $exercice }}</td>
        </tr>
        <tr>
            <td class="lbl">ANNEE D'ORIGINE:</td>
            <td class="val">{{ $exercice }}</td>
        </tr>
    </table>

    <!-- 3. BANNIÈRE TITRE -->
    <div class="title-banner">
        <h1>ORDRE D'IMPUTATION (OI)</h1>
    </div>

    <!-- 4. N° OI & DATE -->
    <table class="oi-ref-table">
        <tr>
            <td style="width: 25%; text-align: center;">N°:</td>
            <td style="width: 25%; text-align: center; font-size: 11px;">{{ $numOiVal }}</td>
            <td style="width: 50%; text-align: center;">{{ $suffixeOi }}</td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: center;">DATE</td>
            <td style="text-align: center;">{{ $dateOi }}</td>
        </tr>
    </table>

    <!-- 5. TABLEAU RENSEIGNEMENTS SUR L'IMPUTATION -->
    <table class="table-section">
        <tr>
            <th colspan="4" class="section-header">RENSEIGNEMENTS SUR L'IMPUTATION</th>
        </tr>
        <tr>
            <td class="lbl-cell">Bénéficiaire</td>
            <td colspan="3" class="val-cell">{{ $beneficiaire }}</td>
        </tr>
        <tr>
            <td class="lbl-cell" rowspan="2">Objet</td>
            <td style="width: 38%; text-align: center; font-weight: bold; background-color: #ffffff;">
                Retenue à la source concernants :
            </td>
            <td colspan="2" class="val-cell" style="text-align: center;">
                {{ $refMarche }}
            </td>
        </tr>
        <tr>
            <td style="text-align: center; font-weight: bold; background-color: #ffffff;">
                Pour objet de :
            </td>
            <td colspan="2" class="val-cell" style="text-align: justify; font-size: 9px; line-height: 1.25;">
                {{ $objetDepense }}
            </td>
        </tr>
        <tr>
            <td class="lbl-cell">Forme d'engagement</td>
            <td colspan="3" class="val-cell">{{ $formeEngagement }}</td>
        </tr>
        <tr>
            <td class="lbl-cell">Refrerence (N°)</td>
            <td colspan="3" class="val-cell">{{ $refMarche }}</td>
        </tr>
        <tr>
            <td class="lbl-cell">Montant (Dh)</td>
            <td colspan="3" class="val-cell" style="text-align: right; font-size: 11px; padding-right: 15px;">
                {{ number_format($montantOi, 2, ',', ' ') }}
            </td>
        </tr>
        <tr>
            <td class="lbl-cell">Montant en lettre</td>
            <td colspan="3" class="val-cell" style="text-transform: uppercase; font-size: 9px;">
                {{ $montant_lettres ?: \App\Support\MontantEnLettres::convert($montantOi) }}
            </td>
        </tr>
        <tr>
            <td class="lbl-cell">Mode de règlement</td>
            <td colspan="3" class="val-cell">{{ $modeReglement }}</td>
        </tr>
    </table>

    <!-- 6. TABLEAU IMPUTATION COMPTABLE -->
    <table class="table-imput">
        <thead>
            <tr>
                <th colspan="7" class="section-header" style="font-size: 9.5px; padding: 3px;">IMPUTATION COMPTABLE</th>
            </tr>
            <tr>
                <th colspan="4" style="width: 70%; background-color: #e5e0d8;">COMPTABILITE GENERALE</th>
                <th colspan="3" style="width: 30%; background-color: #e5e0d8;"></th>
            </tr>
            <tr>
                <th colspan="2" style="width: 35%;">Débit</th>
                <th colspan="2" style="width: 35%;">Crédit</th>
                <th style="width: 10%;">ART</th>
                <th style="width: 10%;">PARA</th>
                <th style="width: 10%;">LIGNE</th>
            </tr>
            <tr>
                <th style="width: 23%;">N° Compte</th>
                <th style="width: 12%;">Montant (Dh)</th>
                <th style="width: 23%;">N° Compte</th>
                <th style="width: 12%;">Montant (Dh)</th>
                <th colspan="3" style="background-color: #e5e0d8;"></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-size: 8.5px; padding: 4px 2px;">
                    <div style="font-family: monospace;">{{ $ribDebit }}</div>
                    <div style="font-size: 8px; margin-top: 2px;">{{ $agenceDebit }}</div>
                </td>
                <td style="vertical-align: middle;">-</td>
                <td style="vertical-align: middle; text-transform: uppercase;">TELEPAIEMENT</td>
                <td style="vertical-align: middle; font-size: 9.5px;">{{ number_format($montantOi, 2, ',', ' ') }}</td>
                <td style="vertical-align: middle;">{{ $ordonnancement->article ?: '415' }}</td>
                <td style="vertical-align: middle;">{{ $ordonnancement->paragraphe ?: '20' }}</td>
                <td style="vertical-align: middle;">{{ $ordonnancement->ligne ?: '14' }}</td>
            </tr>
            <tr>
                <td colspan="4" style="height: 45px; background-color: #ffffff;"></td>
                <td colspan="3" style="font-size: 8.5px; font-weight: normal; text-align: center; vertical-align: middle; padding: 4px 6px;">
                    {{ $ordonnancement->intitule_depense ?: 'Frais de voyage des agriculteurs et techniciens' }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- 7. VISA DU SOUS-ORDONNATEUR -->
    <table class="visa-table">
        <tr>
            <th>VISA DU SOUS-ORDONNATEUR</th>
        </tr>
        <tr>
            <td></td>
        </tr>
    </table>

</body>
</html>
