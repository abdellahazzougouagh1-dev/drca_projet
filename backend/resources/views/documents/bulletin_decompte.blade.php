<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de Décompte</title>
    <style>
        @page { margin: 8mm 10mm 8mm 10mm; size: A4 portrait; }
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 10px; line-height: 1.3; margin: 0; padding: 0; color: #000; }

        .hdr { width: 100%; border-collapse: collapse; margin-bottom: 3px; }
        .hdr td { vertical-align: middle; padding: 0; }
        .hdr-center { text-align: center; font-size: 11px; font-weight: bold; line-height: 1.4; }
        .hdr-divider { border-bottom: 1.5px solid #000; margin: 4px 0 8px 0; }

        .main-title {
            text-align: center; font-size: 15px; font-weight: bold;
            letter-spacing: 0.5px; margin: 6px 0 12px 0;
            text-transform: uppercase; text-decoration: underline;
        }

        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; border: 1.5px solid #000; }
        .data-table th { background: #d9d9d9; font-weight: bold; text-align: center; padding: 5px 6px; border: 1px solid #000; font-size: 10.5px; letter-spacing: 0.3px; }
        .data-table td { border: 1px solid #000; padding: 5px 7px; font-size: 10px; vertical-align: top; }
        .label { font-weight: bold; width: 36%; background: #f5f5f5; }
        .value { }

        .totals-table { width: 55%; border-collapse: collapse; margin: 0 0 12px auto; border: 1.5px solid #000; }
        .totals-table td { border: 1px solid #000; padding: 5px 9px; font-size: 10.5px; }
        .totals-table .tlabel { font-weight: bold; width: 65%; }
        .totals-table .tvalue { text-align: right; font-weight: bold; font-size: 11px; }
        .totals-table .net-row td { background: #d9d9d9; font-size: 12px; font-weight: bold; }

        .sig-table { width: 100%; border-collapse: collapse; margin-top: 5px; border: 1.5px solid #000; }
        .sig-table th { background: #d9d9d9; font-weight: bold; text-align: center; padding: 5px; border: 1px solid #000; font-size: 10.5px; }
        .sig-table td { height: 70px; border: 1px solid #000; vertical-align: top; }
    </style>
</head>
<body>

@php
    $logoOncaSrc   = file_exists(public_path('images/logo-onca.png'))   ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/logo-onca.png')))   : '';
    $sceauMarocSrc = file_exists(public_path('images/sceau-maroc.png')) ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) : '';

    $exercice     = $consultation->annee ?? date('Y');
    $typeBudget   = $consultation->type_budget ?? 'Investissement';

    $dateDoc      = data_get($documentData, 'date_document')
                    ? \Carbon\Carbon::parse(data_get($documentData, 'date_document'))->format('d/m/Y')
                    : date('d/m/Y');

    $numeroBc     = data_get($documentData, 'numero_bc') ?: ($consultation->numero_bc ?? ($doc['numero_bc'] ?? ''));
    $beneficiaire = data_get($documentData, 'societe') ?: ($doc['societe'] ?? '');
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

<!-- TITRE -->
<div class="main-title">BULLETIN DE DÉCOMPTE</div>

<!-- INFORMATIONS GÉNÉRALES -->
<table class="data-table">
    <tr><th colspan="2" style="font-size:11px; padding:6px;">RENSEIGNEMENTS GÉNÉRAUX</th></tr>
    <tr>
        <td class="label">Bon de commande N°</td>
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
        <td class="tlabel" style="background:#fff;">En lettres</td>
        <td class="tvalue" style="background:#fff; font-style:italic; font-weight:normal; text-align:left; font-size:9px;">{{ $netLettres }}</td>
    </tr>
</table>

<!-- SIGNATURES -->
<table class="sig-table">
    <tr>
        <th style="width:50%;">LE SOUS-ORDONNATEUR</th>
        <th style="width:50%;">LE BÉNÉFICIAIRE</th>
    </tr>
    <tr>
        <td style="height:80px;"></td>
        <td style="height:80px;"></td>
    </tr>
</table>

</body>
</html>
