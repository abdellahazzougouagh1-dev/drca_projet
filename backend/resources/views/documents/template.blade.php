<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $docTitle }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 18px 34px 96px 34px;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12.5px;
            color: #000;
            line-height: 1.48;
            margin: 0;
            padding: 0;
        }
        .header-table {
            margin-bottom: 4px;
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-center {
            text-align: center;
            font-size: 13.5px;
            font-weight: bold;
            line-height: 1.35;
            padding: 0 8px;
        }
        .blue-line {
            border-bottom: 2px solid #000080;
            margin-bottom: 16px;
        }
        .doc-title {
            font-size: 15.5px;
            font-weight: bold;
            text-align: center;
            margin: 8px auto 18px auto;
            text-transform: uppercase;
            color: #000;
            border: 2.5px solid #000;
            padding: 9px 14px;
            width: 78%;
            background: #fff;
        }
        .section-title {
            font-size: 12.5px;
            font-weight: bold;
            border-bottom: 1.5px solid #000;
            padding-bottom: 4px;
            margin-top: 18px;
            margin-bottom: 10px;
            color: #000;
            text-transform: uppercase;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            border: 1.5px solid #000;
        }
        .data-table th, .data-table td {
            padding: 8px 10px;
            border: 1px solid #000;
            text-align: left;
            font-size: 12px;
        }
        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #000;
        }
        .kv-table th {
            width: 34%;
        }
    </style>
    @include('documents.partials.bc_typography')
</head>
<body>
    @include('documents.partials.bc_footer')

    <table class="header-table">
        <tr>
            <td style="width: 24%; text-align: left;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo-onca.png'))) }}" alt="Logo ONCA" style="height: 68px; width: auto;">
            </td>
            <td class="header-center" style="width: 52%;">
                Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
            </td>
            <td style="width: 24%; text-align: right;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) }}" alt="Sceau Maroc" style="height: 68px; width: auto;">
            </td>
        </tr>
    </table>
    <div class="blue-line"></div>

    <div class="doc-title">
        {{ $docTitle }}
    </div>

    <div class="section-title">Informations de la Consultation</div>
    <table class="data-table kv-table">
        <tr>
            <th>N° Consultation</th>
            <td>{{ $consultation->numero_consultation }}</td>
        </tr>
        <tr>
            <th>Objet</th>
            <td>{{ $consultation->objet_consultation }}</td>
        </tr>
        <tr>
            <th>Type</th>
            <td>{{ $consultation->categorie }} - {{ $consultation->type_prestation }} ({{ $consultation->mode_engagement }})</td>
        </tr>
        <tr>
            <th>Date Création</th>
            <td>{{ \Carbon\Carbon::parse($consultation->created_at)->format('d/m/Y') }}</td>
        </tr>
    </table>

    <div class="section-title">Informations Financières</div>
    <table class="data-table kv-table">
        <tr>
            <th>Ligne Budgétaire</th>
            <td>
                ART {{ $consultation->budget->art ?? '' }} /
                PAR {{ $consultation->budget->par ?? '' }} /
                LIG {{ $consultation->budget->lig ?? '' }}
                - Code {{ $consultation->budget->code_imputation ?? '' }}
                (Exercice {{ $consultation->budget->exercice_budgetaire ?? '' }})
            </td>
        </tr>
        <tr>
            <th>Montant Estimatif</th>
            <td>{{ number_format($consultation->budget->montant_ttc ?? 0, 2, ',', ' ') }} MAD TTC</td>
        </tr>
        @if($consultation->engagement)
        <tr>
            <th>Prestataire Retenu</th>
            <td>{{ $consultation->engagement->fournisseur->raison_sociale }}</td>
        </tr>
        <tr>
            <th>Montant Engagé</th>
            <td>{{ number_format($consultation->engagement->montant_valide, 2, ',', ' ') }} MAD</td>
        </tr>
        @endif
    </table>

    @if($consultation->prestations && count($consultation->prestations) > 0)
        <div class="section-title">Prestations</div>
        <table class="data-table">
            <tr>
                <th>Désignation</th>
                <th>Unité</th>
                <th>Quantité</th>
                <th>Prix unitaire HT</th>
                <th>Montant TTC</th>
            </tr>
            @foreach($consultation->prestations as $prestation)
                <tr>
                    <td>{{ $prestation->designation }}</td>
                    <td>{{ $prestation->unite }}</td>
                    <td>{{ $prestation->quantite }}</td>
                    <td>{{ number_format($prestation->prix_unitaire_ht, 2, ',', ' ') }}</td>
                    <td>{{ number_format($prestation->montant_ttc, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if($type === 'pv_reception' && $consultation->receptions && count($consultation->receptions) > 0)
        <div class="section-title">Réceptions</div>
        @foreach($consultation->receptions as $reception)
            <table class="data-table">
                <tr>
                    <th>Type</th>
                    <td>{{ $reception->type_reception }}</td>
                </tr>
                <tr>
                    <th>Date Réunion</th>
                    <td>{{ $reception->date_reunion }}</td>
                </tr>
                <tr>
                    <th>Conformité</th>
                    <td>{{ $reception->conformite }}</td>
                </tr>
            </table>
        @endforeach
    @endif

    <div style="margin-top: 32px; text-align: right; padding-right: 30px; font-weight: bold;">
        <p style="font-weight: normal; font-size: 11px; text-align: left;">Document généré électroniquement le {{ date('d/m/Y à H:i') }}.</p>
        <p style="margin-top: 14px;">Signature &amp; Cachet :</p>
    </div>

</body>
</html>
