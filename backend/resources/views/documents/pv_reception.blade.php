<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Procès Verbal de Réception Définitive</title>
    <style>
        @page { size: A4; margin: 15mm 10mm; }
        body { font-family: Arial, sans-serif; font-size: 13px; color: #000; line-height: 1.3; margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; }
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
        
        .title-box { border: 2px solid #000; text-align: center; font-weight: bold; font-size: 16px; padding: 5px; width: 80%; margin: 20px auto; }
        
        .intro-table { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
        .intro-table td { padding: 3px; vertical-align: bottom; }
        
        .grid-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .grid-table th, .grid-table td { border: 1px solid #000; padding: 6px; text-align: center; }
        .grid-table th { background-color: #d9e1f2; font-weight: normal; } /* Bleu clair type Excel */
        
        .section-text { margin: 10px 0; text-align: left; }
        .center-bold { text-align: center; font-weight: bold; margin: 10px 0; }
        
        .bc-info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .bc-info-table td { padding: 5px; }
        
        .paragraph { text-align: justify; margin: 15px 0; line-height: 1.5; }
        
        .signature-table { width: 100%; border-collapse: collapse; margin-top: 20px; page-break-inside: avoid; }
        .signature-table th, .signature-table td { border: 1px solid #000; text-align: center; padding: 5px; }
        .signature-table th { background-color: #d9e1f2; font-weight: normal; }
        .signature-table .empty-space { height: 100px; }
        
        .underline { text-decoration: underline; font-weight: bold; }
    </style>
</head>
<body>

@php
    // Identification du fournisseur gagnant
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
            'raison_sociale' => '........................................',
            'adresse' => '......................................................',
            'ville' => '........................'
        ];
    }
    
    // N° BC et Date BC
    $numeroBC = str_replace('Consultation', 'BC', $consultation->numero_consultation);
    if(strpos($numeroBC, 'BC') === false) {
        $numeroBC = 'BC ' . $numeroBC;
    }
    $dateBC = isset($consultation->engagement->date_engagement) 
        ? \Carbon\Carbon::parse($consultation->engagement->date_engagement)->format('d/m/Y') 
        : \Carbon\Carbon::parse(now())->format('d/m/Y');
        
    // Dates de réunion (par défaut aujourd'hui)
    $dateReunion = \Carbon\Carbon::parse(now())->format('d/m/Y');
    $heureReunion = '10:00';
    $heureFin = '10:45';
    
    // Numéro de décision
    $numDecision = '30/DR/' . date('Y');
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

    <div class="title-box">
        PROCES VERBAL DE LA RECEPTION DEFINITIVE
    </div>

    <table class="intro-table">
        <tr>
            <td style="text-align: right; width: 15%;">En date du</td>
            <td style="font-weight: bold; width: 15%; text-align: center;">{{ $dateReunion }}</td>
            <td style="text-align: center; width: 5%;">à</td>
            <td style="font-weight: bold; width: 10%; text-align: center;">{{ $heureReunion }}</td>
            <td style="width: 55%;">la commission de reception instituée par la décision Numéro:</td>
        </tr>
    </table>
    <table class="intro-table">
        <tr>
            <td style="font-weight: bold; width: 25%; text-align: center;">{{ $numDecision }}</td>
            <td style="text-align: center; width: 15%;">en date du</td>
            <td style="font-weight: bold; width: 15%; text-align: center;">{{ $dateReunion }}</td>
            <td style="width: 45%;">Pour la réception des prestations objet du bon de commande N°:</td>
        </tr>
    </table>
    <table class="intro-table">
        <tr>
            <td style="font-weight: bold; width: 30%; text-align: center;">{{ $numeroBC }}</td>
            <td style="width: 70%;">qui est composée de:</td>
        </tr>
    </table>

    <table class="grid-table">
        <thead>
            <tr>
                <th style="width: 30%;">Nom et Prenom</th>
                <th style="width: 50%;">Fonction</th>
                <th style="width: 20%;">Qualité</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: left;">TAOUIL HASNAA</td>
                <td style="text-align: left;">Administrateur de 1er grade/SAF</td>
                <td>Présidente</td>
            </tr>
            <tr>
                <td style="text-align: left;">Karkass ahmed</td>
                <td style="text-align: left;">Technicien de 2ème grade/RESPONSABLE INVENTAIRE</td>
                <td>Membre</td>
            </tr>
            <tr>
                <td style="text-align: left;">OULD ABBOU Ibtissam</td>
                <td style="text-align: left;">Technicienne de 2ème grade/SAF</td>
                <td>Membre</td>
            </tr>
        </tbody>
    </table>

    <div class="section-text">
        S'est réunie au Siège de la DRCA-RSK en vue de procéder à la réception des prestations relatives au :
    </div>
    <div class="center-bold">
        {{ $consultation->objet_consultation }}
    </div>

    <table class="bc-info-table">
        <tr>
            <td style="width: 25%;">Bon de Commande N°:</td>
            <td style="font-weight: bold; text-align: center; width: 50%;">{{ $numeroBC }}</td>
            <td style="font-weight: bold; text-align: right; width: 25%;">{{ $dateBC }}</td>
        </tr>
    </table>

    <div class="section-text">
        Executées par l'attributaire:
    </div>

    <table class="grid-table">
        <thead>
            <tr>
                <th style="width: 30%;">La société</th>
                <th style="width: 50%;">Adresse</th>
                <th style="width: 20%;">Ville</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $fournisseur->raison_sociale }}</td>
                <td>{{ $fournisseur->adresse }}</td>
                <td>{{ $fournisseur->ville ?? '................' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="paragraph">
        A l'ouverture de la séance, la présidente rappelle aux membres de la commission l'objet de la réunion et fait une lecture du contenu du Bon de Commande sus-cité, notamment les désignations précisant les prestations demandées. Ensuite, le président demande aux membres de la commission de formuler leurs observations sur la conformité et la qualité des prestations livrées.
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 5px;">
        <tr>
            <td style="border: 1px solid #000; border-right: none; padding: 5px;">La commission atteste que les prestations objet du bon de commande N°:</td>
            <td style="border: 1px solid #000; border-left: none; border-right: none; font-weight: bold; text-align: center; padding: 5px;">{{ $numeroBC }}</td>
            <td style="border: 1px solid #000; border-left: none; padding: 5px;">sont conformes</td>
        </tr>
        <tr>
            <td colspan="3" style="border: 1px solid #000; border-top: none; padding: 5px;">aux dispositions prévues et certifie qu'elles sont définitivement réceptionnées</td>
        </tr>
    </table>
    
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
        <tr>
            <td style="border: 1px solid #000; border-right: none; padding: 5px; width: 35%;">La séance est levée le même jour</td>
            <td style="border: 1px solid #000; border-left: none; padding: 5px; width: 65%;">à &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $heureFin }}</td>
        </tr>
    </table>

    <div style="text-align: right; font-weight: bold; margin-bottom: 20px;">
        Fait à Kénitra le: {{ $dateReunion }}
    </div>

    <div style="text-align: center; font-weight: bold; text-decoration: underline; margin-bottom: 10px;">
        Les membres de la commission : Signataires
    </div>

    <table class="signature-table">
        <thead>
            <tr>
                <th style="width: 33.33%;">Présidente</th>
                <th style="width: 33.33%;">Membre 1</th>
                <th style="width: 33.33%;">Membre 2</th>
            </tr>
            <tr>
                <td style="background-color: #d9e1f2;">TAOUIL HASNAA</td>
                <td style="background-color: #d9e1f2;">Karkass ahmed</td>
                <td style="background-color: #d9e1f2;">OULD ABBOU Ibtissam</td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="empty-space"></td>
                <td class="empty-space"></td>
                <td class="empty-space"></td>
            </tr>
        </tbody>
    </table>

</body>
</html>
