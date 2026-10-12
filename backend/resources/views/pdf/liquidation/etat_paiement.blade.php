<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>État de Paiement</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; line-height: 1.6; }
        .title { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 30px; text-transform: uppercase; }
        .row { margin-bottom: 8px; }
        .label { font-weight: bold; display: inline-block; width: 250px; }
    </style>
</head>
<body>
    <div class="title">ÉTAT DE PAIEMENT N° {{ $liquidation->id }}</div>
    <div class="row"><span class="label">Marché N° :</span> {{ $marche->num_marche }}</div>
    <div class="row"><span class="label">Titulaire :</span> {{ $fournisseur->raison_sociale }}</div>
    <div class="row"><span class="label">Facture :</span> {{ $liquidation->num_facture }} du {{ $liquidation->date_facture }}</div>
    <hr>
    <div class="row"><span class="label">Montant Brut TTC :</span> {{ number_format($liquidation->montant_brut_ttc, 2, ',', ' ') }} DH</div>
    <div class="row"><span class="label">Retenues :</span> {{ number_format($liquidation->retenues, 2, ',', ' ') }} DH</div>
    <div class="row"><span class="label">Net à Payer :</span> {{ number_format($liquidation->net_a_payer, 2, ',', ' ') }} DH</div>
</body>
</html>
