<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bon de commande</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #000;
            margin: 10px;
            line-height: 1.3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
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
            font-size: 14px;
            font-weight: bold;
        }
        .blue-line {
            border-bottom: 2px solid #000080;
            margin-bottom: 20px;
        }
        .top-right-info {
            text-align: right;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 13px;
        }
        .title-box {
            border: 2px solid #000;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            padding: 5px;
            width: 80%;
            margin: 0 auto 20px auto;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-grid td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: top;
        }
        .info-grid .label {
            font-weight: bold;
            width: 15%;
        }
        .info-grid .imputation-label {
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        .imputation-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            padding: 0;
        }
        .imputation-table td {
            border: none;
            border-bottom: 1px solid #000;
            border-left: 1px solid #000;
            text-align: center;
            padding: 3px;
        }
        .imputation-table tr:last-child td {
            border-bottom: none;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
        }
        .data-table th {
            font-weight: bold;
            background-color: #e6eed5; /* Vert très clair style Excel */
        }
        .data-table .col-left {
            text-align: left;
        }
        .totals-table {
            width: 350px;
            margin-left: 20%;
            margin-bottom: 15px;
            border: 2px solid #000;
            border-collapse: collapse;
        }
        .totals-table td {
            border: 1px solid #000;
            padding: 5px 10px;
        }
        .totals-table .label {
            font-weight: bold;
            text-align: left;
        }
        .totals-table .amount {
            text-align: right;
            font-weight: bold;
        }
        .amount-words-box {
            margin-bottom: 30px;
            font-weight: bold;
        }
        .signature-box {
            text-align: right;
            font-weight: bold;
            margin-bottom: 40px;
            padding-right: 50px;
        }
        .date-box {
            border: 1px solid #000;
            padding: 5px 10px;
            width: 250px;
            font-weight: bold;
        }
    </style>
</head>
<body>

@php
    // Identification du fournisseur gagnant de la consultation
    $fournisseur = null;
    
    // 1. Chercher dans l'engagement (Phase d'attribution officielle)
    if(isset($consultation->engagement) && $consultation->engagement->fournisseur) {
        $fournisseur = $consultation->engagement->fournisseur;
    } 
    // 2. Sinon, chercher dans les offres (celui qui a été 'retenu')
    elseif(isset($consultation->offres)) {
        $gagnant = $consultation->offres->where('retenu', true)->first();
        if($gagnant) $fournisseur = $gagnant->fournisseur;
    }
    
    // Fallback visuel si aucun fournisseur n'est encore lié
    if(!$fournisseur) {
        $fournisseur = (object)[
            'raison_sociale' => '................................',
            'adresse' => '................................',
            'patente' => '............',
            'ice' => '........................',
            'cnss' => '............',
            'rib' => '........................................'
        ];
    }
    
    // Formatage intelligent du numéro de Bon de Commande (Remplacement "Consultation" par "BC" ou ajout du préfixe)
    $numeroBC = str_replace('Consultation', 'BC', $consultation->numero_consultation);
    if(strpos($numeroBC, 'BC') === false) {
        $numeroBC = 'BC ' . $numeroBC;
    }

    // Récupération des articles (prestations) de la DB
    $articles = isset($consultation->prestations) && count($consultation->prestations) > 0 
                ? $consultation->prestations 
                : collect([]);
    
    // Calcul dynamique des totaux
    $totalHT = 0;
    foreach($articles as $article) {
        $totalHT += ($article->quantite ?? 1) * ($article->prix_unitaire_ht ?? 0);
    }
    
    // Fallback de calcul si pas d'articles détaillés
    if($totalHT == 0 && isset($consultation->montant_estime)) {
        $totalHT = $consultation->montant_estime;
    }
    
    $tva = $totalHT * 0.20;
    $totalTTC = $totalHT + $tva;
@endphp

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

    <div class="top-right-info">
        Exercice {{ $consultation->annee ?? date('Y') }}<br>
        Budget {{ $consultation->type_budget ?? 'Investissement' }}
    </div>

    <div class="title-box">
        Bon de commande N° : {{ $numeroBC }}
    </div>

    <table class="info-grid">
        <tr>
            <td class="label">Objet</td>
            <td style="width: 45%;">{{ $consultation->objet_consultation }}</td>
            <td class="imputation-label" style="width: 15%;" rowspan="2">Imputation</td>
            <td style="width: 25%; padding: 0;" rowspan="2">
                <table class="imputation-table">
                    <tr>
                        <td style="width: 40%; border-left: none;">ART</td>
                        <td style="width: 60%;">415</td>
                    </tr>
                    <tr>
                        <td style="border-left: none;">PAR</td>
                        <td>10</td>
                    </tr>
                    <tr>
                        <td style="border-left: none;">LIG</td>
                        <td>42</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="label">Titulaire</td>
            <td>{{ $fournisseur->raison_sociale }}</td>
        </tr>
        <tr>
            <td class="label">Adresse</td>
            <td>{{ $fournisseur->adresse }}</td>
            <td colspan="2" style="text-align: center; font-weight: bold; text-decoration: underline;">Intitulé</td>
        </tr>
        <tr>
            <td class="label">Patente</td>
            <td>{{ $fournisseur->patente ?? '................' }}</td>
            <td colspan="2" style="text-align: center;" rowspan="2">{{ $consultation->objet_consultation }}</td>
        </tr>
        <tr>
            <td class="label">CNSS</td>
            <td>{{ $fournisseur->cnss ?? '................' }}</td>
        </tr>
        <tr>
            <td class="label">ICE</td>
            <td>"{{ $fournisseur->ice ?? '................' }}"</td>
            <td colspan="2" style="text-align: center; font-weight: bold; text-decoration: underline;">Prestation de même nature</td>
        </tr>
        <tr>
            <td class="label">RIB</td>
            <td>{{ $fournisseur->rib ?? '........................................' }}</td>
            <td colspan="2" style="text-align: center;">{{ $consultation->objet_consultation }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th colspan="2" style="text-align: left; background-color: transparent; border-right: none; border-top: none;">Détail du bon de commande</th>
                <th colspan="4" style="background-color: transparent; border-top: none; border-left: none; border-right: none;"></th>
            </tr>
            <tr>
                <th style="width: 5%;">N°</th>
                <th style="width: 50%;">Désignation</th>
                <th style="width: 10%;">Unité</th>
                <th style="width: 10%;">Qt</th>
                <th style="width: 10%;">P.U HT</th>
                <th style="width: 15%;">Montant HT</th>
            </tr>
        </thead>
        <tbody>
            @if(count($articles) > 0)
                @foreach($articles as $key => $article)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td class="col-left">{{ $article->designation }}</td>
                    <td>{{ $article->unite ?? 'Unité' }}</td>
                    <td>{{ $article->quantite ?? 1 }}</td>
                    <td style="text-align: right;">{{ number_format($article->prix_unitaire_ht ?? 0, 2, ',', ' ') }}</td>
                    <td style="text-align: right;">{{ number_format(($article->quantite ?? 1) * ($article->prix_unitaire_ht ?? 0), 2, ',', ' ') }}</td>
                </tr>
                @endforeach
            @else
                <!-- Ligne de secours si aucun article n'est défini -->
                <tr>
                    <td>1</td>
                    <td class="col-left">{{ $consultation->objet_consultation }}</td>
                    <td>Forfait</td>
                    <td>1</td>
                    <td style="text-align: right;">{{ number_format($totalHT, 2, ',', ' ') }}</td>
                    <td style="text-align: right;">{{ number_format($totalHT, 2, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td class="col-left"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="label">Total Hors Taxe</td>
            <td class="amount">{{ number_format($totalHT, 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <td class="label">Montant TVA (20%)</td>
            <td class="amount">{{ number_format($tva, 2, ',', ' ') }}</td>
        </tr>
        <tr>
            <td class="label">Total TTC</td>
            <td class="amount">{{ number_format($totalTTC, 2, ',', ' ') }}</td>
        </tr>
    </table>

    <div class="amount-words-box">
        <span style="display:inline-block; width: 60%; text-align: center;">Le présent Bon de Commande est arrêté à la somme de :</span>
        <span style="display:inline-block; width: 25%; text-align: right;">{{ number_format($totalTTC, 2, ',', ' ') }}</span>
        <span style="display:inline-block; width: 10%; text-align: center;">Dh</span>
        <br><br>
        En lettre : <span style="font-weight: normal; margin-left: 20px;">{{ $montantEnLettres ?? '#NOM?' }}</span>
    </div>

    <div class="signature-box">
        Le Sous Ordonnateur
    </div>

    <div class="date-box">
        Date: <span style="float: right;">{{ \Carbon\Carbon::parse($consultation->date_consultation ?? now())->format('d/m/Y') }}</span>
    </div>

</body>
</html>
