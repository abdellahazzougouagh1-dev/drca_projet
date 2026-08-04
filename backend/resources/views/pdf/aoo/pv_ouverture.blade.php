<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Procès-Verbal d'Ouverture des Plis</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .content {
            margin: 20px 0;
            text-align: justify;
        }
        .section {
            margin: 20px 0;
        }
        .section-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
            background-color: #f0f0f0;
            padding: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        .signature-box {
            width: 180px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">PROCES-VERBAL D'OUVERTURE DES PLIS</div>
        <div>Appel d'Offres: {{ $aoo->num_aoo }}</div>
    </div>

    <div class="content">
        <p>Le présent procès-verbal constate l'ouverture des plis reçus pour l'Appel d'Offres numéro <strong>{{ $aoo->num_aoo }}</strong>, objet: <strong>{{ $aoo->objet_aoo }}</strong>.</p>
    </div>

    <div class="section">
        <div class="section-title">I. CARACTERISTIQUES DE L'APPEL D'OFFRES</div>
        <table>
            <tr>
                <td><strong>Numéro d'AOO:</strong></td>
                <td>{{ $aoo->num_aoo }}</td>
            </tr>
            <tr>
                <td><strong>Objet:</strong></td>
                <td>{{ $aoo->objet_aoo }}</td>
            </tr>
            <tr>
                <td><strong>Date limite de dépôt:</strong></td>
                <td>{{ $aoo->date_limite_depot ?? 'À définir' }}</td>
            </tr>
            <tr>
                <td><strong>Date d'ouverture:</strong></td>
                <td>{{ \Carbon\Carbon::now('Africa/Casablanca')->format('d/m/Y à H:i') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">II. OFFRES RECUES</div>
        <table>
            <thead>
                <tr>
                    <th>N° d'Ordre</th>
                    <th>Fournisseur/Société</th>
                    <th>Montant HT</th>
                    <th>Observations</th>
                </tr>
            </thead>
            <tbody>
                @php $cpt = 1; @endphp
                @forelse($aoo->concurrents as $concurrent)
                <tr>
                    <td>{{ $cpt }}</td>
                    <td>{{ $concurrent->fournisseur->societe ?? 'N/A' }}</td>
                    <td>{{ number_format($concurrent->montant ?? 0, 2, ',', ' ') }} DH</td>
                    <td></td>
                </tr>
                @php $cpt++; @endphp
                @empty
                <tr>
                    <td colspan="4" style="text-align: center;">Aucune offre reçue</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">III. OPERATEURS ADMIS</div>
        <p>Les fournisseurs dont les offres sont jugées conformes aux conditions de participation:</p>
        <ul>
            @forelse($aoo->concurrents as $concurrent)
            <li>{{ $concurrent->fournisseur->societe ?? 'N/A' }} - Montant: {{ number_format($concurrent->montant ?? 0, 2, ',', ' ') }} DH</li>
            @empty
            <li>Aucun concurrent</li>
            @endforelse
        </ul>
    </div>

    <div class="section">
        <div class="section-title">IV. LOTS</div>
        <table>
            <thead>
                <tr>
                    <th>N° Lot</th>
                    <th>Description</th>
                    <th>Nombre Articles</th>
                </tr>
            </thead>
            <tbody>
                @forelse($aoo->lots as $lot)
                <tr>
                    <td>{{ $lot->num_lot }}</td>
                    <td>{{ $lot->objet_lot }}</td>
                    <td>{{ $lot->items ? count($lot->items) : 0 }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" style="text-align: center;">Aucun lot</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">V. OBSERVATIONS PARTICULIERES</div>
        <p>Observations et remarques de la commission lors de l'ouverture des plis: à compléter selon les constatations.</p>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div style="height: 50px;"></div>
            <div style="border-top: 1px solid #000; margin-top: 10px;">
                Président<br>Commission
            </div>
        </div>
        <div class="signature-box">
            <div style="height: 50px;"></div>
            <div style="border-top: 1px solid #000; margin-top: 10px;">
                Secrétaire<br>Commission
            </div>
        </div>
        <div class="signature-box">
            <div style="height: 50px;"></div>
            <div style="border-top: 1px solid #000; margin-top: 10px;">
                Membre<br>Commission
            </div>
        </div>
    </div>
</body>
</html>
