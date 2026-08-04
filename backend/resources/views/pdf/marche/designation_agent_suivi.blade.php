<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Désignation Agent de Suivi</title>
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
            width: 200px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">DESIGNATION DE L'AGENT CHARGE DU SUIVI</div>
        <div>Marché: {{ $marche->num_marche }}</div>
    </div>

    <div class="content">
        <p>Par la présente, il est désigné un agent chargé du suivi du marché référencé ci-dessous.</p>
    </div>

    <div class="section">
        <div class="section-title">I. CARACTERISTIQUES DU MARCHE</div>
        <table>
            <tr>
                <td><strong>Numéro du Marché:</strong></td>
                <td>{{ $marche->num_marche }}</td>
            </tr>
            <tr>
                <td><strong>Objet:</strong></td>
                <td>{{ $marche->objet_marche }}</td>
            </tr>
            <tr>
                <td><strong>Fournisseur:</strong></td>
                <td>{{ $marche->fournisseur?->raison_sociale ?? $marche->nom_fournisseur }}</td>
            </tr>
            <tr>
                <td><strong>Montant du Marché:</strong></td>
                <td>{{ number_format($marche->montant, 2, ',', ' ') }} DH</td>
            </tr>
            <tr>
                <td><strong>Date de Signature:</strong></td>
                <td>{{ $marche->date_signature_marche ?? '...' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">II. AGENT DESIGNE</div>
        <table>
            <tr>
                <td><strong>Nom et Prénom:</strong></td>
                <td>{{ $marche->agent_suivi_nom ?? '.....................................................................' }}</td>
            </tr>
            <tr>
                <td><strong>Fonction/Poste:</strong></td>
                <td>{{ $marche->agent_suivi_fonction ?? '.....................................................................' }}</td>
            </tr>
            <tr>
                <td><strong>Numéro de Téléphone:</strong></td>
                <td>{{ $marche->agent_suivi_telephone ?? '.....................................................................' }}</td>
            </tr>
            <tr>
                <td><strong>Adresse Email:</strong></td>
                <td>{{ $marche->agent_suivi_email ?? '.....................................................................' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">III. MISSIONS ET RESPONSABILITES</div>
        <p>L'agent désigné aura pour missions:</p>
        <ul>
            <li>Assurer le suivi régulier de l'exécution du marché</li>
            <li>Vérifier la conformité des prestations avec les spécifications du marché</li>
            <li>Collecter les pièces justificatives nécessaires (factures, attestations, etc.)</li>
            <li>Signaler sans délai tout problème ou écart détecté</li>
            <li>Participer aux réunions de suivi du marché</li>
            <li>Établir les rapports périodiques d'exécution</li>
        </ul>
    </div>

    <div class="section">
        <div class="section-title">IV. DATE D'EFFET</div>
        <p>Cette désignation prend effet à partir du {{ \Carbon\Carbon::now('Africa/Casablanca')->format('d/m/Y') }} et reste valable jusqu'à la clôture définitive du marché.</p>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div style="height: 60px;"></div>
            <div style="border-top: 1px solid #000; margin-top: 10px;">
                <strong>Autorité Responsable</strong>
            </div>
        </div>
        <div class="signature-box">
            {{ \Carbon\Carbon::now('Africa/Casablanca')->format('d/m/Y') }}
            <div style="height: 40px;"></div>
            <div style="border-top: 1px solid #000; margin-top: 10px;">
                Date
            </div>
        </div>
    </div>
</body>
</html>
