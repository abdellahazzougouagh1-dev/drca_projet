<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>État de Liquidation</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 40px; }
        .title { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 30px; text-transform: uppercase; border-bottom: 2px solid #000; display: inline-block; padding-bottom: 5px; }
        .content { margin-bottom: 30px; }
        .row { margin-bottom: 8px; }
        .label { font-weight: bold; display: inline-block; width: 250px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: right; }
        th { background-color: #f0f0f0; text-align: center; }
        .signature { margin-top: 60px; float: right; width: 300px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <p>Royaume du Maroc<br>Administration des Douanes et Impôts Indirects</p>
    </div>

    <div style="text-align: center;">
        <div class="title">ÉTAT DE LIQUIDATION<br>Exercice {{ $liquidation->exercice_budgetaire }}</div>
        <p>Liquidation N° {{ $liquidation->num_liquidation }}</p>
    </div>

    <div class="content">
        <div class="row"><span class="label">Marché N° :</span> {{ $marche->num_marche }}</div>
        <div class="row"><span class="label">Titulaire :</span> {{ $fournisseur->raison_sociale }}</div>
        <div class="row"><span class="label">Imputation budgétaire :</span> {{ $marche->imputation_budgetaire }}</div>
        
        <table>
            <tr>
                <th colspan="2">DÉTAIL FINANCIER</th>
            </tr>
            <tr>
                <td>Montant du Marché (TTC)</td>
                <td>{{ number_format($marche->montant, 2, ',', ' ') }} DH</td>
            </tr>
            <tr>
                <td>Montant Brut de la Liquidation (TTC)</td>
                <td>{{ number_format($liquidation->montant_brut_ttc, 2, ',', ' ') }} DH</td>
            </tr>
            <tr>
                <td>Retenues de Garantie</td>
                <td>- {{ number_format($liquidation->retenue_garantie, 2, ',', ' ') }} DH</td>
            </tr>
            <tr>
                <td>Pénalités</td>
                <td>- {{ number_format($liquidation->penalites_retard, 2, ',', ' ') }} DH</td>
            </tr>
            <tr>
                <td>Avances à récupérer</td>
                <td>- {{ number_format($liquidation->avances_a_recuperer, 2, ',', ' ') }} DH</td>
            </tr>
            <tr style="font-weight: bold;">
                <td>NET À PAYER (TTC)</td>
                <td>{{ number_format($liquidation->net_a_payer, 2, ',', ' ') }} DH</td>
            </tr>
        </table>
    </div>

    <div class="signature">
        <p>Le Responsable</p>
        <br><br>
        <p><i>Signature</i></p>
    </div>
</body>
</html>
