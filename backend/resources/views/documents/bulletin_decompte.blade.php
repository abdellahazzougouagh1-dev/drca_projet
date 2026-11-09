<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de Décompte</title>
    <style>
        @page { margin: 18px 34px 96px 34px; size: A4 portrait; }
        * { box-sizing: border-box; }
        body { font-family: "Times New Roman", Times, serif; font-size: 12px; line-height: 1.42; margin: 0; padding: 0; color: #000; }
        td, th { font-family: "Times New Roman", Times, serif; }

        .hdr { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .hdr td { vertical-align: middle; padding: 0; border: none; }
        .hdr-center { text-align: center; font-size: 13.5px; font-weight: bold; line-height: 1.35; padding: 0 8px; }
        .hdr-divider { border-bottom: 2px solid #000080; margin: 4px 0 14px 0; }

        .title-banner {
            background-color: #e5e0d8;
            border: 2px solid #000;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 0.6px;
            padding: 8px 14px;
            margin: 4px auto 18px auto;
            width: 72%;
            text-transform: uppercase;
        }

        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; border: 1.5px solid #000; }
        .data-table th { background: #e5e0d8; font-weight: bold; text-align: center; padding: 7px 8px; border: 1px solid #000; font-size: 12px; letter-spacing: 0.4px; text-transform: uppercase; }
        .data-table td { border: 1px solid #000; padding: 9.5px 10px; font-size: 11.5px; vertical-align: middle; }
        .label { font-weight: bold; width: 35%; background: #f5f5f5; }
        .value { }

        .totals-table { width: 64%; border-collapse: collapse; margin: 0 0 18px auto; border: 2px solid #000; }
        .totals-table td { border: 1px solid #000; padding: 8px 10px; font-size: 11.5px; }
        .totals-table .tlabel { font-weight: bold; width: 60%; background: #f5f5f5; }
        .totals-table .tvalue { text-align: right; font-weight: bold; font-size: 12px; }
        .totals-table .net-row td { background: #e5e0d8; font-size: 12.5px; font-weight: bold; }

        .sig-table { width: 100%; border-collapse: collapse; margin-top: 6px; border: 1.5px solid #000; page-break-inside: avoid; }
        .sig-table th { background: #e5e0d8; font-weight: bold; text-align: center; padding: 7px; border: 1px solid #000; font-size: 11.5px; }
        .sig-table td { height: 145px; border: 1px solid #000; vertical-align: top; }
    </style>
    @include('documents.partials.bc_typography')
</head>
<body>

@php
    $logoOncaSrc   = file_exists(public_path('images/logo-onca.png'))   ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/logo-onca.png')))   : '';
    $sceauMarocSrc = file_exists(public_path('images/sceau-maroc.png')) ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) : '';

    $isConvention = (!empty($consultation) && (in_array($consultation->mode_engagement, ['Convention', 'Contrat']) || str_starts_with(strtoupper($consultation->numero_consultation ?? ''), 'CONV')))
        || str_starts_with(strtoupper((string)data_get($documentData, 'numero_consultation', '')), 'CONV');
    $exercice     = $consultation->annee ?? date('Y');
    $typeBudget   = data_get($documentData, 'type_budget') ?: ($consultation->type_budget ?? 'Investissement');
    $isContrat    = $isConvention && strtolower((string)$typeBudget) === 'fonctionnement';

    $dateDoc      = data_get($documentData, 'date_document')
                    ? \Carbon\Carbon::parse(data_get($documentData, 'date_document'))->format('d/m/Y')
                    : date('d/m/Y');

    $cleanNumConv = trim(preg_replace('/\bCONV\s*[-_]?\s*/i', '', (string)($doc['numero_consultation'] ?? ($consultation->numero_consultation ?? ''))));
    $cleanNumConv = trim(preg_replace('/^[-_\s]+|[-_\s]+$/', '', $cleanNumConv));
    $numeroBc     = $isConvention 
        ? $cleanNumConv
        : (data_get($documentData, 'numero_bc') ?: ($consultation->numero_bc ?? ($doc['numero_bc'] ?? '')));
    $labelEngagement = $isConvention ? ($isContrat ? 'Contrat N°' : 'Convention N°') : 'Bon de commande N°';
    $beneficiaire = data_get($documentData, 'societe') ?: ($doc['societe'] ?? ($doc['attributaire'] ?? ''));
    $objet        = data_get($documentData, 'objet') ?: ($consultation->objet_consultation ?? '');

    $numFacture   = data_get($documentData, 'numero_facture', '');
    $dateFacture  = data_get($documentData, 'date_facture')
                    ? \Carbon\Carbon::parse(data_get($documentData, 'date_facture'))->format('d/m/Y')
                    : '';

    $montantFact  = (float)(data_get($documentData, 'montant_facture') ?: $doc['total_ttc'] ?? 0);
    $montantRet   = (float)(data_get($documentData, 'montant_retenu') ?: 0);
    $montantNet   = (float)(data_get($documentData, 'montant_net') ?: ($montantFact - $montantRet));
    $netLettres   = \App\Helpers\NumberToWordsHelper::toFrenchMoneyWords($montantNet);

    $art          = data_get($documentData, 'art') ?: ($consultation->budget->art ?? '');
    $par          = data_get($documentData, 'par') ?: ($consultation->budget->par ?? '');
    $lig          = data_get($documentData, 'lig') ?: ($consultation->budget->lig ?? '');
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

<!-- TITRE -->
<div class="title-banner">BULLETIN DE DÉCOMPTE</div>

<!-- INFORMATIONS GÉNÉRALES -->
<table class="data-table">
    <tr><th colspan="2">RENSEIGNEMENTS GÉNÉRAUX</th></tr>
    <tr>
        <td class="label">{{ $labelEngagement }}</td>
        <td class="value" style="font-weight:bold;">{{ $numeroBc }}</td>
    </tr>
    <tr>
        <td class="label">Date du bulletin</td>
        <td class="value">{{ $dateDoc }}</td>
    </tr>
    <tr>
        <td class="label">Bénéficiaire</td>
        <td class="value" style="font-weight:bold; text-transform:uppercase;">{{ $beneficiaire }}</td>
    </tr>
    <tr>
        <td class="label">Objet</td>
        <td class="value">{{ $objet }}</td>
    </tr>
    <tr>
        <td class="label">N° Facture</td>
        <td class="value" style="font-weight:bold;">{{ $numFacture }}</td>
    </tr>
    <tr>
        <td class="label">Date de la facture</td>
        <td class="value">{{ $dateFacture }}</td>
    </tr>
    <tr>
        <td class="label">Imputation budgétaire</td>
        <td class="value" style="font-weight:bold;">
            ART : {{ $art }} &nbsp;&nbsp; / &nbsp;&nbsp; PAR : {{ $par }} &nbsp;&nbsp; / &nbsp;&nbsp; LIG : {{ $lig }}
        </td>
    </tr>
</table>

<!-- DÉCOMPTE FINANCIER -->
<table class="totals-table">
    <tr>
        <td class="tlabel">Montant de la facture TTC (DH)</td>
        <td class="tvalue">{{ number_format($montantFact, 2, ',', ' ') }}</td>
    </tr>
    <tr>
        <td class="tlabel">Retenue de garantie (DH)</td>
        <td class="tvalue">{{ number_format($montantRet, 2, ',', ' ') }}</td>
    </tr>
    <tr class="net-row">
        <td class="tlabel">NET À PAYER (DH)</td>
        <td class="tvalue">{{ number_format($montantNet, 2, ',', ' ') }}</td>
    </tr>
    <tr>
        <td class="tlabel" style="background:#fff;">En lettres :</td>
        <td class="tvalue" style="background:#fff; font-style:italic; font-weight:normal; text-align:left; font-size:10.5px;">{{ $netLettres }}</td>
    </tr>
</table>

<!-- SIGNATURES -->
<table class="sig-table">
    <tr>
        <th style="width:50%;">LE SOUS-ORDONNATEUR</th>
        <th style="width:50%;">LE BÉNÉFICIAIRE</th>
    </tr>
    <tr>
        <td style="height:145px;"></td>
        <td style="height:145px;"></td>
    </tr>
</table>

</body>
</html>
