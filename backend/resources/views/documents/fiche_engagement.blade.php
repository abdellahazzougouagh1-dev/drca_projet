<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Fiche d'Engagement</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 16px 34px 96px 34px;
        }
        * { box-sizing: border-box; }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11.5px;
            line-height: 1.38;
            margin: 0;
            padding: 0;
            color: #000;
        }
        td, th {
            font-family: "Times New Roman", Times, serif;
        }

        /* ── HEADER ── */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .header-table td { vertical-align: middle; padding: 0; border: none; }

        .header-center {
            text-align: center;
            font-size: 13.5px;
            font-weight: bold;
            line-height: 1.35;
        }
        .header-divider { border-bottom: 2px solid #000080; margin: 4px 0 12px 0; }

        /* ── CADRE BUDGET ── */
        .budget-box {
            width: 245px;
            border-collapse: collapse;
            margin-left: auto;
            margin-bottom: 8px;
            border: 1.5px solid #000;
        }
        .budget-box td {
            border: 1px solid #000;
            padding: 5px 10px;
            font-size: 11px;
        }

        /* ── TITRE ── */
        .num-fe {
            font-size: 11.5px;
            font-weight: bold;
            text-align: left;
            margin-bottom: 6px;
            padding-left: 16px;
        }
        .main-title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin: 0 auto 14px auto;
            padding: 7px 14px;
            background: #e5e0d8;
            border: 2px solid #000;
            width: 72%;
            text-transform: uppercase;
        }

        /* ── TABLEAUX PRINCIPAUX ── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            border: 1.5px solid #000;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 8px 10px;
            font-size: 11px;
            vertical-align: middle;
        }
        .data-table .label-col {
            font-weight: bold;
            width: 34%;
            text-align: center;
            background: #f5f5f5;
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
            margin-bottom: 14px;
            border: 1.5px solid #000;
            text-align: center;
        }
        .rubrique-table th, .rubrique-table td {
            border: 1px solid #000;
            padding: 5.5px 4px;
            font-size: 9.5px;
            vertical-align: middle;
        }
        .rubrique-table th {
            font-weight: bold;
            background: #e5e0d8;
        }

        /* ── TABLEAU FINANCIER ── */
        .finance-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            border: 1.5px solid #000;
        }
        .finance-table td {
            border: 1px solid #000;
            padding: 7.5px 10px;
            font-size: 11px;
            vertical-align: middle;
        }
        .finance-table .flabel { font-weight: bold; width: 46%; background: #f5f5f5; }
        .finance-table .fval   { text-align: center; font-weight: bold; width: 54%; }

        /* ── VISA BOX ── */
        .visa-box {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            margin-top: 4px;
            page-break-inside: avoid;
        }
        .visa-box td { border: 1px solid #000; padding: 6px 10px; font-size: 11px; }
    </style>
    @include('documents.partials.bc_typography')
</head>
<body>

    @php
        /* ── Logos ── */
        $logoOncaPath  = public_path('images/logo-onca.png');
        $logoOncaSrc   = file_exists($logoOncaPath)  ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoOncaPath))  : '';
        $sceauMarocPath = public_path('images/sceau-maroc.png');
        $sceauMarocSrc  = file_exists($sceauMarocPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($sceauMarocPath)) : '';

        $isConvention = (!empty($consultation) && (in_array($consultation->mode_engagement, ['Convention', 'Contrat']) || str_starts_with(strtoupper($consultation->numero_consultation ?? ''), 'CONV')))
            || str_starts_with(strtoupper((string)data_get($documentData, 'numero_consultation', '')), 'CONV');

        /* ── Données de base ── */
        $typeBudget = data_get($documentData, 'type_budget') ?: ($consultation->type_budget ?? 'Investissement');
        $exercice   = $consultation->annee ?? date('Y');
        $isContrat  = $isConvention && strtolower((string)$typeBudget) === 'fonctionnement';
        $labelEngagement = $isConvention ? ($isContrat ? 'Contrat' : 'Convention') : 'Bon de commande';
        $formeEngagement = data_get($documentData, 'forme_engagement')
            ?: ($doc['forme_engagement'] ?? $labelEngagement);

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
            ?: (data_get($documentData, 'attributaire')
                ?: ($doc['titulaire_nom']
                    ?? ($doc['societe']
                        ?? ($doc['attributaire']
                            ?? ($consultation->fournisseur->raison_sociale
                                ?? ($consultation->engagement?->fournisseur?->raison_sociale ?? ''))))));

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
        if ($isConvention) {
            $cleanNum = trim(preg_replace('/\bCONV\s*[-_]?\s*/i', '', (string)($consultation->numero_consultation ?? '')));
            $cleanNum = trim(preg_replace('/^[-_\s]+|[-_\s]+$/', '', $cleanNum));
            $prefix = $isContrat ? 'Contrat' : 'Convention';
            $piecesJointes = data_get($documentData, 'pieces_jointes')
                ?: ($doc['pieces_jointes'] ?? ($prefix . ' N° ' . $cleanNum));
        } else {
            $piecesJointes = data_get($documentData, 'pieces_jointes')
                ?: ($doc['pieces_jointes'] ?? ($consultation->numero_bc ? 'Bon de commande N° '.$consultation->numero_bc : ''));
        }
    @endphp

    @include('documents.partials.bc_footer')

    <!-- ══════════ HEADER LOGOS ══════════ -->
    <table class="header-table">
        <tr>
            <td style="width:24%; text-align:left; vertical-align:middle;">
                @if($logoOncaSrc)
                    <img src="{{ $logoOncaSrc }}" style="height:68px; width:auto;" alt="ONCA">
                @else
                    <div style="font-size:7.5px; font-weight:bold; line-height:1.3;">
                        المكتب الوطني للإستشارة الفلاحية<br>
                        <span style="font-size:7px;">Office National du Conseil Agricole</span>
                    </div>
                @endif
            </td>
            <td style="width:52%; text-align:center; vertical-align:middle; padding:0 8px;">
                <div class="header-center">
                    Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
                </div>
            </td>
            <td style="width:24%; text-align:right; vertical-align:middle;">
                @if($sceauMarocSrc)
                    <img src="{{ $sceauMarocSrc }}" style="height:68px; width:auto;" alt="Royaume du Maroc">
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
            <td style="width:42%; font-weight:bold; background:#f2f2f2;">Budget</td>
            <td style="width:58%; font-weight:bold; text-align:center;">{{ $typeBudget }}</td>
        </tr>
        <tr>
            <td style="font-weight:bold; background:#f2f2f2;">Exercice</td>
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
            <td class="value-col">{{ $formeEngagement }}</td>
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
            <td class="flabel">Montant de la dépense en dirhams</td>
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
            <td class="flabel" style="text-align:center;">En lettres :</td>
            <td class="fval" style="font-style:italic;">{{ $montantEnLettres }}</td>
        </tr>
    </table>

    <!-- ══════════ TABLEAU 4 : PIÈCES JOINTES & VISA ══════════ -->
    <table class="visa-box">
        <tr>
            <td colspan="2" style="background:#f5f5f5; padding:7px 10px;">
                <span style="font-weight:bold;">Pièces jointes :</span><br>
                <span style="margin-left:10px; font-weight:bold;">{{ $piecesJointes }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="background:#e5e0d8; text-align:center; font-weight:bold; font-size:12px; padding:6px; letter-spacing:0.5px;">
                VISA DU SOUS-ORDONNATEUR
            </td>
        </tr>
        <tr>
            <td colspan="2" style="height:125px; vertical-align:top; border-bottom:none;">
                <!-- Zone réservée au visa, signature et cachet -->
            </td>
        </tr>
        <tr>
            <td style="width:45%; font-weight:bold; text-align:center; border-top:1px solid #000; background:#f5f5f5;">Date :</td>
            <td style="width:55%; font-weight:bold; text-align:center; border-top:1px solid #000;">{{ $dateEngagement }}</td>
        </tr>
    </table>

</body>
</html>
