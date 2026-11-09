<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordre de Paiement</title>
    <style>
        @page { margin: 16px 32px 96px 32px; size: A4 portrait; }
        * { box-sizing: border-box; }
        body { font-family: "Times New Roman", Times, serif; font-size: 10.5px; line-height: 1.32; margin: 0; padding: 0; color: #000; }
        td, th { font-family: "Times New Roman", Times, serif; }

        .hdr { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .hdr td { vertical-align: middle; padding: 0; border: none; }
        .hdr-center { text-align: center; font-size: 13px; font-weight: bold; line-height: 1.3; padding: 0 8px; }
        .hdr-divider { border-bottom: 2px solid #000080; margin: 4px 0 7px 0; }

        .budget-box { width: 235px; border-collapse: collapse; margin-left: auto; margin-bottom: 5px; border: 1.5px solid #000; }
        .budget-box td { border: 1px solid #000; padding: 3.5px 7px; font-size: 9.5px; font-weight: bold; }
        .budget-box .val { text-align: center; }

        .title-banner {
            background-color: #e5e0d8;
            border: 1.5px solid #000;
            text-align: center;
            padding: 5px 0;
            margin: 4px 0 7px 0;
            font-size: 13.5px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .num-box { border-collapse: collapse; margin-left: auto; margin-bottom: 8px; border: 1.5px solid #000; }
        .num-box td { border: 1px solid #000; padding: 3.5px 8px; font-size: 10px; }

        .main-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; border: 1.5px solid #000; }
        .main-table th {
            background: #e5e0d8; font-weight: bold; text-align: center;
            padding: 5px 6px; border: 1px solid #000; font-size: 10px;
            letter-spacing: 0.3px; text-transform: uppercase;
        }
        .main-table td { border: 1px solid #000; padding: 4.5px 7px; font-size: 10px; vertical-align: middle; }
        .label-col { font-weight: bold; width: 22%; text-align: center; background: #f5f5f5; vertical-align: middle !important; }
        .value-col { }

        .pieces-table { width: 100%; border-collapse: collapse; }
        .pieces-table td { border: 1px solid #000; padding: 3.5px 6px; font-size: 9.5px; }
        .pieces-table .num { width: 6%; text-align: center; font-weight: bold; }
        .pieces-table .pj { width: 72%; }
        .pieces-table .mode-cell { width: 22%; text-align: center; font-weight: bold; font-size: 11px; vertical-align: middle; }

        .imput-table { width: 100%; border-collapse: collapse; border: 1.5px solid #000; margin-bottom: 8px; }
        .imput-table th { background: #e5e0d8; font-weight: bold; text-align: center; padding: 4.5px; border: 1px solid #000; font-size: 9.5px; }
        .imput-table td { border: 1px solid #000; padding: 4.5px 6px; text-align: center; font-size: 9.5px; font-weight: bold; }

        .visa-row { width: 100%; border-collapse: collapse; border: 1.5px solid #000; page-break-inside: avoid; }
        .visa-row th { background: #e5e0d8; font-weight: bold; text-align: center; padding: 5px; border: 1px solid #000; font-size: 10px; letter-spacing: 0.3px; }
        .visa-row td { height: 75px; border: 1px solid #000; vertical-align: top; }
    </style>
    @include('documents.partials.bc_typography')
</head>
<body>

@php
    $logoOncaSrc   = file_exists(public_path('images/logo-onca.png'))   ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/logo-onca.png')))   : '';
    $sceauMarocSrc = file_exists(public_path('images/sceau-maroc.png')) ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) : '';

    $isConvention = (!empty($consultation) && (in_array($consultation->mode_engagement, ['Convention', 'Contrat']) || str_starts_with(strtoupper($consultation->numero_consultation ?? ''), 'CONV')))
        || str_starts_with(strtoupper((string)data_get($documentData, 'numero_consultation', '')), 'CONV');
    $typeBudget    = data_get($documentData, 'type_budget') ?: ($consultation->type_budget ?? 'Investissement');
    $isContrat     = $isConvention && strtolower((string)$typeBudget) === 'fonctionnement';
    $labelEngagement = $isConvention ? ($isContrat ? 'Contrat' : 'Convention') : 'Bon de commande';
    $exercice      = $consultation->annee ?? date('Y');
    $creditType    = data_get($documentData, 'credit_type', 'Crédit Consolidés');
    $exerciceOrig  = data_get($documentData, 'exercice_origine', $exercice - 1);

    $numOP         = data_get($documentData, 'numero_op', '');
    $suffixe       = '/DRCA-RSK/' . $exercice;
    $dateDoc       = data_get($documentData, 'date_document')
                     ? \Carbon\Carbon::parse(data_get($documentData, 'date_document'))->format('d/m/Y')
                     : '';

    $beneficiaire  = data_get($documentData, 'societe') ?: ($doc['societe'] ?? ($doc['attributaire'] ?? ''));
    $adresse       = data_get($documentData, 'adresse_societe') ?: ($doc['adresse_societe'] ?? '');
    $rib           = data_get($documentData, 'rib') ?: ($doc['rib'] ?? '');
    $banque        = data_get($documentData, 'banque', '');
    $objet         = data_get($documentData, 'objet') ?: ($consultation->objet_consultation ?? '');

    $cleanNumConv  = trim(preg_replace('/\bCONV\s*[-_]?\s*/i', '', (string)($doc['numero_consultation'] ?? ($consultation->numero_consultation ?? ''))));
    $cleanNumConv  = trim(preg_replace('/^[-_\s]+|[-_\s]+$/', '', $cleanNumConv));
    $defaultRef    = $isConvention ? (($isContrat ? 'Contrat N° ' : 'Convention N° ') . $cleanNumConv) : ($doc['numero_bc'] ?? '');
    $reference     = data_get($documentData, 'reference_bc') ?: $defaultRef;

    $montantOp     = (float)(data_get($documentData, 'montant_op') ?: $doc['total_ttc'] ?? 0);
    $montantEng    = (float)(data_get($documentData, 'montant_engagement') ?: $montantOp);
    $sommeALettres = \App\Helpers\NumberToWordsHelper::toFrenchMoneyWords($montantOp);

    // Pièces jointes → tableau de lignes
    $pjRaw   = data_get($documentData, 'pieces_jointes_op', '');
    $pjLines = array_values(array_filter(explode("\n", $pjRaw), fn($l) => trim($l) !== ''));
    // Toujours 10 lignes
    $pjLines = array_pad($pjLines, 10, '');

    $art              = data_get($documentData, 'art') ?: ($consultation->budget->art ?? '');
    $par              = data_get($documentData, 'par') ?: ($consultation->budget->par ?? '');
    $lig              = data_get($documentData, 'lig') ?: ($consultation->budget->lig ?? '');
    $intituleRubrique = data_get($documentData, 'intitule_rubrique') ?: ($consultation->intitule ?? $consultation->objet_consultation ?? '');
    $prestationNature = data_get($documentData, 'prestation_meme_nature', 'PRESTATION DE MEME NATURE');
@endphp

@include('documents.partials.bc_footer')

<!-- HEADER -->
<table class="hdr">
    <tr>
        <td style="width:24%; text-align:left;">
            @if($logoOncaSrc)<img src="{{ $logoOncaSrc }}" style="height:64px; width:auto;">@endif
        </td>
        <td style="width:52%;" class="hdr-center">
            Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
        </td>
        <td style="width:24%; text-align:right;">
            @if($sceauMarocSrc)<img src="{{ $sceauMarocSrc }}" style="height:64px; width:auto;">@endif
        </td>
    </tr>
</table>
<div class="hdr-divider"></div>

<!-- BUDGET BOX -->
<table class="budget-box">
    <tr><td style="background:#f5f5f5;">Budget</td><td class="val">{{ $typeBudget }}</td></tr>
    <tr><td style="background:#f5f5f5;">Crédit</td><td class="val">{{ $creditType }}</td></tr>
    <tr><td style="background:#f5f5f5;">Exercice</td><td class="val">{{ $exercice }}</td></tr>
    <tr><td style="background:#f5f5f5;">Exercice origine</td><td class="val">{{ $exerciceOrig }}</td></tr>
</table>

<!-- TITRE -->
<div class="title-banner">ORDRE DE PAIEMENT (OP)</div>

<!-- N° OP + DATE -->
<table class="num-box" style="width:56%; margin-left:auto; margin-bottom:8px;">
    <tr>
        <td style="font-weight:bold; width:24%; background:#f5f5f5;">OP N°</td>
        <td style="font-weight:bold; font-size:12.5px; text-align:center; width:20%;">{{ $numOP }}</td>
        <td style="font-weight:bold; width:56%; text-align:center;">{{ $suffixe }}</td>
    </tr>
    <tr>
        <td style="font-weight:bold; background:#f5f5f5;">DATE</td>
        <td colspan="2" style="text-align:center; font-weight:bold;">{{ $dateDoc }}</td>
    </tr>
</table>

<!-- RENSEIGNEMENTS SUR LA DEPENSE -->
<table class="main-table">
    <tr><th colspan="3" style="font-size:10.5px; padding:6px;">RENSEIGNEMENTS SUR LA DÉPENSE</th></tr>
    <tr>
        <td class="label-col" rowspan="2">BÉNÉFICIAIRE</td>
        <td colspan="2" style="font-weight:bold; text-transform:uppercase; font-size:10px;">{{ $beneficiaire }}</td>
    </tr>
    <tr>
        <td colspan="2">{{ $adresse }}</td>
    </tr>
    <tr>
        <td class="label-col">RIB N°</td>
        <td style="font-weight:bold; font-family:monospace; font-size:9px; width:50%;">{{ $rib }}</td>
        <td style="font-weight:bold; width:28%;">{{ $banque }}</td>
    </tr>
    <tr>
        <td class="label-col">OBJET</td>
        <td colspan="2" style="text-transform:uppercase;">{{ $objet }}</td>
    </tr>
    <tr>
        <td class="label-col">Référence</td>
        <td colspan="2" style="font-weight:bold; text-align:center;">{{ $reference }}</td>
    </tr>
    <!-- MONTANT + PIÈCES JOINTES -->
    <tr>
        <td class="label-col" style="vertical-align:middle;">MONTANT (DH)</td>
        <td colspan="2" style="padding:0;">
            <table class="pieces-table">
                <tr>
                    <td class="num" style="background:#e5e0d8; font-weight:bold; border-bottom:1px solid #000;"></td>
                    <td class="pj" style="background:#e5e0d8; font-weight:bold; font-size:10px; padding:4px; border-bottom:1px solid #000;">PIÈCES JOINTES :</td>
                    <td class="mode-cell" style="background:#e5e0d8; font-weight:bold; font-size:10px; border-bottom:1px solid #000;">MODE DE PAIEMENT</td>
                </tr>
                @foreach($pjLines as $idx => $pj)
                <tr>
                    <td class="num">{{ $idx + 1 }}</td>
                    <td class="pj">{{ $pj }}</td>
                    @if($idx === 0)
                    <td class="mode-cell" rowspan="10" style="font-size:12px; font-weight:bold;">VIREMENT</td>
                    @endif
                </tr>
                @endforeach
            </table>
        </td>
    </tr>
    <tr>
        <td class="label-col" style="font-weight:bold; font-size:11px;">{{ number_format($montantOp, 2, ',', ' ') }}</td>
        <td colspan="2" style="padding:0;">
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="padding:5px 7px; font-weight:bold; font-size:10px; border:none;">SOMME À PAYER<br><span style="font-size:8.5px; font-weight:normal;">(en lettres)</span></td>
                    <td style="padding:5px 7px; font-style:italic;">{{ $sommeALettres }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- IMPUTATION COMPTABLE -->
<table class="imput-table">
    <tr><th colspan="6" style="font-size:10.5px; padding:5px;">IMPUTATION COMPTABLE</th></tr>
    <tr>
        <th style="width:10%;">Chap</th>
        <th style="width:10%;">Art</th>
        <th style="width:15%;">Parag</th>
        <th style="width:10%;">Ligne</th>
        <th style="width:27.5%;">ENGAGEMENT (DH)</th>
        <th style="width:27.5%;">PAIEMENT (DH)</th>
    </tr>
    <tr>
        <td></td>
        <td>{{ $art }}</td>
        <td>{{ $par }}</td>
        <td>{{ $lig }}</td>
        <td>{{ number_format($montantEng, 2, ',', ' ') }}</td>
        <td>{{ number_format($montantOp, 2, ',', ' ') }}</td>
    </tr>
    <tr>
        <th colspan="3" style="text-align:left; font-size:9px; padding:4px 6px;">INTITULÉ DE LA RUBRIQUE</th>
        <th colspan="3" style="text-align:left; font-size:9px; padding:4px 6px;">PRESTATION DE MÊME NATURE</th>
    </tr>
    <tr>
        <td colspan="3" style="font-size:9px; text-align:left;">{{ $intituleRubrique }}</td>
        <td colspan="3" style="font-size:9px; text-align:left;">{{ $prestationNature }}</td>
    </tr>
</table>

<!-- VISAS -->
<table class="visa-row">
    <tr>
        <th style="width:50%;">VISA DU SOUS-ORDONNATEUR</th>
        <th style="width:50%;">VISA DU FONDÉ DE POUVOIR</th>
    </tr>
    <tr>
        <td style="height:75px;"></td>
        <td style="height:75px;"></td>
    </tr>
</table>

</body>
</html>
