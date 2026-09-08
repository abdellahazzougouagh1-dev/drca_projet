<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Procès-Verbal de Réception</title>
    <style>
        @page {
            margin: 8mm 12mm 15mm 12mm;
        }
        body { 
            font-family: Arial, Helvetica, sans-serif; 
            font-size: 12.5px; 
            line-height: 1.45; 
            margin: 0; 
            padding: 0; 
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .header-table { width: 100%; margin-bottom: 8px; }
        .header-table td { vertical-align: middle; border: none; padding: 0; }
        .center-header { text-align: center; font-size: 14px; font-weight: bold; line-height: 1.35; }

        .title-box {
            border: 2px solid #000;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            padding: 6px;
            margin: 12px auto;
            width: 85%;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .period-box {
            border: 2px solid #000;
            text-align: center;
            font-size: 13.5px;
            font-weight: bold;
            padding: 6px;
            margin: 12px auto;
            width: 75%;
        }
        .period-table { width: 100%; border-collapse: collapse; border: none; }
        .period-table td { border: none; padding: 0; }

        .content-table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 10px; font-size: 12px; }
        .content-table th, .content-table td { border: 1.5px solid #000; padding: 6px 8px; text-align: center; vertical-align: middle; }
        .content-table th { background-color: #f8fafc; font-weight: bold; }
        
        .sign-table { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 12px; }
        .sign-table th, .sign-table td { border: 2px solid #000; padding: 6px; text-align: center; }
        .sign-table td { height: 75px; vertical-align: top; }

        .mb-1 { margin-bottom: 5px; }
        .mb-2 { margin-bottom: 10px; }
        .mt-2 { margin-top: 10px; }
        
        p { margin: 5px 0; text-align: justify; font-size: 12.5px; }

    </style>
</head>
<body>

    <!-- Header Logos & Direction -->
    <table class="header-table">
        <tr>
            <td style="width: 25%; text-align: left;">
                @if(file_exists(public_path('images/logo-onca.png')))
                    <img src="{{ public_path('images/logo-onca.png') }}" style="height: 75px;" alt="ONCA">
                @endif
            </td>
            <td class="center-header" style="width: 50%;">
                Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
            </td>
            <td style="width: 25%; text-align: right;">
                @if(file_exists(public_path('images/sceau-maroc.png')))
                    <img src="{{ public_path('images/sceau-maroc.png') }}" style="height: 75px;" alt="Royaume du Maroc">
                @endif
            </td>
        </tr>
    </table>
    
    <hr style="border: 1.5px solid #000; margin-top: 0px; margin-bottom: 12px;">

    <div class="title-box">
        PROCES VERBAL DE LA RECEPTION {{ strtoupper($liquidation->type_execution ?? 'PARTIELLE') }}
    </div>

    @php
        // Résolution Date Réunion
        $dateReunionRaw = $liquidation->date_reunion_commission 
            ?? ($marche->date_reunion_commission 
            ?? ($liquidation->date_reception 
            ?? ($liquidation->date_fin_prestations 
            ?? ($liquidation->date_service_fait 
            ?? ($marche->date_reception_finale ?? null)))));
        $dateReunionFormatted = $dateReunionRaw ? \Carbon\Carbon::parse($dateReunionRaw)->format('d/m/Y') : date('d/m/Y');

        // Résolution Heure Réunion
        $heureReunionRaw = $liquidation->heure_reunion_commission 
            ?? ($marche->heure_reunion_commission 
            ?? ($marche->aoo->heure_ouverture ?? '10:00'));
        $heureReunionFormatted = '10:00';
        if (!empty($heureReunionRaw)) {
            if (is_string($heureReunionRaw)) {
                $heureReunionFormatted = strlen($heureReunionRaw) >= 5 ? substr($heureReunionRaw, 0, 5) : $heureReunionRaw;
            } elseif ($heureReunionRaw instanceof \DateTimeInterface) {
                $heureReunionFormatted = $heureReunionRaw->format('H:i');
            }
        }

        // Résolution N° Décision
        $numDecisionFormatted = $liquidation->num_decision 
            ?? ($marche->num_decision_nomination 
            ?? ($marche->num_decision 
            ?? ($marche->aoo->num_decision_nomination 
            ?? ('DEC-' . ($marche->num_marche ?? date('Y'))))));

        // Résolution Date Décision
        $dateDecisionRaw = $liquidation->date_decision 
            ?? ($marche->date_decision_nomination 
            ?? ($marche->date_decision 
            ?? ($marche->aoo->date_decision_nomination 
            ?? ($marche->date_approbation 
            ?? ($marche->date_notification_marche ?? null)))));
        $dateDecisionFormatted = $dateDecisionRaw ? \Carbon\Carbon::parse($dateDecisionRaw)->format('d/m/Y') : date('d/m/Y');

        // Résolution Période du ... au ...
        $dateDebutRaw = $liquidation->date_debut_prestations 
            ?? ($marche->os_date_effet 
            ?? ($marche->date_notification_marche ?? null));
        $dateDebutFormatted = $dateDebutRaw ? \Carbon\Carbon::parse($dateDebutRaw)->format('d/m/Y') : '01/10/' . date('Y');

        $dateFinRaw = $liquidation->date_fin_prestations 
            ?? ($liquidation->date_reception 
            ?? ($dateReunionRaw ?? null));
        $dateFinFormatted = $dateFinRaw ? \Carbon\Carbon::parse($dateFinRaw)->format('d/m/Y') : '20/11/' . date('Y');

        // Membres commission
        $membresCommission = [];
        if (isset($liquidation->commission_reception) && !empty($liquidation->commission_reception)) {
            $rawMembres = is_string($liquidation->commission_reception) ? json_decode($liquidation->commission_reception, true) : $liquidation->commission_reception;
            if (is_array($rawMembres) && count($rawMembres) > 0) {
                foreach ($rawMembres as $m) {
                    $membresCommission[] = [
                        'nom' => $m['nom_prenom'] ?? ($m['nom'] ?? ''),
                        'fonction' => $m['fonction'] ?? '',
                        'qualite' => $m['qualite'] ?? ($m['role'] ?? 'Membre')
                    ];
                }
            }
        } elseif (isset($marche->commission_reception) && !empty($marche->commission_reception)) {
            $rawMembres = is_string($marche->commission_reception) ? json_decode($marche->commission_reception, true) : $marche->commission_reception;
            if (is_array($rawMembres) && count($rawMembres) > 0) {
                foreach ($rawMembres as $m) {
                    $membresCommission[] = [
                        'nom' => $m['nom_prenom'] ?? ($m['nom'] ?? ''),
                        'fonction' => $m['fonction'] ?? '',
                        'qualite' => $m['qualite'] ?? ($m['role'] ?? 'Membre')
                    ];
                }
            }
        } elseif (isset($marche->aoo->membres_commission) && !empty($marche->aoo->membres_commission)) {
            $rawMembres = is_string($marche->aoo->membres_commission) ? json_decode($marche->aoo->membres_commission, true) : $marche->aoo->membres_commission;
            if (is_array($rawMembres) && count($rawMembres) > 0) {
                foreach ($rawMembres as $m) {
                    $membresCommission[] = [
                        'nom' => $m['nom_prenom'] ?? ($m['nom'] ?? ''),
                        'fonction' => $m['fonction'] ?? '',
                        'qualite' => $m['role'] ?? ($m['qualite'] ?? 'Membre')
                    ];
                }
            }
        }

        if (empty($membresCommission)) {
            $membresCommission = [
                ['nom' => 'Enneddam Wafaa', 'fonction' => 'Chef de Service de la Programmation du conseil agricole', 'qualite' => 'Présidente'],
                ['nom' => 'Ramah Mohamed', 'fonction' => 'Chef de SPMOCA KHEMISSAT', 'qualite' => 'Membre'],
                ['nom' => 'ABDELHAKIM Mohsine', 'fonction' => 'Chef de CCA Had Kourt', 'qualite' => 'Membre']
            ];
        }
    @endphp

    <div class="period-box">
        <table class="period-table">
            <tr>
                <td style="width: 25%; text-align: left;">Période : du</td>
                <td style="width: 25%; text-align: center;">{{ $dateDebutFormatted }}</td>
                <td style="width: 25%; text-align: center;">au</td>
                <td style="width: 25%; text-align: right;">{{ $dateFinFormatted }}</td>
            </tr>
        </table>
    </div>

    <div class="text-center font-bold mb-2" style="font-size: 12px;">
        Marché N°: {{ $marche->num_marche }}
    </div>

    <div style="margin-bottom: 10px; padding: 0 10px; line-height: 1.5;">
        En date du <strong>{{ $dateReunionFormatted }}</strong> à <strong>{{ $heureReunionFormatted }}</strong> la commission de reception instituée par la décision Numéro:<br>
        <strong>{{ $numDecisionFormatted }}</strong> en date du <strong>{{ $dateDecisionFormatted }}</strong> Pour la reception des prestations objet du marché N°: <strong>{{ $marche->num_marche }}</strong> qui est composée de:
    </div>

    <table class="content-table" style="border: 2px solid #000;">
        <tr>
            <th style="width: 40%;">Nom et Prenom</th>
            <th style="width: 40%;">Fonction</th>
            <th style="width: 20%;">Qualité</th>
        </tr>
        @foreach($membresCommission as $membre)
        <tr>
            <td style="text-align: left; padding-left: 10px;">{{ $membre['nom'] }}</td>
            <td style="text-align: left; padding-left: 10px;">{{ $membre['fonction'] }}</td>
            <td>{{ $membre['qualite'] }}</td>
        </tr>
        @endforeach
    </table>

    @php
        $rawObjetPv = $marche->objet_marche 
            ?: ($marche->aoo->objet 
            ?: ($marche->aoo->objet_marche 
            ?: ($marche->lot->objet_lot 
            ?: ($marche->notificationLigne->intitule 
            ?: ($marche->aoo->notificationLigne->intitule 
            ?: '')))));
        
        $fullObjetPv = !empty($rawObjetPv) ? $rawObjetPv : 'Prestations liées au marché';
    @endphp

    <div style="margin: 10px 0; padding: 0 10px;">
        <p>S'est réunie au Siège de la DRCA-RSK en vue de procéder à la réception des prestations relatives à:</p>
        <p class="font-bold">{{ $fullObjetPv }}</p>
    </div>

    <table style="width: 100%; margin: 10px 0; border: none;">
        <tr>
            <td style="width: 50%; text-align: center;">Marché N°: &nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ $marche->num_marche }}</strong></td>
            <td style="width: 50%; text-align: center;">en date du &nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ $marche->date_notification_marche ? \Carbon\Carbon::parse($marche->date_notification_marche)->format('d/m/Y') : ($marche->date_approbation ? \Carbon\Carbon::parse($marche->date_approbation)->format('d/m/Y') : ($marche->created_at ? $marche->created_at->format('d/m/Y') : date('d/m/Y'))) }}</strong></td>
        </tr>
    </table>

    <div style="padding: 0 10px;">Executées par :</div>

    <table class="content-table" style="border: 2px solid #000;">
        <tr>
            <th style="width: 40%;">La société</th>
            <th style="width: 40%;">Adresse</th>
            <th style="width: 20%;">Ville</th>
        </tr>
        <tr>
            <td class="font-bold">{{ strtoupper($fournisseur->raison_sociale ?? ($marche->titulaire ?? 'MALKMER PROJECTS SARL AU')) }}</td>
            <td class="font-bold">{{ strtoupper($fournisseur->adresse ?? ($marche->adresse_fournisseur ?? 'RABAT')) }}</td>
            <td class="font-bold">{{ strtoupper($fournisseur->ville ?? ($marche->ville_fournisseur ?? 'RABAT')) }}</td>
        </tr>
    </table>

    <div style="margin: 10px 0; padding: 0 10px;">
        <p>A l'ouverture de la séance, la présidente rappelle aux membres de la commission l'objet de la réunion et fait une lecture du contenu du marché sus-cité, notamment les désignations et les quantités précisant les prestations demandées. Ensuite, la présidente demande aux membres de la commission de formuler leurs observations sur la conformité et la qualité des prestations livrées ou exécutées.</p>
        
        <p>La commission atteste que les prestations objet du marché numéro: &nbsp;&nbsp;&nbsp;&nbsp;{{ $marche->num_marche }} &nbsp;&nbsp;&nbsp;&nbsp;sont conformes aux dispositions prévues dans le CPS et certifie qu'elles sont {{ strtolower($liquidation->type_execution ?? 'partiellement') }} réceptionnées</p>
        
        <p class="font-bold">Comme suivant :</p>
    </div>

    <table class="content-table" style="border: 2px solid #000;">
        <tr>
            <th style="width: 40%;">Désignation des prestations</th>
            <th style="width: 20%;">Unité de<br>compte</th>
            <th style="width: 20%;">Quantité<br>Prévue</th>
            <th style="width: 20%;">Quantité<br>Exécutée</th>
        </tr>
        @if(isset($liquidation->lignes) && count($liquidation->lignes) > 0)
            @foreach($liquidation->lignes as $ligne)
            <tr>
                <td style="text-align: left; padding: 10px;">{{ $ligne->designation }}</td>
                <td>{{ $ligne->unite }}</td>
                <td>{{ $ligne->quantite_prevue }}</td>
                <td>{{ $ligne->quantite_executee }}</td>
            </tr>
            @endforeach
        @else
            <tr>
                <td style="padding: 20px;"></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif
    </table>

    <table style="width: 100%; margin: 15px 0 30px 0; border: none;">
        <tr>
            <td style="width: 50%; text-align: left; padding-left: 10px;">La séance est levée le même jour &nbsp;&nbsp;&nbsp;&nbsp;à &nbsp;&nbsp;{{ $heureReunionFormatted ? \Carbon\Carbon::parse($heureReunionFormatted)->addHours(1)->format('H:i') : '11:00' }}</td>
            <td style="width: 50%; text-align: right; padding-right: 20px;">Fait à Kénitra le: {{ $liquidation->date_reception ? \Carbon\Carbon::parse($liquidation->date_reception)->format('d/m/Y') : $dateReunionFormatted }}</td>
        </tr>
    </table>

    <div class="text-center font-bold mb-2" style="text-decoration: underline;">
        Les membres de la commission : Signataires
    </div>

    <table class="sign-table">
        <tr>
            @foreach($membresCommission as $membre)
            <th style="width: {{ 100 / count($membresCommission) }}%; font-weight: normal;">{{ $membre['qualite'] }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach($membresCommission as $membre)
            <td>{{ $membre['nom'] }}</td>
            @endforeach
        </tr>
    </table>

</body>
</html>
