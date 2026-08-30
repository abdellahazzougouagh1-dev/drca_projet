<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificat de Service Fait</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 40px; }
        .title { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 30px; text-transform: uppercase; border-bottom: 2px solid #000; display: inline-block; padding-bottom: 5px; }
        .content { margin-bottom: 30px; }
        .row { margin-bottom: 10px; }
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
        <div class="title">CERTIFICAT DE SERVICE FAIT</div>
        <p>N° {{ $liquidation->reference_service_fait }}</p>
    </div>

    <div class="content">
        <div class="row"><span class="label">Marché N° :</span> {{ $marche->num_marche }}</div>
        <div class="row"><span class="label">Date du marché :</span> {{ $marche->created_at->format('d/m/Y') }}</div>
        <div class="row"><span class="label">Objet :</span> {{ $marche->objet_marche }}</div>
        <br>
        <div class="row"><span class="label">Titulaire :</span> {{ $fournisseur->raison_sociale }}</div>
        <div class="row"><span class="label">ICE :</span> {{ $fournisseur->ice }}</div>
        <br>
        <div class="row"><span class="label">Type de service fait :</span> {{ ucfirst($liquidation->type_execution) }}</div>
        <div class="row"><span class="label">Période d'exécution :</span> Du {{ \Carbon\Carbon::parse($liquidation->date_debut_prestations)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($liquidation->date_fin_prestations)->format('d/m/Y') }}</div>
        <div class="row"><span class="label">Montant du Service Fait (TTC) :</span> {{ number_format($liquidation->montant_ttc, 2, ',', ' ') }} DH</div>
        <br>
        <p style="text-align: justify;">
            Je soussigné, <b>{{ $liquidation->agent_responsable }}</b>, en qualité de <b>{{ $liquidation->fonction_agent }}</b> au service <b>{{ $liquidation->service_agent }}</b>, certifie exact le service fait pour les prestations susvisées, conformément aux clauses et conditions du marché.
        </p>
    </div>

    <div class="signature">
        <p>Fait à Casablanca, le {{ \Carbon\Carbon::parse($liquidation->date_service_fait)->format('d/m/Y') }}</p>
        <p><b>Le Responsable</b></p>
        <br><br><br>
        <p><i>Signature et Cachet</i></p>
    </div>
</body>
</html>
