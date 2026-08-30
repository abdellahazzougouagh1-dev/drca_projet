<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $docTitle }}</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12px; color: #333; line-height: 1.5; margin: 30px; }
        .header-table {
            margin-bottom: 5px;
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-center {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
        }
        .blue-line {
            border-bottom: 2px solid #000080;
            margin-bottom: 20px;
        }
        .doc-title { font-size: 14px; font-weight: bold; text-align: center; margin: 30px 0; text-transform: uppercase; color: #222; border: 1px solid #ccc; padding: 15px; background: #f9f9f9;}
        .section-title { font-size: 12px; font-weight: bold; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 30px; margin-bottom: 15px; color: #0056b3; }
        .data-table { w-full: 100%; width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th, .data-table td { padding: 10px; border: 1px solid #eee; text-align: left; }
        .data-table th { background-color: #f5f7fa; width: 35%; font-weight: bold; color: #444; }
        .footer { position: fixed; bottom: -10px; left: 0; right: 0; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 25%;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo-onca.png'))) }}" alt="Logo ONCA" style="height: 70px; width: auto;">
            </td>
            <td class="header-center" style="width: 50%;">
                Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
            </td>
            <td style="width: 25%; text-align: right;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) }}" alt="Sceau Maroc" style="height: 70px; width: auto;">
            </td>
        </tr>
    </table>
    <div class="blue-line"></div>

    <div class="doc-title">
        {{ $docTitle }}
    </div>

    <div class="section-title">Informations de la Consultation</div>
    <table class="data-table">
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
    <table class="data-table">
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
                <th>DÃ©signation</th>
                <th>UnitÃ©</th>
                <th>QuantitÃ©</th>
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

    <div style="margin-top: 50px;">
        <p>Document généré électroniquement le {{ date('d/m/Y à H:i') }}.</p>
        <p>Signature & Cachet :</p>
    </div>

    <div class="footer">
        Dossier Administratif - ONCA ERP - Document de travail interne
    </div>

</body>
</html>
