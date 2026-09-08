<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>État de Liquidation - {{ $numMarche ?? 'Document' }}</title>
    <style>
        @page {
            margin: 20px 25px 20px 25px;
            size: A4 portrait;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10.5px;
            color: #000;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }

        /* HEADER */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .header-logo {
            height: 72px;
            width: auto;
            max-height: 80px;
        }
        .header-center {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            color: #111;
        }
        .header-right {
            text-align: right;
        }

        /* BUDGET & EXERCICE BOX */
        .budget-box {
            width: 100%;
            margin-bottom: 12px;
        }
        .budget-table {
            width: 42%;
            margin-left: auto;
            border-collapse: collapse;
            font-size: 10px;
        }
        .budget-table td {
            border: 1.2px solid #000;
            padding: 3px 8px;
            font-weight: bold;
        }
        .budget-table .lbl {
            text-align: center;
            width: 45%;
            background-color: #ffffff;
        }
        .budget-table .val {
            text-align: center;
            width: 55%;
        }

        /* TITRE PRINCIPAL */
        .title-banner {
            width: 100%;
            background-color: #e5e0d8;
            border: 1.2px solid #000;
            text-align: center;
            padding: 7px 0;
            margin-bottom: 12px;
        }
        .title-banner h1 {
            margin: 0;
            font-size: 13.5px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* DATE BANNER */
        .date-banner {
            width: 100%;
            border: 1.2px solid #000;
            background-color: #e5e0d8;
            text-align: center;
            font-weight: bold;
            font-size: 10.5px;
            padding: 4px 0;
            margin-bottom: 0px;
        }

        /* GENERAL TABLES */
        .table-section {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #000;
            margin-bottom: 14px;
        }
        .table-section th, .table-section td {
            border: 1.2px solid #000;
            padding: 4px 6px;
            font-size: 10px;
        }
        .section-header {
            background-color: #e5e0d8;
            text-align: center;
            font-weight: bold;
            font-size: 10.5px;
            padding: 4px 0;
        }
        .cell-label {
            font-weight: bold;
            width: 18%;
            background-color: #ffffff;
            vertical-align: top;
        }
        .cell-value {
            vertical-align: middle;
        }

        /* RETENUES TABLE */
        .table-ras {
            width: 100%;
            border-collapse: collapse;
            border: 1.2px solid #000;
            margin-bottom: 14px;
            text-align: center;
        }
        .table-ras th, .table-ras td {
            border: 1.2px solid #000;
            padding: 4px 3px;
            font-size: 9.5px;
            vertical-align: middle;
        }
        .table-ras th {
            background-color: #e5e0d8;
            font-weight: bold;
            line-height: 1.15;
        }
        .table-ras td {
            font-weight: bold;
            padding: 6px 3px;
        }

        /* VISA SIGNATURE BOX */
        .visa-container {
            width: 100%;
            border: 1.2px solid #000;
            margin-top: 4px;
        }
        .visa-header {
            background-color: #e5e0d8;
            text-align: center;
            font-weight: bold;
            font-size: 10.5px;
            padding: 4px 0;
            border-bottom: 1.2px solid #000;
        }
        .visa-body {
            height: 110px;
        }
    </style>
</head>
<body>

    <!-- 1. HEADER LOGOS & DIRECTION -->
    <table class="header-table">
        <tr>
            <td style="width: 25%; text-align: left;">
                @if(file_exists(public_path('images/logo-onca.png')))
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo-onca.png'))) }}" class="header-logo" alt="Logo ONCA">
                @endif
            </td>
            <td style="width: 50%; text-align: center;" class="header-center">
                Direction regionale du Conseil agricole Rabat-Salé-Kénitra
            </td>
            <td style="width: 25%; text-align: right;" class="header-right">
                @if(file_exists(public_path('images/sceau-maroc.png')))
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) }}" class="header-logo" alt="Royaume du Maroc">
                @endif
            </td>
        </tr>
    </table>

    <!-- 2. BUDGET & EXERCICE TABLE -->
    <div class="budget-box">
        <table class="budget-table">
            <tr>
                <td class="lbl">BUDGET</td>
                <td class="val">{{ $budget ?? 'Investissement' }}</td>
            </tr>
            <tr>
                <td class="lbl">EXERCICE :</td>
                <td class="val">{{ $exercice ?? date('Y') }}</td>
            </tr>
        </table>
    </div>

    <!-- 3. TITRE: ÉTAT DE LIQUIDATION -->
    <div class="title-banner">
        <h1>ETAT DE LIQUIDATION</h1>
    </div>

    <!-- 4. DATE -->
    <div class="date-banner">
        DATE : {{ $dateLiquidation ?? date('d/m/Y') }}
    </div>

    <!-- 5. TABLEAU: RENSEIGNEMENTS SUR L'ETAT DE LIQUIDATION -->
    <table class="table-section" style="border-top: none; margin-top: 0;">
        <tr>
            <th colspan="2" class="section-header">
                RENSEIGNEMENTS SUR L'ETAT DE LIQUIDATION
            </th>
        </tr>
        <tr>
            <td class="cell-label">Marché N°</td>
            <td class="cell-value" style="font-weight: bold;">{{ $numMarche ?? '-' }}</td>
        </tr>
        <tr>
            <td class="cell-label">Objet</td>
            <td class="cell-value" style="font-weight: bold; text-align: justify;">
                {{ $objet ?? 'Prestation / Dépense' }}
            </td>
        </tr>
        <tr>
            <td class="cell-label">Facture</td>
            <td class="cell-value" style="font-weight: bold;">
                {{ $factureRef ?? ('Facture N° ' . ($numLiquidation ?? '001') . ' du ' . ($dateLiquidation ?? date('d/m/Y'))) }}
            </td>
        </tr>
        <tr>
            <td class="cell-label">Au profit de</td>
            <td class="cell-value" style="font-weight: bold;">
                {{ $beneficiaire ?? 'Fournisseur / Prestataire' }}
            </td>
        </tr>
        <tr>
            <td class="cell-label">Montant (Dh)</td>
            <td class="cell-value" style="font-weight: bold; text-align: center;">
                {{ number_format((float)($montantTtc ?? 0), 2, ',', ' ') }}
            </td>
        </tr>
        <tr>
            <td class="cell-label">Montant en lettre</td>
            <td class="cell-value" style="font-weight: bold; text-align: center; text-transform: uppercase;">
                {{ $montantEnLettres ?? '-' }}
            </td>
        </tr>
    </table>

    <!-- 6. TABLEAU: ETAT DESCRIPTIF DE LA RETENUE A LA SOURCE / TVA -->
    <table class="table-ras">
        <thead>
            <tr>
                <th colspan="7" class="section-header">
                    Etat descriptif de la Retenue à la source/ TVA
                </th>
            </tr>
            <tr>
                <th style="width: 14%;">Montant TTC</th>
                <th style="width: 14%;">Montant HT</th>
                <th style="width: 14%;">MONTANT DE<br>LA TVA</th>
                <th style="width: 14%;">Taux de la Retenue<br>à la source</th>
                <th style="width: 15%;">Montant de la<br>retenue à la<br>source en DH</th>
                <th style="width: 15%;">MONTANT DE<br>LA RAS<br>ARRONDI AU<br>DH SUPERIEUR</th>
                <th style="width: 14%;">MT A VERSER AU<br>PRESTATAIRE</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ number_format((float)($montantTtc ?? 0), 2, ',', ' ') }}</td>
                <td>{{ number_format((float)($montantHt ?? 0), 2, ',', ' ') }}</td>
                <td>{{ number_format((float)($montantTva ?? 0), 2, ',', ' ') }}</td>
                <td>{{ $tauxRas ?? ($montantRas > 0 ? '75%' : '0%') }}</td>
                <td>{{ number_format((float)($montantRas ?? 0), 2, ',', ' ') }}</td>
                <td>{{ number_format(ceil((float)($montantRas ?? 0)), 2, ',', ' ') }}</td>
                <td>{{ number_format((float)($netAVerser ?? ($montantTtc - $montantRas)), 2, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- 7. VISA DU SOUS-ORDONNATEUR -->
    <div class="visa-container">
        <div class="visa-header">
            VISA DU SOUS-ORDONNATEUR
        </div>
        <div class="visa-body"></div>
    </div>

</body>
</html>
