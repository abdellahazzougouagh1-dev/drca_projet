<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordre d'Imputation</title>
    <style>
        @page { margin: 18px 34px 96px 34px; size: A4 portrait; }
        * { box-sizing: border-box; }
        body { font-family: "Times New Roman", Times, serif; font-size: 11px; line-height: 1.38; margin: 0; padding: 0; color: #000; }
        td, th { font-family: "Times New Roman", Times, serif; }

        /* ── Header ── */
        .hdr { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .hdr td { vertical-align: middle; padding: 0; border: none; }
        .hdr-center { text-align: center; font-size: 13.5px; font-weight: bold; line-height: 1.35; padding: 0 8px; }
        .hdr-divider { border-bottom: 2px solid #000080; margin: 4px 0 10px 0; }

        /* ── Budget box ── */
        .budget-box { width: 245px; border-collapse: collapse; margin-left: auto; margin-bottom: 8px; border: 1.5px solid #000; }
        .budget-box td { border: 1px solid #000; padding: 4.5px 8px; font-size: 10.5px; font-weight: bold; }
        .budget-box .val { text-align: center; }

        /* ── N° box (haut droite) ── */
        .num-box { width: 100%; border-collapse: collapse; margin-bottom: 10px; border: 1.5px solid #000; }
        .num-box td { border: 1px solid #000; padding: 5px 8px; font-size: 10.5px; }

        /* ── Titre bannière AOO ── */
        .title-banner {
            background-color: #e5e0d8;
            border: 1.5px solid #000;
            text-align: center;
            padding: 6px 0;
            margin: 4px 0 10px 0;
            font-size: 14.5px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* ── Tableaux données ── */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; border: 1.5px solid #000; }
        .data-table th { background: #e5e0d8; font-weight: bold; text-align: center; padding: 6.5px; border: 1px solid #000; font-size: 11px; letter-spacing: 0.3px; text-transform: uppercase; }
        .data-table td { border: 1px solid #000; padding: 7.5px 9px; font-size: 11px; vertical-align: middle; }
        .label { font-weight: bold; width: 32%; background: #f5f5f5; }
        .value { font-weight: bold; }

        /* ── Imputation comptable ── */
        .imput-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; border: 1.5px solid #000; }
        .imput-table th { background: #e5e0d8; font-weight: bold; text-align: center; padding: 5.5px; border: 1px solid #000; font-size: 10px; }
        .imput-table td { border: 1px solid #000; padding: 6px 6px; font-size: 10px; text-align: center; vertical-align: middle; }
        .imput-table .col-label { text-align: left; font-weight: bold; background: #f5f5f5; }
        .imput-table .col-num { font-family: "DejaVu Sans Mono", monospace; }

        /* ── Visa ── */
        .visa-box { width: 100%; border-collapse: collapse; border: 1.5px solid #000; page-break-inside: avoid; }
        .visa-box th { background: #e5e0d8; font-weight: bold; text-align: center; padding: 6px; border: 1px solid #000; font-size: 11px; letter-spacing: 0.3px; text-transform: uppercase; }
        .visa-box td { height: 110px; border: 1px solid #000; }
    </style>
    @include('documents.partials.bc_typography')
</head>
<body>

@php
    $logoOncaSrc   = file_exists(public_path('images/logo-onca.png'))   ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/logo-onca.png')))   : '';
    $sceauMarocSrc = file_exists(public_path('images/sceau-maroc.png')) ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) : '';

    $isConvention = (!empty($consultation) && (in_array($consultation->mode_engagement, ['Convention', 'Contrat']) || str_starts_with(strtoupper($consultation->numero_consultation ?? ''), 'CONV')))
        || str_starts_with(strtoupper((string)data_get($documentData, 'numero_consultation', '')), 'CONV');
    $typeBudget  = data_get($documentData, 'type_budget') ?: ($consultation->type_budget ?? 'Investissement');
    $isContrat   = $isConvention && strtolower((string)$typeBudget) === 'fonctionnement';
    $labelEngagement = $isConvention ? ($isContrat ? 'Contrat' : 'Convention') : 'Bon de commande';
    $exercice    = $consultation->annee ?? date('Y');
    $anneeOrig   = data_get($documentData, 'annee_origine', $exercice);
    $creditType  = data_get($documentData, 'credit_type', 'Crédit Consolidés');

    $numOI       = data_get($documentData, 'numero_oi', '');
    $dateDoc     = data_get($documentData, 'date_document')
                   ? \Carbon\Carbon::parse(data_get($documentData, 'date_document'))->format('d/m/Y')
                   : '';

    $beneficiaire     = data_get($documentData, 'societe') ?: ($doc['societe'] ?? ($doc['attributaire'] ?? ''));
    $objet            = data_get($documentData, 'objet') ?: ($consultation->objet_consultation ?? '');
    $formeEngagement  = data_get($documentData, 'forme_engagement', $labelEngagement);
    $cleanNumConv     = trim(preg_replace('/\bCONV\s*[-_]?\s*/i', '', (string)($doc['numero_consultation'] ?? ($consultation->numero_consultation ?? ''))));
    $cleanNumConv     = trim(preg_replace('/^[-_\s]+|[-_\s]+$/', '', $cleanNumConv));
    $defaultRef       = $isConvention ? (($isContrat ? 'Contrat N° ' : 'Convention N° ') . $cleanNumConv) : ($doc['numero_bc'] ?? '');
    $referenceBc      = data_get($documentData, 'reference_bc') ?: $defaultRef;
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

    $suffixe = '/DRCA-RSK/' . $exercice;
@endphp

@include('documents.partials.bc_footer')

<!-- HEADER -->
<table class="hdr">
    <tr>
        <td style="width:24%; text-align:left;">
            @if($logoOncaSrc)<img src="{{ $logoOncaSrc }}" style="height:68px; width:auto;">@endif
        </td>
        <td style="width:52%;" class="hdr-center">
            Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
        </td>
        <td style="width:24%; text-align:right;">
            @if($sceauMarocSrc)<img src="{{ $sceauMarocSrc }}" style="height:68px; width:auto;">@endif
        </td>
    </tr>
</table>
<div class="hdr-divider"></div>

<!-- BUDGET BOX -->
<table class="budget-box">
    <tr><td style="background:#f5f5f5;">BUDGET</td><td class="val">{{ $typeBudget }}</td></tr>
    <tr><td style="background:#f5f5f5;">EXERCICE :</td><td class="val">{{ $exercice }}</td></tr>
    <tr><td style="background:#f5f5f5;">ANNÉE D'ORIGINE :</td><td class="val">{{ $anneeOrig }}</td></tr>
</table>

<!-- TITRE -->
<div class="title-banner">ORDRE D'IMPUTATION (OI)</div>

<!-- N° + DATE -->
<table class="num-box" style="width:55%; margin-left:auto; margin-bottom:12px;">
    <tr>
        <td style="font-weight:bold; width:20%; background:#f5f5f5;">N° :</td>
        <td style="width:30%; text-align:center; font-weight:bold; font-size:13px;">{{ $numOI }}</td>
        <td style="font-weight:bold; width:50%; text-align:center;">{{ $suffixe }}</td>
    </tr>
    <tr>
        <td style="font-weight:bold; background:#f5f5f5;">DATE :</td>
        <td colspan="2" style="text-align:center; font-weight:bold;">{{ $dateDoc }}</td>
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
        <td class="label">Référence (N°)</td>
        <td class="value" style="font-weight:bold;">{{ $referenceBc }}</td>
    </tr>
    <tr>
        <td class="label">Montant (DH)</td>
        <td class="value" style="font-weight:bold; font-size:11px;">{{ number_format($montant, 2, ',', ' ') }}</td>
    </tr>
    <tr>
        <td class="label">Montant en lettres :</td>
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
        <th colspan="3" style="width:40%;">COMPTABILITÉ GÉNÉRALE</th>
        <th colspan="5" style="width:60%;"> </th>
    </tr>
    <tr>
        <th style="width:18%;">Débit</th>
        <th colspan="2" style="width:22%;"></th>
        <th style="width:18%;">Crédit</th>
        <th style="width:15%;"></th>
        <th style="width:9%;">ART</th>
        <th style="width:9%;">PARA</th>
        <th style="width:9%;">LIGNE</th>
    </tr>
    <tr>
        <th style="font-size:9px;">N° Compte</th>
        <th style="font-size:9px;">Montant (Dh)</th>
        <th style="font-size:9px;"></th>
        <th style="font-size:9px;">N° Compte</th>
        <th style="font-size:9px;">Montant (Dh)</th>
        <th></th><th></th><th></th>
    </tr>
    <tr>
        <td class="col-num" style="font-size:9px;">{{ $compteDebit }}</td>
        <td style="text-align:center;">-</td>
        <td style="text-align:center;"></td>
        <td class="col-num" style="font-size:9px;">{{ $compteCredit }}</td>
        <td style="text-align:right; font-weight:bold;">{{ number_format($montant, 2, ',', ' ') }}</td>
        <td style="font-weight:bold;">{{ $art }}</td>
        <td style="font-weight:bold;">{{ $par }}</td>
        <td style="font-weight:bold;">{{ $lig }}</td>
    </tr>
    <tr>
        <td style="text-align:center; font-weight:bold; font-size:9.5px; height:36px;">{{ $libelleDebit }}</td>
        <td style="text-align:center;">-</td>
        <td></td>
        <td></td>
        <td></td>
        <td colspan="3" style="font-size:9.5px; text-align:center;">{{ $intituleRubrique }}</td>
    </tr>
</table>

<!-- VISA -->
<table class="visa-box">
    <tr><th colspan="1">VISA DU SOUS-ORDONNATEUR</th></tr>
    <tr><td style="height:110px;"></td></tr>
</table>

</body>
</html>
