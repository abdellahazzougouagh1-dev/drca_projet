<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordre de Service de Notification</title>
    <style>
        @page { 
            size: A4 portrait; 
            margin: 18px 34px 96px 34px; 
        }
        body { 
            font-family: "Times New Roman", Times, serif; 
            font-size: 12.5px; 
            color: #000; 
            line-height: 1.48; 
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
            margin-bottom: 16px;
        }
        
        .top-info-table { 
            margin-bottom: 22px; 
            font-size: 12.5px;
        }
        .top-info-table td { 
            vertical-align: top; 
        }
        
        .destinataire-box { 
            margin-left: 46%; 
            margin-bottom: 24px; 
            line-height: 1.5; 
            font-size: 12.5px;
        }
        
        .title { 
            text-align: center; 
            font-size: 16px; 
            font-weight: bold; 
            border: 2px solid #000;
            padding: 8px 14px;
            margin: 0 auto 22px auto;
            width: 88%;
        }
        
        .subject-table { 
            margin-bottom: 20px; 
            border: 1.5px solid #000;
        }
        .subject-table td { 
            vertical-align: top; 
            border: 1px solid #000;
            padding: 7.5px 9px;
            font-size: 12.5px;
        }
        .subject-label { 
            font-weight: bold; 
            width: 110px; 
            background: #f2f2f2;
        }
        
        .content { 
            text-align: justify; 
            margin-bottom: 24px; 
            font-size: 13px; 
            line-height: 1.55;
        }
        
        .signature { 
            text-align: right; 
            font-weight: bold; 
            font-size: 13px;
            margin-bottom: 36px; 
            padding-right: 40px; 
        }
        
        .accuse-box { 
            border: 1.5px solid #000; 
            padding: 14px 16px; 
            margin-top: 12px; 
            page-break-inside: avoid;
        }
        .accuse-title { 
            text-align: center; 
            font-weight: bold; 
            font-size: 14px; 
            text-decoration: underline; 
            margin-bottom: 14px; 
        }
        .accuse-content { 
            line-height: 1.65; 
            margin-bottom: 20px; 
            text-align: justify;
            font-size: 12.5px;
        }
        .accuse-signature { 
            text-align: right; 
            font-weight: bold; 
            padding-right: 36px; 
            height: 60px;
            font-size: 12.5px;
        }
    </style>
    @include('documents.partials.bc_typography')
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
            'raison_sociale' => '......................................................',
            'adresse' => '......................................................',
            'ville' => '........................'
        ];
    }
    
    // Format du numéro d'engagement
    $typeEng = $consultation->mode_engagement === 'BC' ? 'Bon de commande' : 'Convention';
    $numeroRef = str_replace('Consultation', $consultation->mode_engagement, $consultation->numero_consultation);
    
    // Date de notification
    $dateNotif = isset($consultation->engagement->date_notification) && $consultation->engagement->date_notification
        ? \Carbon\Carbon::parse($consultation->engagement->date_notification)->format('d/m/Y') 
        : \Carbon\Carbon::parse(now())->format('d/m/Y');
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

    <table class="top-info-table">
        <tr>
            <td style="width: 50%;">
                <strong>N° OS :</strong> ...... /{{ $consultation->annee ?? date('Y') }}/DRCA-RSK
            </td>
            <td style="width: 50%; text-align: right;">
                Kénitra, le : <strong>{{ $dateNotif }}</strong>
            </td>
        </tr>
    </table>

    <div class="destinataire-box">
        <strong>À Monsieur le Gérant de la société :</strong><br>
        <strong>{{ $fournisseur->raison_sociale }}</strong><br>
        {{ $fournisseur->adresse }}<br>
        {{ $fournisseur->ville ?? '' }}
    </div>

    <div class="title">
        ORDRE DE SERVICE DE NOTIFICATION DE L'APPROBATION
    </div>

    <table class="subject-table">
        <tr>
            <td class="subject-label">Objet :</td>
            <td>Notification de l'approbation {{ $typeEng === 'Bon de commande' ? 'du Bon de Commande' : 'de la Convention' }}.</td>
        </tr>
        <tr>
            <td class="subject-label">Référence :</td>
            <td>{{ $typeEng }} N° <strong>{{ $numeroRef }}</strong>, relatif à : <br><em>{{ $consultation->objet_consultation }}</em></td>
        </tr>
    </table>

    <div class="content">
        J'ai l'honneur de vous faire connaître que {{ $typeEng === 'Bon de commande' ? 'le bon de commande cité en référence' : 'la convention citée en référence' }}, dont le montant s'élève à <strong style="padding-left: 3px;">{{ number_format($consultation->engagement->montant_engagement ?? 0, 2, ',', ' ') }} MAD TTC</strong>, a été approuvé{{ $typeEng === 'Bon de commande' ? '' : 'e' }} le : <strong style="padding-left: 2px;">{{ \Carbon\Carbon::parse($consultation->engagement->date_engagement ?? now())->format('d/m/Y') }}</strong>.
        <br><br>
        Je vous invite en conséquence à prendre toutes les dispositions nécessaires pour l'exécution des prestations qui vous sont confiées dans les délais impartis.
    </div>

    <div class="signature">
        Le Sous-Ordonnateur
    </div>

    <!-- ACCUSE DE RECEPTION ENCADRÉ -->
    <div class="accuse-box">
        <div class="accuse-title">
            ACCUSÉ DE RÉCEPTION
        </div>
        <div class="accuse-content">
            Je soussigné(e) : ............................................................................................................................................................<br>
            Agissant en qualité de Gérant de la société : <strong>{{ $fournisseur->raison_sociale }}</strong><br>
            Atteste avoir reçu en date du : ...... / ...... / 20...... l'Ordre de Service de Notification de l'approbation {{ $typeEng === 'Bon de commande' ? 'relatif au Bon de Commande' : 'relative à la Convention' }} N° {{ $numeroRef }}.
        </div>
        <div class="accuse-signature">
            Signature et Cachet du Fournisseur
        </div>
    </div>

</body>
</html>
