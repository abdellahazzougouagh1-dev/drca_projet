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
        * {
            box-sizing: border-box;
        }
        body { 
            font-family: "DejaVu Sans", "Helvetica", Arial, sans-serif; 
            font-size: 11px; 
            line-height: 1.25; 
            margin: 0; 
            padding: 0;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .header-table td {
            vertical-align: middle;
            padding: 0;
        }
        
        .budget-box {
            width: 250px;
            border-collapse: collapse;
            margin-left: auto;
            margin-bottom: 5px;
        }
        .budget-box td {
            border: 1.5px solid #000;
            padding: 4px 8px;
            font-size: 11px;
        }
        
        .main-title-container {
            width: 100%;
            margin-top: 5px;
            margin-bottom: 12px;
            position: relative;
        }
        .num-fe {
            font-size: 11px;
            font-weight: bold;
            text-align: left;
            margin-bottom: 2px;
            padding-left: 50px;
        }
        .main-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 0.5px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border: 1.5px solid #000;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 5px 6px;
            font-size: 10.5px;
        }
        
        .rubrique-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border: 1.5px solid #000;
            text-align: center;
        }
        .rubrique-table th, .rubrique-table td {
            border: 1px solid #000;
            padding: 4px 3px;
            font-size: 9.5px;
        }
        .rubrique-table th {
            font-weight: bold;
            background-color: #fff;
        }
        
        .visa-box {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            margin-top: 5px;
        }
        .visa-box td {
            border: 1px solid #000;
            padding: 5px 8px;
        }
    </style>
</head>
<body>

    @php
        $logoOncaPath = public_path('images/logo-onca.png');
        $logoOncaSrc = file_exists($logoOncaPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoOncaPath)) : '';
        
        $sceauMarocPath = public_path('images/sceau-maroc.png');
        $sceauMarocSrc = file_exists($sceauMarocPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($sceauMarocPath)) : '';

        $montantDepense = (float) ($marche->montant ?? 0);
        $interetMoratoire = round($montantDepense * 0.01, 2);
        $montantTotal = round($montantDepense + $interetMoratoire, 2);

        $montantEnLettres = \App\Helpers\NumberToWordsHelper::toFrenchMoneyWords($montantTotal);
        
        $numEngagement = $marche->num_engagement ?: ($marche->id . '/' . ($marche->exercice ?: date('Y')) . '/FE/DRCA-RSK');
        $refEngagement = $marche->reference_engagement ?: ($marche->num_marche ? (stripos($marche->num_marche, 'marché') !== false ? $marche->num_marche : 'Marché N° ' . $marche->num_marche) : 'Marché N° 06/' . ($marche->exercice ?: date('Y')) . '/DRCA-RSK');
        $formeEngagement = ($marche->forme_engagement && !in_array(strtolower(trim($marche->forme_engagement)), ['bon de commande', 'bc', ''])) ? $marche->forme_engagement : 'Marché';
        $typeBudget = $marche->type_budget ?: 'Investissement';
        $exercice = $marche->exercice ?: date('Y');

        $nl = $marche->notificationLigne ?? ($marche->aoo->notificationLigne ?? null);

        $article = $nl ? $nl->article : ($marche->article_budget ?: '415');
        $paragraphe = $nl ? $nl->paragraphe : ($marche->paragraphe_budget ?: '20');
        $ligne = $nl ? $nl->ligne_budgetaire : ($marche->ligne_budget ?: '16');

        $creditCp = $nl ? ((float)($nl->reports ?? 0) + (float)($nl->credits_neufs ?? 0)) : ($marche->credit_budget_cp !== null ? $marche->credit_budget_cp : null);
        $creditCe = $nl ? ((float)($nl->credits_engagements ?? 0)) : ($marche->credit_budget_ce !== null ? $marche->credit_budget_ce : null);

        $depensesCp = $nl ? ((float)($nl->credits_engages ?? 0)) : ($marche->depenses_engagees_cp !== null ? $marche->depenses_engagees_cp : null);
        $depensesCe = $marche->depenses_engagees_ce !== null ? $marche->depenses_engagees_ce : null;
        
        $disponibleCp = $nl ? ((float)($nl->credits_disponibles ?? 0)) : ($creditCp !== null && $depensesCp !== null ? ($creditCp - $depensesCp) : null);
        $disponibleCe = $marche->disponible_ce !== null ? $marche->disponible_ce : null;
        
        $engagementProposeCp = $marche->engagement_propose_cp !== null ? $marche->engagement_propose_cp : $montantTotal;
        $engagementProposeCe = $marche->engagement_propose_ce;

        $piecesJointes = $marche->pieces_jointes ?: $refEngagement;
        $dateEngagement = $marche->date_engagement ? \Carbon\Carbon::parse($marche->date_engagement)->format('d/m/Y') : ($marche->date_signature ? \Carbon\Carbon::parse($marche->date_signature)->format('d/m/Y') : date('d/m/Y'));
    @endphp

    <!-- HEADER LOGOS & TITRE REGIONAL -->
    <table class="header-table">
        <tr>
            <td style="width: 25%; text-align: left; vertical-align: middle;">
                @if($logoOncaSrc)
                    <img src="{{ $logoOncaSrc }}" style="height: 62px; width: auto; max-width: 150px;" alt="ONCA">
                @else
                    <div style="font-size: 8px; font-weight: bold; line-height: 1.3;">
                        المكتب الوطني للإستشارة الفلاحية<br>
                        <span style="font-size: 7.5px;">Office National du Conseil Agricole</span>
                    </div>
                @endif
            </td>
            <td style="width: 50%; text-align: center; vertical-align: middle; padding: 0 5px;">
                <div style="font-size: 13px; font-weight: bold; text-transform: uppercase; line-height: 1.35; color: #000; letter-spacing: 0.3px;">
                    Direction Régionale du Conseil Agricole<br>
                    <span style="font-size: 13.5px; letter-spacing: 0.5px;">RABAT SALE KENITRA</span>
                </div>
            </td>
            <td style="width: 25%; text-align: right; vertical-align: middle;">
                @if($sceauMarocSrc)
                    <img src="{{ $sceauMarocSrc }}" style="height: 62px; width: auto; max-width: 150px;" alt="Royaume du Maroc">
                @else
                    <div style="font-size: 8px; font-weight: bold; line-height: 1.3;">
                        المملكة المغربية<br>
                        <span style="font-size: 7px;">وزارة الفلاحة والصيد البحري</span>
                    </div>
                @endif
            </td>
        </tr>
    </table>
    <div style="border-bottom: 1.5px solid #000; margin-top: 4px; margin-bottom: 10px;"></div>

    <!-- CADRE BUDGET / EXERCICE -->
    <table class="budget-box">
        <tr>
            <td style="width: 35%; font-weight: bold;">Budget</td>
            <td style="width: 65%; font-weight: bold; text-align: center;">{{ $typeBudget }}</td>
        </tr>
        <tr>
            <td style="width: 35%; font-weight: bold;">Exercice</td>
            <td style="width: 65%; font-weight: bold; text-align: center;">{{ $exercice }}</td>
        </tr>
    </table>

    <!-- TITRE PRINCIPAL -->
    <div class="main-title-container">
        <div class="num-fe">N° &nbsp;&nbsp; {{ $numEngagement }}</div>
        <div class="main-title">FICHE D'ENGAGEMENT</div>
    </div>

    <!-- TABLEAU 1 : IDENTIFICATION DE L'ENGAGEMENT -->
    <table class="data-table">
        <tr>
            <td style="width: 28%; font-weight: bold;">Référence de l'engagement</td>
            <td style="width: 72%; text-align: center; font-weight: bold;">{{ $refEngagement }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; text-align: center;">Objet</td>
            <td style="text-align: center; font-weight: bold; text-transform: uppercase;">
                {{ $marche->objet_marche ?? ($marche->aoo->objet ?? '') }}
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold; text-align: center;">Forme</td>
            <td style="text-align: center; font-weight: bold;">{{ $formeEngagement }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; text-align: center;">Montant en dirhams</td>
            <td style="text-align: center; font-weight: bold; font-size: 11.5px;">
                {{ number_format($montantDepense, 2, ',', ' ') }}
            </td>
        </tr>
    </table>

    <!-- TABLEAU 2 : RUBRIQUE BUDGÉTAIRE -->
    <table class="rubrique-table">
        <thead>
            <tr>
                <th colspan="2" style="width: 22%;">Rubrique budgétaire</th>
                <th colspan="2" style="width: 20%;">Crédit budgétaire</th>
                <th colspan="2" style="width: 20%;">Dépenses engagées</th>
                <th colspan="2" style="width: 19%;">Disponible</th>
                <th colspan="2" style="width: 19%;">Engagement de la dépense proposée</th>
            </tr>
            <tr>
                <th style="width: 13%;"></th>
                <th style="width: 9%;"></th>
                <th style="width: 10%;">CP</th>
                <th style="width: 10%;">CE</th>
                <th style="width: 10%;">CP</th>
                <th style="width: 10%;">CE</th>
                <th style="width: 10%;">CP</th>
                <th style="width: 9%;">CE</th>
                <th style="width: 10%;">CP</th>
                <th style="width: 9%;">CE</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-weight: bold; text-align: left;">ARTICLE</td>
                <td style="font-weight: bold;">{{ $article }}</td>
                <td rowspan="3" style="font-weight: bold; vertical-align: middle;">
                    {{ $creditCp !== null ? number_format((float)$creditCp, 2, ',', ' ') : '-' }}
                </td>
                <td rowspan="3" style="font-weight: bold; vertical-align: middle;">
                    {{ $creditCe !== null && $creditCe > 0 ? number_format((float)$creditCe, 2, ',', ' ') : '-' }}
                </td>
                <td rowspan="3" style="font-weight: bold; vertical-align: middle;">
                    {{ $depensesCp !== null ? number_format((float)$depensesCp, 2, ',', ' ') : '-' }}
                </td>
                <td rowspan="3" style="font-weight: bold; vertical-align: middle;">
                    {{ $depensesCe !== null && $depensesCe > 0 ? number_format((float)$depensesCe, 2, ',', ' ') : '-' }}
                </td>
                <td rowspan="3" style="font-weight: bold; vertical-align: middle;">
                    {{ $disponibleCp !== null ? number_format((float)$disponibleCp, 2, ',', ' ') : '-' }}
                </td>
                <td rowspan="3" style="font-weight: bold; vertical-align: middle;">
                    {{ $disponibleCe !== null && $disponibleCe > 0 ? number_format((float)$disponibleCe, 2, ',', ' ') : '-' }}
                </td>
                <td rowspan="3" style="font-weight: bold; vertical-align: middle;">
                    {{ $engagementProposeCp !== null ? number_format((float)$engagementProposeCp, 2, ',', ' ') : number_format($montantTotal, 2, ',', ' ') }}
                </td>
                <td rowspan="3" style="font-weight: bold; vertical-align: middle;">
                    {{ $engagementProposeCe !== null && $engagementProposeCe > 0 ? number_format((float)$engagementProposeCe, 2, ',', ' ') : '-' }}
                </td>
            </tr>
            <tr>
                <td style="font-weight: bold; text-align: left;">PARAGRAPHE</td>
                <td style="font-weight: bold;">{{ $paragraphe }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold; text-align: left;">LIGNE</td>
                <td style="font-weight: bold;">{{ $ligne }}</td>
            </tr>
        </tbody>
    </table>

    <!-- TABLEAU 3 : BÉNÉFICIAIRE & CALCULS FINANCIERS -->
    <table class="data-table">
        <tr>
            <td style="width: 35%; font-weight: bold;">Bénéficiaire</td>
            <td style="width: 65%; text-align: center; font-weight: bold; text-transform: uppercase;">
                {{ $marche->fournisseur->raison_sociale ?? ($marche->titulaire ?? '-') }}
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Montant de la depense en dirhams</td>
            <td style="text-align: center; font-weight: bold;">
                {{ number_format($montantDepense, 2, ',', ' ') }}
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold;">
                Somme à valoir pour intérêt moratoire<br>
                <span style="font-size: 10px; font-weight: normal;">(1% du montant en dirhams)</span>
            </td>
            <td style="text-align: center; font-weight: bold;">
                {{ number_format($interetMoratoire, 2, ',', ' ') }}
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Montant total de l'engagement en dirhams</td>
            <td style="text-align: center; font-weight: bold; font-size: 11.5px;">
                {{ number_format($montantTotal, 2, ',', ' ') }}
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold; text-align: center;">En Lettre</td>
            <td style="text-align: center; font-weight: bold; font-style: italic;">
                {{ $montantEnLettres }}
            </td>
        </tr>
    </table>

    <!-- TABLEAU 4 : PIÈCES JOINTES & VISA DU SOUS-ORDONNATEUR -->
    <table class="visa-box">
        <tr>
            <td colspan="2" style="background-color: #fafafa; padding: 4px 8px;">
                <span style="font-weight: bold;">Pièces jointes :</span><br>
                <span style="font-weight: bold; margin-left: 10px;">{{ $piecesJointes }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: center; font-weight: bold; font-size: 12px; padding: 6px; letter-spacing: 0.5px;">
                VISA DU SOUS-ORDONNATEUR
            </td>
        </tr>
        <tr>
            <td colspan="2" style="height: 95px; vertical-align: top; border-bottom: none;">
                <!-- Zone réservée au visa, signature et cachet -->
            </td>
        </tr>
        <tr>
            <td style="width: 45%; font-weight: bold; text-align: center; border-top: 1px solid #000;">
                Date:
            </td>
            <td style="width: 55%; font-weight: bold; text-align: center; border-top: 1px solid #000;">
                {{ $dateEngagement }}
            </td>
        </tr>
    </table>

</body>
</html>
