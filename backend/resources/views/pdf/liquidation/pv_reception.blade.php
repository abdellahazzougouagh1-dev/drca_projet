<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Procès-Verbal de Réception</title>
    <style>
        @page {
            margin: 6mm 10mm 15mm 10mm;
        }
        body { 
            font-family: 'DejaVu Sans', 'Arial', sans-serif; 
            font-size: 11px; 
            line-height: 1.3; 
            margin: 0; 
            padding: 0; 
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .header-table { width: 100%; margin-bottom: 5px; }
        .header-table td { vertical-align: middle; border: none; padding: 0; }
        .center-header { text-align: center; font-size: 11px; }

        .title-box {
            border: 2px solid #000;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            padding: 5px;
            margin: 10px auto;
            width: 80%;
            text-transform: uppercase;
        }

        .period-box {
            border: 2px solid #000;
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            padding: 5px;
            margin: 10px auto;
            width: 70%;
        }
        .period-table { width: 100%; border-collapse: collapse; border: none; }
        .period-table td { border: none; padding: 0; }

        .content-table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 10px; }
        .content-table th, .content-table td { border: 1px solid #000; padding: 5px; text-align: center; vertical-align: middle; }
        .content-table th { background-color: #fff; font-weight: bold; }
        
        .sign-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .sign-table th, .sign-table td { border: 2px solid #000; padding: 5px; text-align: center; }
        .sign-table td { height: 70px; vertical-align: top; }

        .mb-1 { margin-bottom: 5px; }
        .mb-2 { margin-bottom: 10px; }
        .mt-2 { margin-top: 10px; }
        
        p { margin: 4px 0; text-align: justify; }

    </style>
</head>
<body>

    <!-- Header Logos & Direction -->
    <table class="header-table">
        <tr>
            <td style="width: 30%; text-align: left;">
                @if(file_exists(public_path('images/logo-onca.png')))
                    <img src="{{ public_path('images/logo-onca.png') }}" style="height: 50px;" alt="ONCA">
                @endif
            </td>
            <td class="center-header" style="width: 40%;">
                Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
            </td>
            <td style="width: 30%; text-align: right;">
                @if(file_exists(public_path('images/sceau-maroc.png')))
                    <img src="{{ public_path('images/sceau-maroc.png') }}" style="height: 50px;" alt="Royaume du Maroc">
                @endif
            </td>
        </tr>
    </table>
    
    <hr style="border: 1px solid #000; margin-top: 0px; margin-bottom: 10px;">

    <div class="title-box">
        PROCES VERBAL DE LA RECEPTION {{ strtoupper($liquidation->type_execution ?? 'PARTIELLE') }}
    </div>

    <div class="period-box">
        <table class="period-table">
            <tr>
                <td style="width: 25%; text-align: left;">Période : du</td>
                <td style="width: 25%; text-align: center;">{{ $liquidation->date_debut_prestations ? \Carbon\Carbon::parse($liquidation->date_debut_prestations)->format('d/m/Y') : '..........' }}</td>
                <td style="width: 25%; text-align: center;">au</td>
                <td style="width: 25%; text-align: right;">{{ $liquidation->date_fin_prestations ? \Carbon\Carbon::parse($liquidation->date_fin_prestations)->format('d/m/Y') : '..........' }}</td>
            </tr>
        </table>
    </div>

    <div class="text-center font-bold mb-2" style="font-size: 12px;">
        Marché N°: {{ $marche->num_marche }}
    </div>

    <div style="margin-bottom: 10px; padding: 0 10px; line-height: 1.5;">
        En date du <strong>{{ ($liquidation->date_reunion_commission ?? $marche->date_reunion_commission) ? \Carbon\Carbon::parse($liquidation->date_reunion_commission ?? $marche->date_reunion_commission)->format('d/m/Y') : '..........' }}</strong> à <strong>{{ ($liquidation->heure_reunion_commission ?? $marche->heure_reunion_commission) ? \Carbon\Carbon::parse($liquidation->heure_reunion_commission ?? $marche->heure_reunion_commission)->format('H:i') : '..........' }}</strong> la commission de reception instituée par la décision Numéro:<br>
        <strong>{{ $liquidation->num_decision ?? $marche->num_decision ?? '..........' }}</strong> en date du <strong>{{ ($liquidation->date_decision ?? $marche->date_decision) ? \Carbon\Carbon::parse($liquidation->date_decision ?? $marche->date_decision)->format('d/m/Y') : '..........' }}</strong> Pour la reception des prestations objet du marché N°: <strong>{{ $marche->num_marche }}</strong> qui est composée de:
    </div>

    @php
        $membresCommission = [];
        
        if (is_array($liquidation->commission_reception) && count($liquidation->commission_reception) > 0) {
            $membresCommission = $liquidation->commission_reception;
        } elseif (is_array($marche->commission_reception) && count($marche->commission_reception) > 0) {
            $membresCommission = $marche->commission_reception;
        } elseif ($marche->aoo && is_array($marche->aoo->membres_commission) && count($marche->aoo->membres_commission) > 0) {
            foreach ($marche->aoo->membres_commission as $membre) {
                $membresCommission[] = [
                    'nom' => $membre['nom_prenom'] ?? '........................',
                    'fonction' => $membre['fonction'] ?? '........................',
                    'qualite' => $membre['role'] ?? 'Membre',
                ];
            }
        } else {
            $membresCommission = [
                ['nom' => '........................', 'fonction' => '........................', 'qualite' => 'Présidente'],
                ['nom' => '........................', 'fonction' => '........................', 'qualite' => 'Membre'],
                ['nom' => '........................', 'fonction' => '........................', 'qualite' => 'Membre'],
            ];
        }
    @endphp

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

    <div style="margin: 10px 0; padding: 0 10px;">
        <p>S'est réunie au Siège de la DRCA-RSK en vue de procéder à la réception des prestations relatives à:</p>
        <p class="font-bold">{{ ucfirst(strtolower($marche->objet_marche)) }}, en lot {{ strtolower(trim($marche->lot ?? '')) === 'lot unique' ? 'unique' : ($marche->lot ?? 'unique') }}</p>
    </div>

    <table style="width: 100%; margin: 10px 0; border: none;">
        <tr>
            <td style="width: 50%; text-align: center;">Marché N°: &nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ $marche->num_marche }}</strong></td>
            <td style="width: 50%; text-align: center;">en date du &nbsp;&nbsp;&nbsp;&nbsp;<strong>{{ $marche->date_notification_marche ? \Carbon\Carbon::parse($marche->date_notification_marche)->format('d/m/Y') : '..........' }}</strong></td>
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
            <td class="font-bold">{{ strtoupper($fournisseur->raison_sociale ?? '........................') }}</td>
            <td class="font-bold">{{ strtoupper($fournisseur->adresse ?? '........................') }}</td>
            <td class="font-bold">{{ strtoupper($fournisseur->ville ?? '........................') }}</td>
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
            <td style="width: 50%; text-align: left; padding-left: 10px;">La séance est levée le même jour &nbsp;&nbsp;&nbsp;&nbsp;à &nbsp;&nbsp;{{ ($liquidation->heure_reunion_commission ?? $marche->heure_reunion_commission) ? \Carbon\Carbon::parse($liquidation->heure_reunion_commission ?? $marche->heure_reunion_commission)->addHours(1)->format('H:i') : '10:45' }}</td>
            <td style="width: 50%; text-align: right; padding-right: 20px;">Fait à Kénitra le: {{ $liquidation->date_reception ? \Carbon\Carbon::parse($liquidation->date_reception)->format('d/m/Y') : '..........' }}</td>
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
