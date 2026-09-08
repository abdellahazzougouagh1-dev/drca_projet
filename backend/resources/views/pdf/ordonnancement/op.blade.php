<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordre de Paiement (OP) - {{ $ordre->num_ordre ?? $ordonnancement->num_op ?? 'OP' }}</title>
    <style>
        @page {
            margin: 16px 22px 16px 22px;
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
            height: 68px;
            width: auto;
            max-height: 75px;
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

        /* BUDGET BOX */
        .budget-box {
            width: 100%;
            margin-bottom: 6px;
        }
        .budget-table {
            width: 44%;
            margin-left: auto;
            border-collapse: collapse;
            font-size: 9.5px;
        }
        .budget-table td {
            border: 1.2px solid #000;
            padding: 2.5px 8px;
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
            margin: 4px 0 6px 0;
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
            margin-bottom: 6px;
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
            margin-bottom: 6px;
        }
        .table-section th, .table-section td {
            border: 1.2px solid #000;
            padding: 4px 6px;
            font-size: 9.5px;
        }
        .section-header {
            background-color: #ffffff;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            padding: 3px 0;
            text-transform: uppercase;
        }
        .cell-label {
            font-weight: bold;
            width: 18%;
            text-align: left;
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
            margin-bottom: 6px;
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
            padding: 2.5px 5px;
            font-size: 8.5px;
            vertical-align: middle;
        }

        /* SOMME A PAYER */
        .table-somme {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #000;
            margin-bottom: 6px;
        }
        .table-somme td {
            border: 1.2px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
        }

        /* IMPUTATION TABLE */
        .table-imput {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #000;
            margin-bottom: 6px;
            text-align: center;
        }
        .table-imput th {
            background-color: #ffffff;
            border: 1.2px solid #000;
            padding: 3px;
            font-size: 8.5px;
            font-weight: bold;
        }
        .table-imput td {
            border: 1.2px solid #000;
            padding: 3px;
            font-size: 9px;
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
            background-color: #ffffff;
            text-align: center;
            font-weight: bold;
            font-size: 9px;
            padding: 3px 0;
            border: 1.2px solid #000;
            width: 50%;
        }
        .visa-table td {
            height: 75px;
            border: 1.2px solid #000;
            vertical-align: top;
        }
    </style>
</head>
<body>

@php
    $logoOncaSrc   = file_exists(public_path('images/logo-onca.png'))   ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/logo-onca.png')))   : '';
    $sceauMarocSrc = file_exists(public_path('images/sceau-maroc.png')) ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) : '';

    $budgetType = $ordonnancement->budget_type ?: ($ordonnancement->marche?->type_budget ?: 'Investissement');
    $exercice   = $ordonnancement->exercice ?: date('Y');
    $exerciceOrigine = $ordonnancement->marche?->exercice ?: $exercice;
    $typeCredit = $ordonnancement->type_credit ?: 'C.Neufs';

    $numOpClean = $ordre?->num_ordre ?: ($ordonnancement->num_op ?: '38');
    $numOpVal   = preg_replace('/[^0-9]/', '', $numOpClean) ?: $numOpClean;
    $suffixeOp  = '/DRCA-RSK/' . $exercice;

    $dateOp = $ordre?->date_ordre 
        ? \Carbon\Carbon::parse($ordre->date_ordre)->format('d/m/Y')
        : ($ordonnancement->date_ordonnancement ? \Carbon\Carbon::parse($ordonnancement->date_ordonnancement)->format('d/m/Y') : date('d/m/Y'));

    $beneficiaireNom = $ordre?->beneficiaire ?: ($ordonnancement->fournisseur?->raison_sociale ?: $ordonnancement->beneficiaire_nom);
    $beneficiaireAdresse = $ordonnancement->fournisseur?->adresse ?: ($ordonnancement->marche?->fournisseur?->adresse ?: '');
    $beneficiaireVille = $ordonnancement->fournisseur?->ville ?: ($ordonnancement->marche?->fournisseur?->ville ?: '');

    $rib = $ordre?->rib_compte ?: ($ordonnancement->fournisseur?->rib ?: ($ordonnancement->marche?->rib ?: ''));
    $banque = $ordonnancement->fournisseur?->banque ?: ($ordonnancement->marche?->banque ?: '');

    $objet = $ordonnancement->intitule_depense ?: ($ordonnancement->liquidation?->objet_liquidation ?: ($ordonnancement->marche?->objet_marche ?: $ordonnancement->consultation?->objet_consultation ?: ''));
    $reference = $ordonnancement->reference ?: ($ordonnancement->marche?->num_marche ?: ($ordonnancement->consultation?->numero_consultation ?: ''));

    $modePaiement = strtoupper($ordre?->mode_paiement ?: ($ordonnancement->mode_paiement ?: 'VIREMENT'));

    $montantPaiement = (float)($montant ?: ($ordre?->montant ?: ($ordonnancement->net_a_payer ?: $ordonnancement->montant_brut)));
    $montantEngagement = (float)($ordonnancement->engagement_montant ?: ($ordonnancement->montant_brut ?: $montantPaiement));

    $art = $ordonnancement->article ?: ($ordonnancement->notificationLigne?->article ?: ($ordonnancement->marche?->article_budget ?: '415'));
    $par = $ordonnancement->paragraphe ?: ($ordonnancement->notificationLigne?->paragraphe ?: ($ordonnancement->marche?->paragraphe_budget ?: '20'));
    $lig = $ordonnancement->ligne ?: ($ordonnancement->notificationLigne?->ligne_budgetaire ?: ($ordonnancement->marche?->ligne_budget ?: '13'));
    $intituleRubrique = $ordonnancement->intitule_rubrique ?: ($ordonnancement->notificationLigne?->intitule ?: ($ordonnancement->marche?->intitule_budget ?: 'Essais de démonstration et achat des intrants pour FFS'));

    $liq = $ordonnancement->liquidation;
    $dateReception = $liq?->date_reception ? \Carbon\Carbon::parse($liq->date_reception)->format('d/m/Y') : ($liq?->date_constat ? \Carbon\Carbon::parse($liq->date_constat)->format('d/m/Y') : $dateOp);
    $factureRef = $liq?->num_facture 
        ? 'Facture N°' . $liq->num_facture . ($liq->date_facture ? ' du ' . \Carbon\Carbon::parse($liq->date_facture)->format('d/m/Y') : '')
        : ($liq?->num_decompte ? 'Décompte N°' . $liq->num_decompte . ($liq->date_decompte ? ' du ' . \Carbon\Carbon::parse($liq->date_decompte)->format('d/m/Y') : '') : 'Facture N°12 du ' . $dateOp);

    $feRef = $ordonnancement->marche?->num_engagement 
        ? 'Fiche d\'engagement N°' . $ordonnancement->marche->num_engagement . ($ordonnancement->marche->date_engagement ? ' Du ' . \Carbon\Carbon::parse($ordonnancement->marche->date_engagement)->format('d/m/Y') : '')
        : 'Fiche d\'engagement N°12/' . $exercice . '/FE/DRCA-RSK Du ' . $dateOp;

    $docRefMarche = $reference ? $reference . ' du ' . $dateOp : 'Bon de commande N°01/' . $exercice . '/DRCA-RSK du ' . $dateOp;
    $oiRef = $ordonnancement->num_oi ? ('OI N°' . $ordonnancement->num_oi . '/DRCA-RSK/' . $exercice) : ('OI N°28/DRCA-RSK/' . $exercice);
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

    <!-- 2. TABLEAU BUDGET -->
    <div class="budget-box">
        <table class="budget-table">
            <tr>
                <td class="lbl">Budget</td>
                <td class="val">{{ $budgetType }}</td>
            </tr>
            <tr>
                <td class="lbl">Crédit</td>
                <td class="val">{{ $typeCredit }}</td>
            </tr>
            <tr>
                <td class="lbl">Exercice</td>
                <td class="val">{{ $exercice }}</td>
            </tr>
            <tr>
                <td class="lbl">Exercice origine</td>
                <td class="val">{{ $exerciceOrigine }}</td>
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

    <!-- 5. RENSEIGNEMENTS SUR LA DÉPENSE -->
    <table class="table-section">
        <tr>
            <th colspan="2" class="section-header">RENSEIGNEMENTS SUR LA DEPENSE</th>
        </tr>
        <tr>
            <td class="cell-label">BENEFICIAIRE</td>
            <td class="cell-value">
                <div style="font-size: 10px; text-transform: uppercase;">{{ $beneficiaireNom }}</div>
                @if($beneficiaireAdresse || $beneficiaireVille)
                    <div style="font-weight: normal; font-size: 8.5px; margin-top: 1px;">
                        {{ $beneficiaireAdresse }}{{ $beneficiaireVille ? ', ' . $beneficiaireVille : '' }}
                    </div>
                @endif
            </td>
        </tr>
        <tr>
            <td class="cell-label">RIB N°</td>
            <td class="cell-value" style="font-family: monospace; font-size: 10px; letter-spacing: 0.5px;">
                {{ $rib ?: '225 330 000 755 918 651 021 026' }} @if($banque) <span style="font-weight: normal; font-family: Arial; font-size: 8.5px;">- {{ $banque }}</span> @endif
            </td>
        </tr>
        <tr>
            <td class="cell-label">OBJET</td>
            <td class="cell-value" style="font-size: 9px; font-weight: bold; text-align: justify;">
                {{ $objet }}
            </td>
        </tr>
        <tr>
            <td class="cell-label">Référence</td>
            <td class="cell-value" style="font-size: 9.5px;">
                {{ $reference }}
            </td>
        </tr>
    </table>

    <!-- 6. PIECES JOINTES & MONTANT & MODE DE PAIEMENT -->
    <table class="table-pj">
        <thead>
            <tr>
                <th style="width: 18%;">MONTANT (DH)</th>
                <th style="width: 62%;">PIECES JOINTES:</th>
                <th style="width: 20%;">MODE DE PAIEMENT</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td rowspan="10" style="text-align: center; font-size: 12px; font-weight: bold; vertical-align: middle;">
                    {{ number_format($montantPaiement, 2, ',', ' ') }}
                </td>
                <td><span style="font-weight: bold; margin-right: 6px;">1</span> Avis d'achat</td>
                <td rowspan="10" style="text-align: center; font-size: 10.5px; font-weight: bold; vertical-align: middle; text-transform: uppercase;">
                    {{ $modePaiement }}
                </td>
            </tr>
            <tr>
                <td><span style="font-weight: bold; margin-right: 6px;">2</span> PV D'examen des devis</td>
            </tr>
            <tr>
                <td><span style="font-weight: bold; margin-right: 6px;">3</span> Lettre de confirmation</td>
            </tr>
            <tr>
                <td><span style="font-weight: bold; margin-right: 6px;">4</span> Devis</td>
            </tr>
            <tr>
                <td><span style="font-weight: bold; margin-right: 6px;">5</span> Bon de commande dématérialisé</td>
            </tr>
            <tr>
                <td><span style="font-weight: bold; margin-right: 6px;">6</span> {{ $feRef }}</td>
            </tr>
            <tr>
                <td><span style="font-weight: bold; margin-right: 6px;">7</span> {{ $docRefMarche }}</td>
            </tr>
            <tr>
                <td><span style="font-weight: bold; margin-right: 6px;">8</span> PV de réception définitive du {{ $dateReception }}</td>
            </tr>
            <tr>
                <td><span style="font-weight: bold; margin-right: 6px;">9</span> {{ $factureRef }}</td>
            </tr>
            <tr>
                <td><span style="font-weight: bold; margin-right: 6px;">10</span> {{ $oiRef }}</td>
            </tr>
        </tbody>
    </table>

    <!-- 7. SOMME A PAYER -->
    <table class="table-somme">
        <tr>
            <td style="width: 18%; font-weight: bold; text-align: center; font-size: 9px; line-height: 1.1;">
                SOMME A PAYER<br><span style="font-weight: normal; font-size: 8.5px;">(en lettres)</span>
            </td>
            <td style="width: 82%; font-weight: bold; font-size: 9.5px; padding-left: 8px;">
                # {{ \App\Helpers\NumberToWordsHelper::toFrenchMoneyWords($montantPaiement) }} #
            </td>
        </tr>
    </table>

    <!-- 8. IMPUTATION COMPTABLE -->
    <table class="table-imput">
        <thead>
            <tr>
                <th colspan="6" style="text-align: center; font-size: 9.5px; padding: 2px;">IMPUTATION COMPTABLE</th>
            </tr>
            <tr>
                <th style="width: 12%;">Chap</th>
                <th style="width: 12%;">Art</th>
                <th style="width: 12%;">Parag</th>
                <th style="width: 12%;">Ligne</th>
                <th style="width: 26%;">ENGAGEMENT (DH)</th>
                <th style="width: 26%;">PAIEMENT (DH)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>-</td>
                <td>{{ $art }}</td>
                <td>{{ $par }}</td>
                <td>{{ $lig }}</td>
                <td>{{ number_format($montantEngagement, 2, ',', ' ') }}</td>
                <td>{{ number_format($montantPaiement, 2, ',', ' ') }}</td>
            </tr>
            <tr>
                <th colspan="4" style="text-align: center; font-size: 8.5px;">INTITULE DE LA RUBRIQUE</th>
                <th colspan="2" style="text-align: center; font-size: 8.5px;">PRETSATION DE MEME NATURE</th>
            </tr>
            <tr>
                <td colspan="4" style="text-align: left; padding-left: 6px; font-size: 8.5px; font-weight: normal;">
                    {{ $intituleRubrique }}
                </td>
                <td colspan="2" style="text-align: center;">
                    -
                </td>
            </tr>
        </tbody>
    </table>

    <!-- 9. SIGNATURES -->
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
