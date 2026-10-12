<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Récapitulatif des Liquidations</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; }
        .title { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 20px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f0f0f0; text-align: center; }
    </style>
</head>
<body>
    <div class="title">RÉCAPITULATIF DES LIQUIDATIONS</div>
    <p><b>Marché N° :</b> {{ $marche->num_marche }}</p>
    <p><b>Montant initial :</b> {{ number_format($marche->montant, 2, ',', ' ') }} DH</p>
    
    <table>
        <thead>
            <tr>
                <th>N° Liq</th>
                <th>Date</th>
                <th>Type</th>
                <th>Facture</th>
                <th>Brut TTC</th>
                <th>Retenues</th>
                <th>Net à Payer</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($liquidations as $liq)
            <tr>
                <td>{{ $liq->num_liquidation ?? $liq->id }}</td>
                <td>{{ \Carbon\Carbon::parse($liq->created_at)->format('d/m/Y') }}</td>
                <td>{{ $liq->type_execution }}</td>
                <td>{{ $liq->num_facture }}</td>
                <td>{{ number_format($liq->montant_brut_ttc, 2, ',', ' ') }}</td>
                <td>{{ number_format($liq->retenues, 2, ',', ' ') }}</td>
                <td>{{ number_format($liq->net_a_payer, 2, ',', ' ') }}</td>
                <td>{{ $liq->statut }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
