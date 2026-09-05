<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordre de Paiement</title>
    <style>
        @page { margin: 7mm 10mm 7mm 10mm; size: A4 portrait; }
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 9.5px; line-height: 1.25; margin: 0; padding: 0; color: #000; }

        .hdr { width: 100%; border-collapse: collapse; margin-bottom: 3px; }
        .hdr td { vertical-align: middle; padding: 0; }
        .hdr-center { text-align: center; font-size: 11px; font-weight: bold; line-height: 1.4; }
        .hdr-divider { border-bottom: 1.5px solid #000; margin: 4px 0 5px 0; }

        .budget-box { width: 220px; border-collapse: collapse; margin-left: auto; margin-bottom: 5px; border: 1.5px solid #000; }
        .budget-box td { border: 1px solid #000; padding: 3px 7px; font-size: 9px; font-weight: bold; }
        .budget-box .val { text-align: center; }

        .main-title {
            text-align: center; font-size: 14px; font-weight: bold;
            letter-spacing: 0.5px; margin: 5px 0 8px 0;
            text-transform: uppercase; text-decoration: underline;
        }

        .num-box { border-collapse: collapse; margin-left: auto; margin-bottom: 8px; }
        .num-box td { border: 1px solid #000; padding: 4px 8px; font-size: 10px; }

        .main-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; border: 1.5px solid #000; }
        .main-table th {
            background: #d9d9d9; font-weight: bold; text-align: center;
            padding: 5px 6px; border: 1px solid #000; font-size: 10px;
            letter-spacing: 0.3px; text-transform: uppercase;
        }
        .main-table td { border: 1px solid #000; padding: 4px 6px; font-size: 9.5px; vertical-align: top; }
        .label-col { font-weight: bold; width: 22%; text-align: center; vertical-align: middle !important; }
        .value-col { }

        .pieces-table { width: 100%; border-collapse: collapse; }
        .pieces-table td { border: 1px solid #000; padding: 3px 5px; font-size: 9px; }
        .pieces-table .num { width: 6%; text-align: center; font-weight: bold; }
        .pieces-table .pj { width: 72%; }
        .pieces-table .mode-cell { width: 22%; text-align: center; font-weight: bold; font-size: 11px; vertical-align: middle; }

        .imput-table { width: 100%; border-collapse: collapse; border: 1.5px solid #000; margin-bottom: 6px; }
        .imput-table th { background: #d9d9d9; font-weight: bold; text-align: center; padding: 4px; border: 1px solid #000; font-size: 9.5px; }
        .imput-table td { border: 1px solid #000; padding: 4px 6px; text-align: center; font-size: 9.5px; font-weight: bold; }

        .visa-row { width: 100%; border-collapse: collapse; border: 1.5px solid #000; }
        .visa-row th { background: #d9d9d9; font-weight: bold; text-align: center; padding: 5px; border: 1px solid #000; font-size: 10px; letter-spacing: 0.3px; }
        .visa-row td { height: 55px; border: 1px solid #000; vertical-align: top; }
    </style>
</head>
<body>

@php
    $logoOncaSrc   = file_exists(public_path('images/logo-onca.png'))   ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/logo-onca.png')))   : '';
    $sceauMarocSrc = file_exists(public_path('images/sceau-maroc.png')) ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) : '';

    $typeBudget    = $consultation->type_budget ?? 'Investissement';
    $exercice      = $consultation->annee ?? date('Y');
    $creditType    = data_get($documentData, 'credit_type', 'Crédit Consolidés');
    $exerciceOrig  = data_get($documentData, 'exercice_origine', $exercice - 1);

    $numOP         = data_get($documentData, 'numero_op', '');
    $suffixe       = '/INV/DRCA-RSK/' . $exercice;
    $dateDoc       = data_get($documentData, 'date_document')
                     ? \Carbon\Carbon::parse(data_get($documentData, 'date_document'))->format('d/m/Y')
                     : '';

    $beneficiaire  = data_get($documentData, 'societe') ?: ($doc['societe'] ?? '');
    $adresse       = data_get($documentData, 'adresse_societe') ?: ($doc['adresse_societe'] ?? '');
    $rib           = data_get($documentData, 'rib') ?: ($doc['rib'] ?? '');
    $banque        = data_get($documentData, 'banque', '');
    $objet         = data_get($documentData, 'objet') ?: ($consultation->objet_consultation ?? '');
    $reference     = data_get($documentData, 'reference_bc') ?: ($doc['numero_bc'] ?? '');

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

<!-- HEADER -->
<table class="hdr">
    <tr>
        <td style="width:22%; text-align:left;">
            @if($logoOncaSrc)<img src="{{ $logoOncaSrc }}" style="height:52px; width:auto;">@endif
        </td>
        <td style="width:56%;" class="hdr-center">
            Direction régionale du Conseil agricole<br>
            <span style="font-size:12px;">Rabat-Salé-Kénitra</span>
        </td>
        <td style="width:22%; text-align:right;">
            @if($sceauMarocSrc)<img src="{{ $sceauMarocSrc }}" style="height:52px; width:auto;">@endif
        </td>
    </tr>
</table>
<div class="hdr-divider"></div>

<!-- BUDGET BOX -->
<table class="budget-box">
    <tr><td>Budget</td><td class="val">{{ $typeBudget }}</td></tr>
    <tr><td>Crédit</td><td class="val">{{ $creditType }}</td></tr>
    <tr><td>Exercice</td><td class="val">{{ $exercice }}</td></tr>
    <tr><td>Exercice origine</td><td class="val">{{ $exerciceOrig }}</td></tr>
</table>

<!-- TITRE -->
<div class="main-title">ORDRE DE PAIEMENT (OP)</div>

<!-- N° OP + DATE -->
<table class="num-box" style="width:60%; margin-left:auto; margin-bottom:8px;">
    <tr>
        <td style="font-weight:bold; width:22%;">OP N°</td>
        <td style="font-weight:bold; font-size:13px; text-align:center; width:15%;">{{ $numOP }}</td>
        <td style="font-weight:bold; width:63%;">{{ $suffixe }}</td>
    </tr>
    <tr>
        <td style="font-weight:bold;">DATE</td>
        <td colspan="2" style="text-align:center;">{{ $dateDoc }}</td>
    </tr>
</table>

<!-- RENSEIGNEMENTS SUR LA DEPENSE -->
<table class="main-table">
    <tr><th colspan="3" style="font-size:10.5px; padding:6px;">RENSEIGNEMENTS SUR LA DEPENSE</th></tr>
    <tr>
        <td class="label-col" rowspan="2">BENEFICIAIRE</td>
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
                    <td class="num" style="background:#f0f0f0; font-weight:bold; border-bottom:1px solid #000;"></td>
                    <td class="pj" style="background:#d9d9d9; font-weight:bold; font-size:10px; padding:4px; border-bottom:1px solid #000;">PIECES JOINTES:</td>
                    <td class="mode-cell" style="background:#d9d9d9; font-weight:bold; font-size:10px; border-bottom:1px solid #000;">MODE DE PAIEMENT</td>
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
                    <td style="padding:5px 7px; font-weight:bold; font-size:10px; border:none;">SOMME A PAYER<br><span style="font-size:8.5px; font-weight:normal;">(en lettres)</span></td>
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
        <th colspan="3" style="text-align:left; font-size:9px; padding:4px 6px;">INTITULE DE LA RUBRIQUE</th>
        <th colspan="3" style="text-align:left; font-size:9px; padding:4px 6px;">PRETSATION DE MEME NATURE</th>
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
        <th style="width:50%;">VISA DU FONDE DE POUVOIRS</th>
    </tr>
    <tr>
        <td style="height:60px;"></td>
        <td style="height:60px;"></td>
    </tr>
</table>

</body>
</html>
