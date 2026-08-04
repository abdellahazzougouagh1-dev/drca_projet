<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Avis d'Appel d'Offres</title>
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
        .notice-number {
            font-size: 14px;
            color: #666;
            margin: 10px 0;
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
        .highlight {
            background-color: #fff3cd;
            padding: 10px;
            border-left: 4px solid #ffc107;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">AVIS D'APPEL D'OFFRES OUVERT</div>
        <div class="notice-number">Publication le: {{ \Carbon\Carbon::now('Africa/Casablanca')->format('d/m/Y') }}</div>
    </div>

    <div class="highlight">
        <strong>Avis Important:</strong> Cet avis annonce le lancement d'un appel d'offres ouvert aux fournisseurs qualifiés. Tous les intéressés sont invités à participer aux conditions stipulées ci-dessous.
    </div>

    <div class="section">
        <div class="section-title">1. CARACTERISTIQUES DE L'APPEL D'OFFRES</div>
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
                <td><strong>Montant Estimé:</strong></td>
                <td>{{ number_format($aoo->montant_estime, 2, ',', ' ') }} DH</td>
            </tr>
            <tr>
                <td><strong>Type d'Appel d'Offres:</strong></td>
                <td>Appel d'Offres Ouvert (AOO)</td>
            </tr>
            <tr>
                <td><strong>Délai de Participation:</strong></td>
                <td>{{ $aoo->date_limite_depot ?? 'À préciser' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. CONDITIONS DE PARTICIPATION</div>
        <ul>
            <li>Être une personne physique ou morale régulièrement constituée</li>
            <li>Être en règle avec les obligations fiscales et sociales</li>
            <li>Ne pas être radiée du registre de commerce et d'industrie</li>
            <li>Disposer de qualifications techniques et professionnelles appropriées</li>
            <li>Fournir tous les documents exigés par le cahier des charges</li>
        </ul>
    </div>

    <div class="section">
        <div class="section-title">3. LOTS ET SPECIFICATIONS</div>
        <table>
            <thead>
                <tr>
                    <th>N° Lot</th>
                    <th>Description</th>
                    <th>Nombre d'Articles</th>
                </tr>
            </thead>
            <tbody>
                @forelse($aoo->lots as $lot)
                <tr>
                    <td>Lot {{ $lot->num_lot }}</td>
                    <td>{{ $lot->objet_lot }}</td>
                    <td>{{ $lot->items ? count($lot->items) : 0 }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" style="text-align: center;">Aucun lot défini</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">4. DEPOT DES OFFRES</div>
        <p>Les offres doivent être déposées avant la date limite stipulée dans le cahier des charges.</p>
        <p>Tous les documents demandés dans l'avis d'appel d'offres doivent être soumis pour que l'offre soit considérée comme complète.</p>
    </div>

    <div class="section">
        <div class="section-title">5. INFORMATION ET CONTACT</div>
        <p>Pour toute information complémentaire, veuillez consulter le cahier des charges ou contacter l'autorité responsable de l'appel d'offres.</p>
    </div>

    <div style="text-align: center; margin-top: 40px; font-size: 12px; color: #666;">
        <p>Avis publié le {{ \Carbon\Carbon::now('Africa/Casablanca')->format('d/m/Y à H:i') }}</p>
    </div>
</body>
</html>
