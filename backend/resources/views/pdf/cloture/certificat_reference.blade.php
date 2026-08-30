<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificat de Référence</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 40px; }
        .title { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 30px; text-transform: uppercase; border-bottom: 2px solid #000; display: inline-block; padding-bottom: 5px; }
        .section-title { font-size: 14px; font-weight: bold; margin-top: 20px; margin-bottom: 10px; background-color: #f0f0f0; padding: 5px; text-transform: uppercase; }
        .row { margin-bottom: 8px; }
        .label { font-weight: bold; display: inline-block; width: 250px; }
        .signature { margin-top: 60px; float: right; width: 300px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <p>Royaume du Maroc</p>
        <p>Administration des Douanes et Impôts Indirects</p>
        <p>Direction Régionale de Casablanca-Settat</p>
    </div>

    <div style="text-align: center;">
        <div class="title">CERTIFICAT DE RÉFÉRENCE</div>
    </div>

    <p style="text-align: justify; margin-bottom: 30px;">
        Le Directeur Régional de l'Administration des Douanes et Impôts Indirects de Casablanca-Settat, certifie par la présente que la société ci-dessous a exécuté les prestations relatives au marché indiqué, selon les détails suivants :
    </p>

    <div class="section-title">TITULAIRE DU MARCHÉ</div>
    <div class="row"><span class="label">Raison sociale :</span> {{ $fournisseur->raison_sociale }}</div>
    <div class="row"><span class="label">ICE :</span> {{ $fournisseur->ice }}</div>
    <div class="row"><span class="label">Adresse :</span> {{ $fournisseur->adresse }}</div>

    <div class="section-title">IDENTIFICATION DU MARCHÉ</div>
    <div class="row"><span class="label">N° du Marché :</span> {{ $marche->num_marche }}</div>
    <div class="row"><span class="label">Objet :</span> {{ $marche->objet_marche }}</div>
    <div class="row"><span class="label">Montant initial (TTC) :</span> {{ number_format($marche->montant, 2, ',', ' ') }} DH</div>
    <div class="row"><span class="label">Montant final exécuté (TTC) :</span> {{ number_format($finances['total_liquide'], 2, ',', ' ') }} DH</div>

    <div class="section-title">EXÉCUTION DES PRESTATIONS</div>
    <div class="row"><span class="label">Délai d'exécution initial :</span> {{ $marche->delai_execution }} jours</div>
    <div class="row"><span class="label">Date de notification :</span> {{ $marche->date_notification_marche ? $marche->date_notification_marche->format('d/m/Y') : '' }}</div>
    <div class="row"><span class="label">Date de réception provisoire :</span> {{ $cloture->date_reception_provisoire ? \Carbon\Carbon::parse($cloture->date_reception_provisoire)->format('d/m/Y') : '-' }}</div>
    <div class="row"><span class="label">Date de réception définitive :</span> {{ $cloture->date_reception_definitive ? \Carbon\Carbon::parse($cloture->date_reception_definitive)->format('d/m/Y') : '-' }}</div>
    
    <div class="row"><span class="label">Qualité de l'exécution :</span> {{ $cloture->qualite_execution }}</div>
    <div class="row"><span class="label">Respect des délais :</span> {{ $cloture->respect_delais ? 'OUI' : 'NON' }}</div>
    
    @if($cloture->reserves_emises)
        <div class="row"><span class="label">Réserves émises à la réception :</span> OUI</div>
        <div class="row"><span class="label">Levée des réserves :</span> {{ $cloture->reserves_levees ? 'OUI' : 'NON' }}</div>
    @else
        <div class="row"><span class="label">Réserves :</span> AUCUNE RÉSERVE</div>
    @endif

    <p style="text-align: justify; margin-top: 30px;">
        En foi de quoi, le présent certificat est délivré à l'intéressé(e) pour servir et valoir ce que de droit.
    </p>

    <div class="signature">
        <p>Fait à Casablanca, le {{ date('d/m/Y') }}</p>
        <p><b>Le Signataire :</b> {{ $cloture->signataire_certificat }}</p>
        <br><br><br>
        <p><i>Signature et Cachet</i></p>
    </div>
</body>
</html>
