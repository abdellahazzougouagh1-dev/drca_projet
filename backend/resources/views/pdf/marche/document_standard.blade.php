<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.55; color: #111; }
        .header, .footer { text-align: center; }
        .header { border-bottom: 1px solid #111; padding-bottom: 12px; }
        h1 { font-size: 16px; text-transform: uppercase; margin: 35px 0 28px; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin: 18px 0; }
        td { border: 1px solid #555; padding: 8px; vertical-align: top; }
        td:first-child { width: 34%; font-weight: bold; background: #f1f1f1; }
        .signature { margin-top: 55px; text-align: right; font-weight: bold; }
        .footer { position: fixed; bottom: 0; font-size: 8px; width: 100%; }
    </style>
</head>
<body>
    <div class="header"><strong>Royaume du Maroc</strong><br>Office National du Conseil Agricole<br>Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra</div>
    <h1>{{ $title }}</h1>
    <p>Le présent document concerne le marché ci-dessous :</p>
    <table>
        <tr><td>Marché</td><td>{{ $marche->num_marche }}</td></tr>
        <tr><td>Objet</td><td>{{ $marche->objet_marche ?: 'Non renseigné' }}</td></tr>
        <tr><td>Titulaire</td><td>{{ $marche->fournisseur?->raison_sociale ?: $marche->titulaire }}</td></tr>
        <tr><td>Montant</td><td>{{ number_format((float) $marche->montant, 2, ',', ' ') }} MAD</td></tr>
        @if($documentType === 'designation-agent-suivi')
            <tr><td>Agent chargé du suivi</td><td>{{ $marche->agent_suivi }}</td></tr>
        @endif
        @if(in_array($documentType, ['os-arret', 'os-reprise']))
            <tr><td>Référence de l'OS</td><td>{{ $marche->os_numero ?: 'Non renseignée' }}</td></tr>
            <tr><td>Date d'effet</td><td>{{ optional($marche->os_date_effet)->format('d/m/Y') ?: 'Non renseignée' }}</td></tr>
        @endif
        @if(in_array($documentType, ['pv-reception-provisoire', 'pv-reception-definitive', 'attestation-bonne-execution']))
            <tr><td>Date de réception finale</td><td>{{ optional($marche->date_reception_finale)->format('d/m/Y') ?: 'Non renseignée' }}</td></tr>
        @endif
    </table>
    @if($documentType === 'designation-agent-suivi')
        <p>M./Mme <strong>{{ $marche->agent_suivi }}</strong> est désigné(e) pour assurer le suivi de l'exécution du présent marché.</p>
    @elseif($documentType === 'os-arret')
        <p>Il est ordonné au titulaire de suspendre l'exécution des prestations jusqu'à nouvel ordre de l'autorité compétente.</p>
    @elseif($documentType === 'os-reprise')
        <p>Il est ordonné au titulaire de reprendre l'exécution des prestations conformément aux dispositions du marché.</p>
    @elseif($documentType === 'pv-reception-provisoire')
        <p>La commission constate la réception provisoire des prestations, sous réserve des observations éventuelles consignées au dossier du marché.</p>
    @elseif($documentType === 'pv-reception-definitive')
        <p>La commission constate la réception définitive des prestations réalisées au titre du présent marché.</p>
    @elseif($documentType === 'attestation-bonne-execution')
        <p>La présente attestation est délivrée au titulaire pour certifier la bonne exécution des prestations du présent marché.</p>
    @else
        <p>La présente décision concerne la désignation de la commission de réception du marché.</p>
    @endif
    <div class="signature">Le Directeur Régional</div>
    <div class="footer">Document généré le {{ now('Africa/Casablanca')->format('d/m/Y') }}</div>
</body>
</html>
