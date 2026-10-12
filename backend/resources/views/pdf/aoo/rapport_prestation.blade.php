<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Rapport de Présentation</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #000;
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }

        .page-wrapper {
            margin: 15px;
            padding: 15px;
            min-height: 950px;
        }

        /* --- En-tête (Logos et Titres) --- */
        .header-table {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
        }

        /* --- Titre Principal --- */
        .main-title-container {
            text-align: center;
            margin: 20px 0;
        }
        .main-title {
            display: inline-block;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* --- Structure des Données --- */
        .section-title {
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 5px;
        }
        
        .indent {
            padding-left: 20px;
        }
        
        .bold {
            font-weight: bold;
        }
        
        .text-center {
            text-align: center;
        }

        /* --- Tableau budget --- */
        .budget-table {
            width: 50%;
            border-collapse: collapse;
            margin: 10px auto;
            border: 2px solid #000;
        }
        .budget-table td {
            border: 1px solid #000;
            padding: 5px;
            font-weight: bold;
        }
        .budget-table td:first-child {
            width: 50%;
        }
        .budget-table td:last-child {
            text-align: right;
        }

        .signature-block {
            width: 100%;
            margin-top: 40px;
            font-weight: bold;
        }
        
        .list-item {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

    <div class="page-wrapper">
        <div class="header-table">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 25%; text-align: left; vertical-align: top;">
                        @if(file_exists(public_path('images/logo-onca.png')))
                            <img src="{{ public_path('images/logo-onca.png') }}" style="height: 80px; width: auto;" alt="ONCA">
                        @endif
                    </td>
                    <td style="width: 50%; text-align: center; vertical-align: middle;" class="header-title">
                        <div style="font-size: 16px; font-weight: bold; color: #000; font-family: serif;">
                            DIRECTION REGIONALE DU CONSEIL AGRICOLE RABAT-SALE-KENITRA
                        </div>
                    </td>
                    <td style="width: 25%; text-align: right; vertical-align: top;">
                        @if(file_exists(public_path('images/sceau-maroc.png')))
                            <img src="{{ public_path('images/sceau-maroc.png') }}" style="height: 80px; width: auto;" alt="Royaume du Maroc">
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="main-title-container">
            <div class="main-title">
                RAPPORT DE PRESENTATION
            </div>
        </div>
        
        @php
            $numMarche = $marche && $marche->num_marche ? $marche->num_marche : null;
            if (!$numMarche && $aoo->num_aoo) {
                $parts = explode('/', $aoo->num_aoo);
                if (count($parts) >= 2 && is_numeric($parts[0])) {
                    $parts[0] = str_pad((int)$parts[0] + 1, 2, '0', STR_PAD_LEFT);
                    $numMarche = implode('/', $parts);
                } else {
                    $numMarche = '........ /' . date('Y') . '/DRCA-RSK';
                }
            }
            $delaiExecution = $marche && $marche->delai_execution ? $marche->delai_execution : '12';
            $nomAttributaire = $attributaire ? $attributaire->nom_soumissionnaire : '.............................................';
            $adresseAttributaire = ($attributaire && $attributaire->fournisseur) ? $attributaire->fournisseur->adresse : '.............................................';
            $dateOuverture = $aoo->date_ouverture ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') : '..../..../........';
            $budgetArt = $aoo->art ?? '...';
            $budgetPar = $aoo->par ?? '...';
            $budgetLig = $aoo->lig ?? '...';
        @endphp

        <div style="margin-bottom: 15px;">
            <span class="section-title">1-Marché N° : </span> <span class="bold">{{ $numMarche }}</span><br>
            <div class="indent" style="margin-top: 10px;">
                Issue de l'AOO n°{{ $aoo->num_aoo }} du {{ $dateOuverture }}.
            </div>
        </div>

        <div class="section-title">2-Attributaire du marché : <span style="font-weight: normal">{{ mb_strtoupper($nomAttributaire) }}</span></div>
        <div class="indent" style="margin-bottom: 15px;">
            Adresse: {{ $adresseAttributaire }}
        </div>

        <div style="margin-bottom: 15px;">
            <span class="section-title">3-Objet du marché : </span> {{ $aoo->objet }}
        </div>

        <div style="margin-bottom: 15px;">
            <span class="section-title">4-Mode de passation du marché : </span> appel d’offres ouvert sur offres de prix.
        </div>

        <div style="margin-bottom: 15px;">
            <span class="section-title">5-Motifs ayant déterminé le choix du mode de passation : </span> prestations courantes.
        </div>

        <div class="section-title">6-Exposé de l'économie générale du marché :</div>
        
        <div style="margin-bottom: 5px;" class="indent">
            <span class="section-title">6-1- Forme du marché : </span> marché en lot unique.
        </div>
        
        <div style="margin-bottom: 5px;" class="indent">
            <span class="section-title">6-2- Montant de l'estimation du maitre d'ouvrage : </span> {{ number_format($estimationTTC, 2, ',', ' ') }} Dhs TTC
        </div>
        
        <div style="margin-bottom: 5px;" class="indent">
            <span class="section-title">6-3- Montant du marché : </span> {{ number_format($montant, 2, ',', ' ') }} DH
        </div>
        
        <div style="margin-bottom: 5px;" class="indent">
            <span class="section-title">6-4- Caractère des prix : </span> prix fermes et non révisables.
        </div>
        
        <div style="margin-bottom: 15px;" class="indent">
            <span class="section-title">6-5- Délai d'exécution : </span> {{ $delaiExecution }} mois.
        </div>

        <div class="section-title indent">6-6- Imputation budgétaire :</div>
        <table class="budget-table">
            <tr>
                <td>Budget</td>
                <td style="text-align: center;">Investissement</td>
            </tr>
            <tr>
                <td>Article</td>
                <td style="text-align: center;">{{ $budgetArt }}</td>
            </tr>
            <tr>
                <td>Paragraphe</td>
                <td style="text-align: center;">{{ $budgetPar }}</td>
            </tr>
            <tr>
                <td>Ligne</td>
                <td style="text-align: center;">{{ $budgetLig }}</td>
            </tr>
        </table>

        <div class="section-title" style="margin-top: 20px;">7-Justification du choix des critères de sélection des candidatures et de jugement des offres : <span style="font-weight: normal">dossiers administratifs et techniques ainsi que offres financières.</span></div>

        <div class="section-title" style="margin-top: 15px;">8-Justification du choix de l'attributaire : <span style="font-weight: normal">Offre avantageuse.</span></div>

        <div style="text-align: center; margin-top: 30px; font-weight: bold; font-size: 14px;">
            Fait à Kenitra, le &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ date('d/m/Y') }}
        </div>
        <div style="text-align: center; margin-top: 20px; font-weight: bold; font-size: 14px;">
            Signature et cachet du maître d’ouvrage
        </div>

    </div>
</body>
</html>
