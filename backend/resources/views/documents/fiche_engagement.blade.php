<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Fiche d'Engagement</title>
    <style>
        @page {
            margin: 8mm 10mm 8mm 10mm;
            size: A4 portrait;
        }
        * { box-sizing: border-box; }
        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 10.5px;
            line-height: 1.25;
            margin: 0;
            padding: 0;
            color: #000;
        }

        /* ── HEADER ── */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .header-table td { vertical-align: middle; padding: 0; }

        .header-center {
            text-align: center;
            font-size: 12.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            line-height: 1.4;
        }
        .header-divider { border-bottom: 1.5px solid #000; margin: 5px 0 8px 0; }

        /* ── CADRE BUDGET ── */
        .budget-box {
            width: 230px;
            border-collapse: collapse;
            margin-left: auto;
            margin-bottom: 5px;
        }
        .budget-box td {
            border: 1.5px solid #000;
            padding: 4px 8px;
            font-size: 10.5px;
        }

        /* ── TITRE ── */
        .num-fe {
            font-size: 10.5px;
            font-weight: bold;
            text-align: left;
            margin-bottom: 2px;
            padding-left: 40px;
        }
        .main-title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        /* ── TABLEAUX PRINCIPAUX ── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border: 1.5px solid #000;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 5px 7px;
            font-size: 10px;
        }
        .data-table .label-col {
            font-weight: bold;
            width: 34%;
            text-align: center;
        }
        .data-table .value-col {
            width: 66%;
            text-align: center;
            font-weight: bold;
        }

        /* ── TABLEAU RUBRIQUE BUDGETAIRE ── */
        .rubrique-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border: 1.5px solid #000;
            text-align: center;
        }
        .rubrique-table th, .rubrique-table td {
            border: 1px solid #000;
            padding: 3.5px 2px;
            font-size: 9px;
        }
        .rubrique-table th { font-weight: bold; }

        /* ── TABLEAU FINANCIER ── */
        .finance-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border: 1.5px solid #000;
        }
        .finance-table td {
            border: 1px solid #000;
            padding: 5px 7px;
            font-size: 10px;
        }
        .finance-table .flabel { font-weight: bold; width: 48%; }
        .finance-table .fval   { text-align: center; font-weight: bold; width: 52%; }

        /* ── VISA BOX ── */
        .visa-box {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            margin-top: 5px;
        }
        .visa-box td { border: 1px solid #000; padding: 5px 8px; }
    </style>
</head>
<body>

    @php
        /* ── Logos ── */
        $logoOncaPath  = public_path('images/logo-onca.png');
        $logoOncaSrc   = file_exists($logoOncaPath)  ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoOncaPath))  : '';
        $sceauMarocPath = public_path('images/sceau-maroc.png');
        $sceauMarocSrc  = file_exists($sceauMarocPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($sceauMarocPath)) : '';

        /* ── Données de base ── */
        $typeBudget = $consultation->type_budget ?? 'Fonctionnement';
        $exercice   = $consultation->annee ?? date('Y');

        /* ── N° Engagement : depuis documentData en priorité ── */
        $numEngagement = data_get($documentData, 'numero_engagement')
            ?: ($consultation->numero_bc
                ? $consultation->numero_bc
                : ($doc['numero_bc'] ?? ($doc['numero_engagement'] ?? '')));

        /* ── Date d'engagement ── */
        $dateEngagement = data_get($documentData, 'date_document')
            ? \Carbon\Carbon::parse(data_get($documentData, 'date_document'))->format('d/m/Y')
            : date('d/m/Y');

        /* ── Objet ── */
        $objet = data_get($documentData, 'objet')
            ?: ($consultation->objet_consultation ?? '');

        /* ── Bénéficiaire ── */
        $beneficiaire = data_get($documentData, 'societe')
            ?: ($doc['titulaire_nom']
                ?? ($doc['societe']
                    ?? ($consultation->fournisseur->raison_sociale
                        ?? ($consultation->engagement?->fournisseur?->raison_sociale ?? ''))));

        /* ── Montant de la dépense ── */
        $rawMontant = data_get($documentData, 'montant_ttc');
        if ($rawMontant === null || $rawMontant === '') {
            $rawMontant = $doc['total_ttc'] ?? 0;
        }
        $montantDepense   = (float) $rawMontant;
        $interetMoratoire = round($montantDepense * 0.01, 2);
        $montantTotal     = round($montantDepense + $interetMoratoire, 2);
        $montantEnLettres = \App\Helpers\NumberToWordsHelper::toFrenchMoneyWords($montantTotal);

        /* ── Imputation budgétaire ── */
        $article    = data_get($documentData, 'art')    ?: ($consultation->budget->art  ?? ($doc['art']  ?? '415'));
        $paragraphe = data_get($documentData, 'par')    ?: ($consultation->budget->par  ?? ($doc['par']  ?? ''));
        $ligne      = data_get($documentData, 'lig')    ?: ($consultation->budget->lig  ?? ($doc['lig']  ?? ''));

        /* ── Crédits budgétaires ── */
        $creditCp         = data_get($documentData, 'credit_budget_cp', data_get($documentData, 'credit_ouvert_cp'));
        $creditCe         = data_get($documentData, 'credit_budget_ce', data_get($documentData, 'credit_ouvert_ce'));
        $depensesCp       = data_get($documentData, 'depenses_engagees_cp', data_get($documentData, 'depenses_anterieures_cp'));
        $depensesCe       = data_get($documentData, 'depenses_engagees_ce', data_get($documentData, 'depenses_anterieures_ce'));
        $disponibleCp     = ($creditCp !== null && $creditCp !== '' && $depensesCp !== null && $depensesCp !== '')
                    ? ((float)$creditCp - (float)$depensesCp) : null;
        $disponibleCe     = ($creditCe !== null && $creditCe !== '' && $depensesCe !== null && $depensesCe !== '')
                    ? ((float)$creditCe - (float)$depensesCe) : null;
        $montantDepense = (float) data_get($documentData, 'montant_depense_neuf', $montantDepense);
        $interetMoratoire = round($montantDepense * 0.01, 2);
        $montantTotal = round($montantDepense + $interetMoratoire, 2);
        $engagementProposeCp = data_get($documentData, 'engagement_propose_cp');
        if (!$engagementProposeCp) {
            $engagementProposeCp = $montantTotal;
        }

        /* ── Pièces jointes ── */
        $piecesJointes = data_get($documentData, 'pieces_jointes')
            ?: ($consultation->numero_bc ? 'Bon de commande N° '.$consultation->numero_bc : '');
    @endphp

    <!-- ══════════ HEADER LOGOS ══════════ -->
    <table class="header-table">
        <tr>
            <td style="width:22%; text-align:left; vertical-align:middle;">
                @if($logoOncaSrc)
                    <img src="{{ $logoOncaSrc }}" style="height:58px; width:auto; max-width:140px;" alt="ONCA">
                @else
                    <div style="font-size:7.5px; font-weight:bold; line-height:1.3;">
                        المكتب الوطني للإستشارة الفلاحية<br>
                        <span style="font-size:7px;">Office National du Conseil Agricole</span>
                    </div>
                @endif
            </td>
            <td style="width:56%; text-align:center; vertical-align:middle; padding:0 5px;">
                <div class="header-center">
                    Direction Régionale du Conseil Agricole<br>
                    <span style="font-size:13px; letter-spacing:0.5px;">RABAT SALE KENITRA</span>
                </div>
            </td>
            <td style="width:22%; text-align:right; vertical-align:middle;">
                @if($sceauMarocSrc)
                    <img src="{{ $sceauMarocSrc }}" style="height:58px; width:auto; max-width:140px;" alt="Royaume du Maroc">
                @else
                    <div style="font-size:7.5px; font-weight:bold; line-height:1.3; text-align:right;">
                        المملكة المغربية<br>
                        <span style="font-size:7px;">وزارة الفلاحة والصيد البحري</span>
                    </div>
                @endif
            </td>
        </tr>
    </table>
    <div class="header-divider"></div>

    <!-- ══════════ CADRE BUDGET / EXERCICE ══════════ -->
    <table class="budget-box">
        <tr>
            <td style="width:40%; font-weight:bold;">Budget</td>
            <td style="width:60%; font-weight:bold; text-align:center;">{{ $typeBudget }}</td>
        </tr>
        <tr>
            <td style="font-weight:bold;">Exercice</td>
            <td style="font-weight:bold; text-align:center;">{{ $exercice }}</td>
        </tr>
    </table>

    <!-- ══════════ TITRE PRINCIPAL ══════════ -->
    <div class="num-fe">N° &nbsp;&nbsp; {{ $numEngagement }}</div>
    <div class="main-title">FICHE D'ENGAGEMENT</div>

    <!-- ══════════ TABLEAU 1 : IDENTIFICATION ══════════ -->
    <table class="data-table">
        <tr>
            <td class="label-col">Référence de l'engagement</td>
            <td class="value-col">{{ $numEngagement }}</td>
        </tr>
        <tr>
            <td class="label-col">Objet</td>
            <td class="value-col" style="text-transform:uppercase;">{{ $objet }}</td>
        </tr>
        <tr>
            <td class="label-col">Forme</td>
            <td class="value-col">Bon de commande</td>
        </tr>
        <tr>
            <td class="label-col">Montant en dirhams</td>
            <td class="value-col" style="font-size:11px;">{{ number_format($montantDepense, 2, ',', ' ') }}</td>
        </tr>
    </table>

    <!-- ══════════ TABLEAU 2 : RUBRIQUE BUDGÉTAIRE ══════════ -->
    @if(strtolower((string) $typeBudget) === 'fonctionnement')
    <table class="rubrique-table">
        <thead>
            <tr>
                <th style="width:28%;">Rubrique budgétaire</th>
                <th style="width:18%;">Crédit budgétaire</th>
                <th style="width:18%;">Dépenses engagées</th>
                <th style="width:18%;">Disponible</th>
                <th style="width:18%;">Engagement de la dépense proposée</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-weight:bold; text-align:left;">ARTICLE<br>PARAGRAPHE<br>LIGNE</td>
                <td style="font-weight:bold; vertical-align:middle;">
                    {{ $creditCp !== null && $creditCp !== '' ? number_format((float)$creditCp, 2, ',', ' ') : '-' }}
                </td>
                <td style="font-weight:bold; vertical-align:middle;">
                    {{ $depensesCp !== null && $depensesCp !== '' ? number_format((float)$depensesCp, 2, ',', ' ') : '-' }}
                </td>
                <td style="font-weight:bold; vertical-align:middle;">
                    {{ $disponibleCp !== null ? number_format((float)$disponibleCp, 2, ',', ' ') : '-' }}
                </td>
                <td style="font-weight:bold; vertical-align:middle;">
                    {{ number_format((float)$engagementProposeCp, 2, ',', ' ') }}
                </td>
            </tr>
            <tr>
                <td style="font-weight:bold; text-align:left;">Valeurs</td>
                <td colspan="4" style="font-weight:bold; text-align:left;">
                    ARTICLE {{ $article }} &nbsp;&nbsp; PARAGRAPHE {{ $paragraphe }} &nbsp;&nbsp; LIGNE {{ $ligne }}
                </td>
            </tr>
        </tbody>
    </table>
    @else
    <table class="rubrique-table">
        <thead>
            <tr>
                <th colspan="2" style="width:22%;">Rubrique budgétaire</th>
                <th colspan="2" style="width:20%;">Crédit budgétaire</th>
                <th colspan="2" style="width:20%;">Dépenses engagées</th>
                <th colspan="2" style="width:19%;">Disponible</th>
                <th colspan="2" style="width:19%;">Engagement de la dépense proposée</th>
            </tr>
            <tr>
                <th style="width:13%;"></th>
                <th style="width:9%;"></th>
                <th style="width:10%;">CP</th>
                <th style="width:10%;">CE</th>
                <th style="width:10%;">CP</th>
                <th style="width:10%;">CE</th>
                <th style="width:10%;">CP</th>
                <th style="width:9%;">CE</th>
                <th style="width:10%;">CP</th>
                <th style="width:9%;">CE</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-weight:bold; text-align:left;">ARTICLE</td>
                <td style="font-weight:bold;">{{ $article }}</td>
                <td rowspan="3" style="font-weight:bold; vertical-align:middle;">
                    {{ $creditCp !== null && $creditCp !== '' ? number_format((float)$creditCp, 2, ',', ' ') : '-' }}
                </td>
                <td rowspan="3" style="font-weight:bold; vertical-align:middle;">
                    {{ $creditCe !== null && $creditCe !== '' ? number_format((float)$creditCe, 2, ',', ' ') : '-' }}
                </td>
                <td rowspan="3" style="font-weight:bold; vertical-align:middle;">
                    {{ $depensesCp !== null && $depensesCp !== '' ? number_format((float)$depensesCp, 2, ',', ' ') : '-' }}
                </td>
                <td rowspan="3" style="font-weight:bold; vertical-align:middle;">
                    {{ $depensesCe !== null && $depensesCe !== '' ? number_format((float)$depensesCe, 2, ',', ' ') : '-' }}
                </td>
                <td rowspan="3" style="font-weight:bold; vertical-align:middle;">
                    {{ $disponibleCp !== null ? number_format((float)$disponibleCp, 2, ',', ' ') : '-' }}
                </td>
                <td rowspan="3" style="font-weight:bold; vertical-align:middle;">
                    {{ $disponibleCe !== null ? number_format((float)$disponibleCe, 2, ',', ' ') : '-' }}
                </td>
                <td rowspan="3" style="font-weight:bold; vertical-align:middle;">
                    {{ number_format((float)$engagementProposeCp, 2, ',', ' ') }}
                </td>
                <td rowspan="3" style="font-weight:bold; vertical-align:middle;">-</td>
            </tr>
            <tr>
                <td style="font-weight:bold; text-align:left;">PARAGRAPHE</td>
                <td style="font-weight:bold;">{{ $paragraphe }}</td>
            </tr>
            <tr>
                <td style="font-weight:bold; text-align:left;">LIGNE</td>
                <td style="font-weight:bold;">{{ $ligne }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- ══════════ TABLEAU 3 : FINANCIER ══════════ -->
    <table class="finance-table">
        <tr>
            <td class="flabel">Bénéficiaire</td>
            <td class="fval" style="text-transform:uppercase;">{{ $beneficiaire }}</td>
        </tr>
        <tr>
            <td class="flabel">Montant de la depense en dirhams</td>
            <td class="fval">{{ number_format($montantDepense, 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <td class="flabel">
                Somme à valoir pour intérêt moratoire<br>
                <span style="font-size:9px; font-weight:normal;">(1% du montant en dirhams)</span>
            </td>
            <td class="fval">{{ number_format($interetMoratoire, 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <td class="flabel">Montant total de l'engagement en dirhams</td>
            <td class="fval" style="font-size:11px;">{{ number_format($montantTotal, 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <td class="flabel" style="text-align:center;">En Lettre</td>
            <td class="fval" style="font-style:italic;">{{ $montantEnLettres }}</td>
        </tr>
    </table>

    <!-- ══════════ TABLEAU 4 : PIÈCES JOINTES & VISA ══════════ -->
    <table class="visa-box">
        <tr>
            <td colspan="2" style="background:#fafafa; padding:4px 8px;">
                <span style="font-weight:bold;">Pièces jointes :</span><br>
                <span style="margin-left:10px; font-weight:bold;">{{ $piecesJointes }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="text-align:center; font-weight:bold; font-size:11.5px; padding:5px; letter-spacing:0.5px;">
                VISA DU SOUS-ORDONNATEUR
            </td>
        </tr>
        <tr>
            <td colspan="2" style="height:90px; vertical-align:top; border-bottom:none;">
                <!-- Zone réservée au visa, signature et cachet -->
            </td>
        </tr>
        <tr>
            <td style="width:45%; font-weight:bold; text-align:center; border-top:1px solid #000;">Date :</td>
            <td style="width:55%; font-weight:bold; text-align:center; border-top:1px solid #000;">{{ $dateEngagement }}</td>
        </tr>
    </table>

</body>
</html>
