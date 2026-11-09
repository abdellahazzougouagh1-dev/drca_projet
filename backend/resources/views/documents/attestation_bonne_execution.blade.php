<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Attestation de Référence</title>
    <style>
        @page { 
            size: A4 portrait; 
            margin: 18px 34px 96px 34px; 
        }
        body { 
            font-family: 'Times New Roman', Times, serif; 
            font-size: 13.5px; 
            color: #000; 
            line-height: 1.55; 
            margin: 0; 
            padding: 0; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
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
            margin-bottom: 18px;
        }
        
        .ref-date-table { 
            margin-bottom: 24px; 
            margin-top: 4px; 
            font-family: 'Times New Roman', Times, serif; 
            font-size: 13px; 
        }
        .ref-date-table td { 
            padding: 4px 0; 
        }
        
        .title-container { 
            text-align: center; 
            margin-bottom: 26px; 
        }
        .title { 
            font-size: 20px; 
            font-weight: bold; 
            text-transform: uppercase;
            border: 2.5px solid #000;
            display: inline-block;
            padding: 10px 26px;
            margin-bottom: 10px;
            letter-spacing: 0.6px;
        }
        
        /* Ornement de séparation compatible Times New Roman / DomPDF */
        .ornament {
            text-align: center;
            font-size: 14px;
            color: #8a783d;
            margin-bottom: 24px;
            letter-spacing: 4px;
            font-weight: bold;
        }
        
        .content-table { 
            width: 100%; 
            margin-bottom: 18px; 
            border-collapse: collapse; 
            border: 1.5px solid #000;
        }
        .content-table td { 
            border: 1px solid #000;
            padding: 8px 10px; 
            vertical-align: middle; 
            font-size: 13px;
        }
        .content-table .label { 
            width: 260px; 
            background: #f2f2f2;
            font-weight: bold;
        }
        .content-table .value { 
            font-weight: bold; 
        }
        
        .paragraph { 
            margin: 14px 0; 
            text-align: justify; 
        }
        
        .signature-box { 
            margin-top: 48px; 
            margin-left: 52%;
            text-align: center; 
            font-weight: bold; 
            line-height: 1.45;
            font-size: 13.5px;
        }
    </style>
    @include('documents.partials.bc_typography')
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

    <table class="ref-date-table">
        <tr>
            <td style="width: 50%;">
                N° ........ /DRCA-RSK/{{ $annee }}
            </td>
            <td style="width: 50%; text-align: right;">
                Kénitra, le : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>{{ $dateJour }}</strong>
            </td>
        </tr>
    </table>

    <div class="title-container">
        <div class="title">ATTESTATION DE RÉFÉRENCE</div>
        <div class="ornament">&mdash;&mdash;&nbsp;&nbsp;*&nbsp;&nbsp;*&nbsp;&nbsp;*&nbsp;&nbsp;&mdash;&mdash;</div>
    </div>

    <div style="margin-bottom: 20px; text-indent: 40px;">
        Je soussigné, Monsieur le Directeur Régional de l'Office National du Conseil Agricole de Rabat-Salé-Kénitra,
    </div>

    <table class="content-table">
        <tr>
            <td class="label">Atteste que la société :</td>
            <td class="value">{{ $fournisseur->raison_sociale }}</td>
        </tr>
        <tr>
            <td class="label">Faisant élection de domicile à :</td>
            <td class="value">{{ $fournisseur->adresse }}</td>
        </tr>
        <tr>
            <td class="label">Ville :</td>
            <td class="value">{{ $fournisseur->ville ?? '' }}</td>
        </tr>
    </table>

    <div class="paragraph">
        a exécuté pour le compte de notre Direction durant les années {{ $anneePrecedente }}-{{ $annee }} :<br><br>
        les prestations objet {{ $typeEng === 'Bon de Commande' ? 'du Bon de Commande' : 'de la Convention' }} N° <strong>{{ $numeroRef }}</strong>
    </div>

    <div class="paragraph">
        Ayant pour objet :
    </div>
    <div class="paragraph" style="font-weight: bold; margin-bottom: 30px;">
        {{ $consultation->objet_consultation }}
    </div>

    <table class="content-table">
        <tr>
            <td class="label">Pour un montant de :</td>
            <td class="value">{{ number_format($montantTotal, 2, ',', ' ') }} MAD TTC</td>
        </tr>
        <tr>
            <td class="label">En lettres :</td>
            <td class="value">{{ $montantEnLettres ?? '#NOM?' }}</td>
        </tr>
    </table>

    <div class="paragraph" style="margin-top: 40px;">
        Les prestations ont été réalisées conformément aux règles de l'art.<br>
        La présente attestation est délivrée sur la demande dudit prestataire pour servir et valoir ce que de droit.
    </div>

    <div class="signature-box">
        Le Directeur Régional de l'Office<br>
        National du Conseil Agricole de<br>
        Rabat-Salé-Kénitra
    </div>

</body>
</html>
