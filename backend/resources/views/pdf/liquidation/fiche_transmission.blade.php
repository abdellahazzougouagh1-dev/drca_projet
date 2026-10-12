<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fiche de Transmission à l'Ordonnancement</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 40px; }
        .title { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 30px; text-transform: uppercase; border-bottom: 2px solid #000; display: inline-block; padding-bottom: 5px; }
        .content { margin-bottom: 30px; }
        .row { margin-bottom: 8px; }
        .label { font-weight: bold; display: inline-block; width: 250px; }
        .signature { margin-top: 60px; float: right; width: 300px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <p>Royaume du Maroc<br>Administration des Douanes et Impôts Indirects</p>
    </div>

    <div style="text-align: center;">
        <div class="title">FICHE DE TRANSMISSION À L'ORDONNANCEMENT</div>
    </div>

    <div class="content">
        <div class="row"><span class="label">Date de transmission :</span> {{ date('d/m/Y') }}</div>
        <div class="row"><span class="label">Marché N° :</span> {{ $marche->num_marche }}</div>
        <div class="row"><span class="label">Titulaire :</span> {{ $fournisseur->raison_sociale }}</div>
        <br>
        <div class="row"><span class="label">Montant Net à Payer (TTC) :</span> {{ number_format($liquidation->net_a_payer, 2, ',', ' ') }} DH</div>
        <div class="row"><span class="label">Imputation Budgétaire :</span> {{ $marche->imputation_budgetaire }}</div>
        <div class="row"><span class="label">Observations :</span> {{ $liquidation->observations }}</div>
    </div>

    <div class="signature">
        <p>Le Responsable</p>
        <br><br>
        <p><i>Signature</i></p>
    </div>
</body>
</html>
