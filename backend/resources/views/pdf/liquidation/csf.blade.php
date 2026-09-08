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
        <p><strong>Royaume du Maroc</strong></p>
        <p><strong>Office National du Conseil Agricole</strong></p>
        <p><strong>Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra</strong></p>
    </div>

    @php
        $rawObjetCsf = $marche->objet_marche 
            ?: ($marche->aoo->objet 
            ?: ($marche->aoo->objet_marche 
            ?: ($marche->lot->objet_lot 
            ?: ($marche->notificationLigne->intitule 
            ?: ($marche->aoo->notificationLigne->intitule 
            ?: '')))));
        
        $lotTextCsf = '';
        if (isset($marche->lot)) {
            if (is_object($marche->lot)) {
                $numLot = $marche->lot->num_lot ?? '';
                $objLot = $marche->lot->objet_lot ?? '';
                if ($numLot && $objLot && $objLot !== $rawObjetCsf) {
                    $lotTextCsf = $numLot . ' - ' . $objLot;
                } elseif ($numLot) {
                    $lotTextCsf = $numLot;
                }
            } elseif (!empty($marche->lot)) {
                $lotTextCsf = $marche->lot;
            }
        }
        
        if (!empty($rawObjetCsf) && !empty($lotTextCsf)) {
            if (stripos($rawObjetCsf, $lotTextCsf) !== false) {
                $fullObjetCsf = $rawObjetCsf;
            } else {
                $fullObjetCsf = $rawObjetCsf . ', ' . $lotTextCsf;
            }
        } elseif (!empty($rawObjetCsf)) {
            $fullObjetCsf = $rawObjetCsf . (!empty($lotTextCsf) ? (', ' . $lotTextCsf) : '');
        } elseif (!empty($lotTextCsf)) {
            $fullObjetCsf = $lotTextCsf;
        } else {
            $fullObjetCsf = 'en lot unique';
        }
    @endphp

    <div style="text-align: center;">
        <div class="title">CERTIFICAT DE SERVICE FAIT</div>
        <p>N° {{ $liquidation->reference_service_fait ?? ('CSF/' . date('Y')) }}</p>
    </div>

    <div class="content">
        <div class="row"><span class="label">Marché N° :</span> {{ $marche->num_marche }}</div>
        <div class="row"><span class="label">Date du marché :</span> {{ $marche->date_notification_marche ? \Carbon\Carbon::parse($marche->date_notification_marche)->format('d/m/Y') : ($marche->created_at ? $marche->created_at->format('d/m/Y') : date('d/m/Y')) }}</div>
        <div class="row"><span class="label">Objet :</span> {{ $fullObjetCsf }}</div>
        <br>
        <div class="row"><span class="label">Titulaire :</span> {{ $fournisseur->raison_sociale ?? $marche->titulaire }}</div>
        <div class="row"><span class="label">ICE :</span> {{ $fournisseur->ice ?? $marche->ice }}</div>
        <br>
        <div class="row"><span class="label">Type de service fait :</span> {{ ucfirst($liquidation->type_execution ?? 'partielle') }}</div>
        <div class="row"><span class="label">Période d'exécution :</span> Du {{ $liquidation->date_debut_prestations ? \Carbon\Carbon::parse($liquidation->date_debut_prestations)->format('d/m/Y') : '.....' }} au {{ $liquidation->date_fin_prestations ? \Carbon\Carbon::parse($liquidation->date_fin_prestations)->format('d/m/Y') : '.....' }}</div>
        <div class="row"><span class="label">Montant du Service Fait (TTC) :</span> {{ number_format($liquidation->montant_ttc ?? 0, 2, ',', ' ') }} DH</div>
        <br>
        <p style="text-align: justify;">
            Je soussigné, <b>{{ $liquidation->agent_responsable ?? 'Le Sous-Ordonnateur' }}</b>, en qualité de <b>{{ $liquidation->fonction_agent ?? "Directeur Régional de l'ONCA de la région Rabat-Salé-Kénitra" }}</b> au service <b>{{ $liquidation->service_agent ?? 'DRCA-RSK' }}</b>, certifie exact le service fait pour les prestations susvisées, conformément aux clauses et conditions du marché.
        </p>
    </div>

    <div class="signature">
        <p>Fait à Rabat, le {{ $liquidation->date_service_fait ? \Carbon\Carbon::parse($liquidation->date_service_fait)->format('d/m/Y') : date('d/m/Y') }}</p>
        <p><b>Le Responsable</b></p>
        <br><br><br>
        <p><i>Signature et Cachet</i></p>
    </div>
</body>
</html>
