<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ordre de Service de Notification</title>
    <style>
        @page { 
            size: A4; 
            margin: 15mm 10mm; 
        }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 13px; 
            color: #000; 
            line-height: 1.5; 
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
        
        .top-info-table { 
            margin-bottom: 40px; 
        }
        .top-info-table td { 
            vertical-align: top; 
        }
        
        .destinataire-box { 
            margin-left: 50%; 
            margin-bottom: 50px; 
            line-height: 1.6; 
        }
        
        .title { 
            text-align: center; 
            font-size: 18px; 
            font-weight: bold; 
            text-decoration: underline; 
            margin-bottom: 40px; 
        }
        
        .subject-table { 
            margin-bottom: 30px; 
        }
        .subject-table td { 
            vertical-align: top; 
        }
        .subject-label { 
            font-weight: bold; 
            width: 80px; 
        }
        
        .content { 
            text-align: justify; 
            margin-bottom: 50px; 
            font-size: 14px; 
            line-height: 1.6;
        }
        
        .signature { 
            text-align: right; 
            font-weight: bold; 
            margin-bottom: 60px; 
            padding-right: 50px; 
        }
        
        .accuse-box { 
            border: 2px solid #000; 
            padding: 15px; 
            margin-top: 30px; 
            page-break-inside: avoid;
        }
        .accuse-title { 
            text-align: center; 
            font-weight: bold; 
            font-size: 15px; 
            text-decoration: underline; 
            margin-bottom: 20px; 
        }
        .accuse-content { 
            line-height: 1.8; 
            margin-bottom: 30px; 
            text-align: justify;
        }
        .accuse-signature { 
            text-align: right; 
            font-weight: bold; 
            padding-right: 50px; 
            height: 80px;
        }
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

    <table class="top-info-table">
        <tr>
            <td style="width: 50%;">
                <strong>N° OS :</strong> ...... /{{ date('Y') }}/DRCA-RSK
            </td>
            <td style="width: 50%; text-align: right;">
                Kénitra le : <strong>{{ $dateNotif }}</strong>
            </td>
        </tr>
    </table>

    <div class="destinataire-box">
        <strong>A Monsieur le Gérant de la société :</strong><br>
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
            <td>Notification de l'approbation du {{ $typeEng }}.</td>
        </tr>
        <tr>
            <td class="subject-label">Référence :</td>
            <td>{{ $typeEng }} N° <strong>{{ $numeroRef }}</strong>, relatif à : <br><em>{{ $consultation->objet_consultation }}</em></td>
        </tr>
    </table>

    <div class="content">
        J'ai l'honneur de vous faire connaître que le {{ strtolower($typeEng) }} cité en référence, dont le montant s'élève à <strong>{{ number_format($consultation->engagement->montant_engagement ?? 0, 2, ',', ' ') }} MAD TTC</strong>, a été approuvé le : <strong>{{ \Carbon\Carbon::parse($consultation->engagement->date_engagement ?? now())->format('d/m/Y') }}</strong>.
        <br><br>
        Je vous invite en conséquence à prendre toutes les dispositions nécessaires pour l'exécution des prestations qui vous sont confiées dans les délais impartis.
    </div>

    <div class="signature">
        Le Sous-Ordonnateur
    </div>

    <!-- ACCUSE DE RECEPTION ENCADRÉ -->
    <div class="accuse-box">
        <div class="accuse-title">
            ACCUSE DE RECEPTION
        </div>
        <div class="accuse-content">
            Je soussigné(e) : ............................................................................................................................................................<br>
            Agissant en qualité de Gérant de la société : <strong>{{ $fournisseur->raison_sociale }}</strong><br>
            Atteste avoir reçu en date du : ...... / ...... / 20...... l'Ordre de Service de Notification de l'approbation relatif au {{ $typeEng }} N° {{ $numeroRef }}.
        </div>
        <div class="accuse-signature">
            Signature et Cachet du Fournisseur
        </div>
    </div>

</body>
</html>
