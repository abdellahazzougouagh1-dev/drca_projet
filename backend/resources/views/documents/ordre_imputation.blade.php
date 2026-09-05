<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordre d'Imputation</title>
    <style>
        @page { margin: 8mm 10mm 8mm 10mm; size: A4 portrait; }
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 10px; line-height: 1.25; margin: 0; padding: 0; color: #000; }

        /* ── Header ── */
        .hdr { width: 100%; border-collapse: collapse; margin-bottom: 3px; }
        .hdr td { vertical-align: middle; padding: 0; }
        .hdr-center { text-align: center; font-size: 11px; font-weight: bold; line-height: 1.4; }
        .hdr-divider { border-bottom: 1.5px solid #000; margin: 4px 0 6px 0; }

        /* ── Budget box ── */
        .budget-box { width: 200px; border-collapse: collapse; margin-left: auto; margin-bottom: 5px; border: 1.5px solid #000; }
        .budget-box td { border: 1px solid #000; padding: 3px 7px; font-size: 9.5px; font-weight: bold; }
        .budget-box .val { text-align: center; }

        /* ── N° box (haut droite) ── */
        .num-box { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .num-box td { border: 1px solid #000; padding: 4px 8px; font-size: 10px; }

        /* ── Titre ── */
        .main-title {
            text-align: center; font-size: 15px; font-weight: bold;
            letter-spacing: 0.5px; margin: 6px 0 10px 0;
            text-transform: uppercase; text-decoration: underline;
        }

        /* ── Tableaux données ── */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; border: 1.5px solid #000; }
        .data-table th { background: #d9d9d9; font-weight: bold; text-align: center; padding: 5px; border: 1px solid #000; font-size: 10px; letter-spacing: 0.3px; }
        .data-table td { border: 1px solid #000; padding: 5px 7px; font-size: 10px; vertical-align: top; }
        .label { font-weight: bold; width: 32%; }
        .value { }

        /* ── Imputation comptable ── */
        .imput-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; border: 1.5px solid #000; }
        .imput-table th { background: #d9d9d9; font-weight: bold; text-align: center; padding: 5px; border: 1px solid #000; font-size: 9.5px; }
        .imput-table td { border: 1px solid #000; padding: 5px 6px; font-size: 9.5px; text-align: center; vertical-align: middle; }
        .imput-table .col-label { text-align: left; font-weight: bold; background: #f5f5f5; }
        .imput-table .col-num { font-family: "DejaVu Sans Mono", monospace; }

        /* ── Visa ── */
        .visa-box { width: 100%; border-collapse: collapse; border: 1.5px solid #000; }
        .visa-box th { background: #d9d9d9; font-weight: bold; text-align: center; padding: 5px; border: 1px solid #000; font-size: 11px; letter-spacing: 0.3px; }
        .visa-box td { height: 70px; border: 1px solid #000; }
    </style>
</head>
<body>

@php
    $logoOncaSrc   = file_exists(public_path('images/logo-onca.png'))   ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/logo-onca.png')))   : '';
    $sceauMarocSrc = file_exists(public_path('images/sceau-maroc.png')) ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) : '';

    $typeBudget  = $consultation->type_budget ?? 'Investissement';
    $exercice    = $consultation->annee ?? date('Y');
    $anneeOrig   = data_get($documentData, 'annee_origine', $exercice);
    $creditType  = data_get($documentData, 'credit_type', 'Crédit Consolidés');

    $numOI       = data_get($documentData, 'numero_oi', '');
    $dateDoc     = data_get($documentData, 'date_document')
                   ? \Carbon\Carbon::parse(data_get($documentData, 'date_document'))->format('d/m/Y')
                   : '';

    $beneficiaire     = data_get($documentData, 'societe') ?: ($doc['societe'] ?? '');
    $objet            = data_get($documentData, 'objet') ?: ($consultation->objet_consultation ?? '');
    $formeEngagement  = data_get($documentData, 'forme_engagement', 'Bon de commande');
    $referenceBc      = data_get($documentData, 'reference_bc') ?: ($doc['numero_bc'] ?? '');
    $montant          = (float)(data_get($documentData, 'montant_oi') ?: $doc['total_ttc'] ?? 0);
    $montantLettres   = \App\Helpers\NumberToWordsHelper::toFrenchMoneyWords($montant);
    $modeReglement    = data_get($documentData, 'mode_reglement', 'Virement');

    $compteDebit      = data_get($documentData, 'compte_debit', '310330100602470154760181');
    $libelleDebit     = data_get($documentData, 'libelle_debit', 'T.P KENITRA');
    $compteCredit     = data_get($documentData, 'compte_credit') ?: ($doc['rib'] ?? '');
    $art              = data_get($documentData, 'art') ?: ($consultation->budget->art ?? ($doc['art'] ?? ''));
    $par              = data_get($documentData, 'par') ?: ($consultation->budget->par ?? ($doc['par'] ?? ''));
    $lig              = data_get($documentData, 'lig') ?: ($consultation->budget->lig ?? ($doc['lig'] ?? ''));
    $intituleRubrique = data_get($documentData, 'intitule_rubrique') ?: ($consultation->intitule ?? $consultation->objet_consultation ?? '');

    $suffixe = '/INV/DRCA-RSK/' . $exercice;
@endphp

<!-- HEADER -->
<table class="hdr">
    <tr>
        <td style="width:22%; text-align:left;">
            @if($logoOncaSrc)<img src="{{ $logoOncaSrc }}" style="height:55px; width:auto;">@endif
        </td>
        <td style="width:56%;" class="hdr-center">
            Direction régionale du Conseil agricole<br>
            <span style="font-size:12px;">Rabat-Salé-Kénitra</span>
        </td>
        <td style="width:22%; text-align:right;">
            @if($sceauMarocSrc)<img src="{{ $sceauMarocSrc }}" style="height:55px; width:auto;">@endif
        </td>
    </tr>
</table>
<div class="hdr-divider"></div>

<!-- BUDGET BOX -->
<table class="budget-box">
    <tr><td>BUDGET</td><td class="val">{{ $typeBudget }}</td></tr>
    <tr><td>EXERCICE :</td><td class="val">{{ $exercice }}</td></tr>
    <tr><td>ANNEE D'ORIGINE :</td><td class="val">{{ $anneeOrig }}</td></tr>
</table>

<!-- TITRE -->
<div class="main-title">ORDRE D'IMPUTATION (OI)</div>

<!-- N° + DATE -->
<table class="num-box" style="width:55%; margin-left:auto; margin-bottom:10px;">
    <tr>
        <td style="font-weight:bold; width:20%;">N°:</td>
        <td style="width:25%; text-align:center; font-weight:bold; font-size:13px;">{{ $numOI }}</td>
        <td style="font-weight:bold; width:20%; text-align:center;">{{ $suffixe }}</td>
    </tr>
    <tr>
        <td style="font-weight:bold;">DATE</td>
        <td colspan="2" style="text-align:center;">{{ $dateDoc }}</td>
    </tr>
</table>

<!-- RENSEIGNEMENTS SUR L'IMPUTATION -->
<table class="data-table">
    <tr><th colspan="2" style="font-size:11px; padding:6px; letter-spacing:0.5px;">RENSEIGNEMENTS SUR L'IMPUTATION</th></tr>
    <tr>
        <td class="label">Bénéficiaire</td>
        <td class="value" style="text-transform:uppercase; font-weight:bold;">{{ $beneficiaire }}</td>
    </tr>
    <tr>
        <td class="label">Objet</td>
        <td class="value">{{ $objet }}</td>
    </tr>
    <tr>
        <td class="label">Forme d'engagement</td>
        <td class="value">{{ $formeEngagement }}</td>
    </tr>
    <tr>
        <td class="label">Reférence (N°)</td>
        <td class="value" style="font-weight:bold;">{{ $referenceBc }}</td>
    </tr>
    <tr>
        <td class="label">Montant (Dh)</td>
        <td class="value" style="font-weight:bold; font-size:11px;">{{ number_format($montant, 2, ',', ' ') }}</td>
    </tr>
    <tr>
        <td class="label">Montant en lettre</td>
        <td class="value" style="font-style:italic;">{{ $montantLettres }}</td>
    </tr>
    <tr>
        <td class="label">Mode de règlement</td>
        <td class="value">{{ $modeReglement }}</td>
    </tr>
</table>

<!-- IMPUTATION COMPTABLE -->
<table class="imput-table">
    <tr><th colspan="8" style="font-size:11px; padding:6px;">IMPUTATION COMPTABLE</th></tr>
    <tr>
        <th colspan="3" style="width:40%;">COMPTABILITE GENERALE</th>
        <th colspan="5" style="width:60%;"> </th>
    </tr>
    <tr>
        <th style="width:18%;">Débit</th>
        <th colspan="2" style="width:22%;"></th>
        <th style="width:18%;">Crédit</th>
        <th style="width:15%;"></th>
        <th style="width:9%;">ART</th>
        <th style="width:9%;">PAR A</th>
        <th style="width:9%;">LIGNE</th>
    </tr>
    <tr>
        <th style="font-size:8.5px;">N° Compte</th>
        <th style="font-size:8.5px;">Montant (Dh)</th>
        <th style="font-size:8.5px;"></th>
        <th style="font-size:8.5px;">N° Compte</th>
        <th style="font-size:8.5px;">Montant (Dh)</th>
        <th></th><th></th><th></th>
    </tr>
    <tr>
        <td class="col-num" style="font-size:8.5px;">{{ $compteDebit }}</td>
        <td style="text-align:center;">-</td>
        <td style="text-align:center;"></td>
        <td class="col-num" style="font-size:8.5px;">{{ $compteCredit }}</td>
        <td style="text-align:right; font-weight:bold;">{{ number_format($montant, 2, ',', ' ') }}</td>
        <td style="font-weight:bold;">{{ $art }}</td>
        <td style="font-weight:bold;">{{ $par }}</td>
        <td style="font-weight:bold;">{{ $lig }}</td>
    </tr>
    <tr>
        <td style="text-align:center; font-weight:bold; font-size:9px;">{{ $libelleDebit }}</td>
        <td style="text-align:center;">-</td>
        <td></td>
        <td></td>
        <td></td>
        <td colspan="3" style="font-size:8.5px; text-align:center;">{{ $intituleRubrique }}</td>
    </tr>
</table>

<!-- VISA -->
<table class="visa-box">
    <tr><th colspan="1" style="font-size:11px; padding:6px; letter-spacing:0.5px;">VISA DU SOUS-ORDONNATEUR</th></tr>
    <tr><td style="height:80px;"></td></tr>
</table>

</body>
</html>
