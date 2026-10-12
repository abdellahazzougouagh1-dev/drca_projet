<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>MAINLEVÉE</title>
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
        <div class="title">DÉCISION DE MAINLEVÉE</div>
        <p>Date : {{ date('d/m/Y') }}</p>
    </div>

    <div class="section-title">IDENTIFICATION DU MARCHÉ</div>
    <div class="row"><span class="label">Marché N° :</span> {{ $marche->num_marche }}</div>
    <div class="row"><span class="label">Date du marché :</span> {{ $marche->created_at->format('d/m/Y') }}</div>
    <div class="row"><span class="label">Objet :</span> {{ $marche->objet_marche }}</div>
    <div class="row"><span class="label">Titulaire :</span> {{ $fournisseur->raison_sociale }}</div>
    <div class="row"><span class="label">ICE :</span> {{ $fournisseur->ice }}</div>

    <div class="section-title">CAUTIONNEMENT</div>
    <div class="row"><span class="label">Type de cautionnement :</span> {{ $cloture->type_cautionnement }}</div>
    <div class="row"><span class="label">Référence :</span> {{ $cloture->reference_caution }}</div>
    <div class="row"><span class="label">Date :</span> {{ $cloture->date_caution ? \Carbon\Carbon::parse($cloture->date_caution)->format('d/m/Y') : '' }}</div>
    <div class="row"><span class="label">Organisme financier :</span> {{ $cloture->organisme_caution }}</div>
    <div class="row"><span class="label">Montant :</span> {{ number_format($cloture->montant_caution, 2, ',', ' ') }} DH</div>

    <div class="section-title">SITUATION FINANCIÈRE ET EXÉCUTION</div>
    <div class="row"><span class="label">Montant Marché (TTC) :</span> {{ number_format($marche->montant, 2, ',', ' ') }} DH</div>
    <div class="row"><span class="label">Total Liquidé/Ordonnancé :</span> {{ number_format($finances['total_liquide'], 2, ',', ' ') }} DH</div>
    <div class="row"><span class="label">Retenues Appliquées :</span> {{ number_format($finances['total_retenues'], 2, ',', ' ') }} DH</div>
    <div class="row"><span class="label">Date de réception définitive :</span> {{ $cloture->date_reception_definitive ? \Carbon\Carbon::parse($cloture->date_reception_definitive)->format('d/m/Y') : 'Non renseignée' }}</div>

    <div class="section-title">DÉCISION</div>
    <p style="text-align: justify;">
        Considérant que le titulaire sus-désigné a rempli l'ensemble de ses obligations au titre du marché référencé ci-dessus, et au vu de la réception définitive prononcée sans réserves.<br><br>
        Il est décidé d'accorder <b>la mainlevée totale et définitive</b> du cautionnement référencé ci-dessus.
    </p>
    <div class="row"><span class="label">Motif :</span> {{ $cloture->motif_mainlevee }}</div>
    <div class="row"><span class="label">Observations :</span> {{ $cloture->observations_mainlevee }}</div>

    <div class="signature">
        <p>Fait à Casablanca, le {{ $cloture->date_signature_mainlevee ? \Carbon\Carbon::parse($cloture->date_signature_mainlevee)->format('d/m/Y') : date('d/m/Y') }}</p>
        <p><b>Le Signataire :</b> {{ $cloture->signataire_mainlevee }}</p>
        <br><br><br>
        <p><i>Signature et Cachet</i></p>
    </div>
</body>
</html>
