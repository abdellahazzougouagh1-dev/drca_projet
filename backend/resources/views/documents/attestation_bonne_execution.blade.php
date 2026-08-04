<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Attestation de Référence</title>
    <style>
        @page { 
            size: A4; 
            margin: 20mm 15mm; 
        }
        body { 
            font-family: 'Times New Roman', Times, serif; 
            font-size: 15px; 
            color: #000; 
            line-height: 1.6; 
            margin: 0; 
            padding: 0; 
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
        
        .ref-date-table { 
            margin-bottom: 40px; 
            margin-top: 10px; 
            font-family: Arial, sans-serif; 
            font-size: 13px; 
        }
        .ref-date-table td { 
            padding: 5px; 
        }
        
        .title-container { 
            text-align: center; 
            margin-bottom: 40px; 
        }
        .title { 
            font-size: 24px; 
            font-weight: bold; 
            text-transform: uppercase;
            text-decoration: underline;
            margin-bottom: 10px;
        }
        
        /* Ornement de séparation vintage (CSS) */
        .ornament {
            text-align: center;
            font-size: 20px;
            color: #b09e5f; /* Couleur dorée discrète */
            margin-bottom: 40px;
            letter-spacing: 5px;
        }
        
        .content-table { 
            width: 100%; 
            margin-bottom: 20px; 
            border-collapse: collapse; 
        }
        .content-table td { 
            padding: 8px 5px; 
            vertical-align: top; 
        }
        .content-table .label { 
            width: 250px; 
        }
        .content-table .value { 
            font-weight: bold; 
        }
        
        .paragraph { 
            margin: 15px 0; 
            text-align: left; 
        }
        
        .signature-box { 
            margin-top: 80px; 
            margin-left: 55%;
            text-align: center; 
            font-weight: bold; 
            line-height: 1.4;
        }
    </style>
</head>
<body>

@php
    // Identification du fournisseur
    $fournisseur = null;
    if(isset($consultation->engagement) && $consultation->engagement->fournisseur) {
        $fournisseur = $consultation->engagement->fournisseur;
    } elseif(isset($consultation->offres)) {
        $gagnant = $consultation->offres->where('retenu', true)->first();
        if($gagnant) $fournisseur = $gagnant->fournisseur;
    }
    
    // Fallback visuel
    if(!$fournisseur) {
        $fournisseur = (object)[
            'raison_sociale' => '......................................................',
            'adresse' => '......................................................',
            'ville' => '........................'
        ];
    }
    
    // Format du N° d'engagement
    $typeEng = $consultation->mode_engagement === 'BC' ? 'Bon de Commande' : 'Convention';
    $numeroRef = str_replace('Consultation', $consultation->mode_engagement, $consultation->numero_consultation);
    if(strpos($numeroRef, $consultation->mode_engagement) === false) {
        $numeroRef = $consultation->mode_engagement . ' ' . $numeroRef;
    }
    
    // Dates
    $annee = $consultation->annee ?? date('Y');
    $anneePrecedente = $annee - 1;
    $dateJour = \Carbon\Carbon::parse(now())->format('d/m/Y');
    
    // Montant TTC
    $montantTotal = 0;
    if(isset($consultation->engagement->montant_engagement)) {
        $montantTotal = $consultation->engagement->montant_engagement;
    } elseif(isset($consultation->budget->montant_ttc)) {
        $montantTotal = $consultation->budget->montant_ttc;
    }
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

    <table class="ref-date-table">
        <tr>
            <td style="width: 50%;">
                N° ........ /DRCA-RSK/{{ $annee }}
            </td>
            <td style="width: 50%; text-align: right;">
                Kénitra le: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>{{ $dateJour }}</strong>
            </td>
        </tr>
    </table>

    <div class="title-container">
        <div class="title">ATTESTATION DE REFERENCE</div>
        <div class="ornament">&#10086; &sim;&sim;&sim; &#10086; &sim;&sim;&sim; &#10086;</div>
    </div>

    <div style="margin-bottom: 20px; text-indent: 40px;">
        Je soussigné Monsieur Le Directeur Régional de l'office National du Conseil agricole de Rabat-Salé-Kénitra
    </div>

    <table class="content-table">
        <tr>
            <td class="label">Attestons que la Société</td>
            <td class="value">{{ $fournisseur->raison_sociale }}</td>
        </tr>
        <tr>
            <td class="label">Faisant élection de domicile à</td>
            <td class="value">{{ $fournisseur->adresse }}</td>
        </tr>
        <tr>
            <td class="label">Ville</td>
            <td class="value">{{ $fournisseur->ville ?? '' }}</td>
        </tr>
    </table>

    <div class="paragraph">
        A exécuté pour le compte de notre Direction durant l'année {{ $anneePrecedente }}-{{ $annee }}<br><br>
        Les prestations objet du {{ $typeEng }} N° <strong>{{ $numeroRef }}</strong>
    </div>

    <div class="paragraph">
        Ayant pour objet:
    </div>
    <div class="paragraph" style="font-weight: bold; margin-bottom: 30px;">
        {{ $consultation->objet_consultation }}
    </div>

    <table class="content-table">
        <tr>
            <td class="label">Pour un montant de:</td>
            <td class="value">{{ number_format($montantTotal, 2, ',', ' ') }} MAD</td>
        </tr>
        <tr>
            <td class="label">En lettres</td>
            <td class="value">{{ $montantEnLettres ?? '#NOM?' }}</td>
        </tr>
    </table>

    <div class="paragraph" style="margin-top: 40px;">
        Les prestations sont réalisées conformément aux règles de l'art.<br>
        La présente attestation est délivrée sur demande dudit prestataire pour servir et valoir ce que de droit
    </div>

    <div class="signature-box">
        Le Directeur Régional de l'Office<br>
        National du Conseil Agricole de<br>
        Rabat-Salé-Kénitra
    </div>

</body>
</html>
