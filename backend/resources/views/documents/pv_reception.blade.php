<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $docTitle }}</title>
    <style>
        @page { margin: 115px 40px 70px 40px; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12.5px;
            color: #000;
            line-height: 1.45;
            margin: 0;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: middle; }
        .page { position: relative; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .underline { text-decoration: underline; }
        .box td, .box th {
            border: 1px solid #000;
            padding: 7px 8px;
            vertical-align: middle;
        }
        .box th {
            background: #f4f4f4;
            font-weight: bold;
        }
        .tight td, .tight th { padding: 6px 8px; }
        .signature { margin-top: 18px; page-break-inside: avoid; }
        .signature th { border: 1px solid #000; background: #f4f4f4; padding: 7px; }
        .signature td { border: 1px solid #000; text-align: center; }
    </style>
</head>
<body>
    @include('documents.partials.bc_header')
    @include('documents.partials.bc_footer')
<div class="page">

    <div style="border: 2px solid #000; text-align: center; font-weight: bold; padding: 8px; margin: 15px auto 18px; width: 75%;">
        <div style="font-size: 14px; text-transform: uppercase; margin-bottom: 4px;">PROCÈS-VERBAL DE LA RÉCEPTION {{ mb_strtoupper($doc['type_reception'] ?? 'DÉFINITIVE', 'UTF-8') }}</div>
        @if(strtolower($doc['type_reception'] ?? '') === 'partielle' && (!empty($doc['periode_du']) || !empty($doc['periode_au'])))
            <div style="font-size: 12px; margin-top: 4px;">
                Période : du <span style="font-weight: bold;">{{ $doc['periode_du'] ?? '.....' }}</span> au <span style="font-weight: bold;">{{ $doc['periode_au'] ?? '.....' }}</span>
            </div>
        @else
            <div style="font-size: 12px;">Bon de Commande N° : {{ $doc['numero_bc'] }}</div>
        @endif
    </div>

    <p style="text-align: justify; margin-bottom: 12px; line-height: 1.4;">
        En date du <span class="bold">{{ $doc['date_reunion'] }}</span> à <span class="bold">{{ $doc['heure_reunion'] }}</span>, la commission de réception instituée par la décision Numéro : <span class="bold">{{ $doc['numero_decision'] }}</span> en date du <span class="bold">{{ $doc['date_decision'] }}</span> pour la réception des prestations objet du bon de commande N° : <span class="bold">{{ $doc['numero_bc'] }}</span> est composée de :
    </p>

    <table class="box tight" style="margin-bottom: 12px;">
        <thead>
            <tr>
                <th style="width: 35%;">Nom et Prénom</th>
                <th style="width: 45%;">Fonction</th>
                <th style="width: 20%;">Qualité</th>
            </tr>
        </thead>
        <tbody>
            @foreach($doc['commission'] as $membre)
                <tr>
                    <td>{{ data_get($membre, 'nom') }}</td>
                    <td>{{ data_get($membre, 'fonction') }}</td>
                    <td class="center">{{ data_get($membre, 'qualite') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-bottom: 6px;">S’est réunie au siège de la DRCA-RSK en vue de procéder à la réception des prestations relatives au :</p>

    <p class="bold center" style="margin-bottom: 12px; font-size: 12px;">
        {{ $doc['objet'] }}
    </p>

    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="width: 22%;">Bon de Commande N° :</td>
            <td style="width: 38%; font-weight: bold;">{{ $doc['numero_bc'] }}</td>
            <td style="width: 22%;">établi(e) en date du</td>
            <td style="width: 18%; font-weight: bold;">{{ $doc['date_document'] }}</td>
        </tr>
    </table>

    <p style="margin-bottom: 6px;">Exécutées par l'attributaire :</p>

    <table class="box tight" style="margin-bottom: 14px;">
        <thead>
            <tr>
                <th style="width: 35%;">La société</th>
                <th style="width: 45%;">Adresse</th>
                <th style="width: 20%;">Ville</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="center bold">{{ $doc['societe'] }}</td>
                <td class="center">{{ $doc['adresse_societe'] }}</td>
                <td class="center">{{ $doc['ville_societe'] }}</td>
            </tr>
        </tbody>
    </table>

    <p style="text-align: justify; margin-bottom: 10px; line-height: 1.4;">
        À l’ouverture de la séance, la présidente rappelle aux membres de la commission l’objet de la réunion et fait une lecture du contenu du Bon de Commande susmentionné, notamment les désignations précisant les prestations demandées. Ensuite, la présidente demande aux membres de la commission de formuler leurs observations sur la conformité et la qualité des prestations livrées.
    </p>

    <p style="text-align: justify; margin-bottom: 12px; line-height: 1.4;">
        La commission atteste que les prestations objet du bon de commande N° : <span class="bold">{{ $doc['numero_bc'] }}</span> sont conformes aux dispositions prévues et certifie qu’elles sont {{ (isset($doc['type_reception']) && strtolower($doc['type_reception']) === 'partielle') ? 'partiellement' : 'définitivement' }} réceptionnées.
    </p>

    @if(strtolower($doc['type_reception'] ?? '') === 'partielle')
    <p class="bold" style="margin-bottom: 6px;">Comme suivant :</p>
    <table class="box tight" style="margin-bottom: 14px;">
        <thead>
            <tr>
                <th style="width: 22%;">N° de prix</th>
                <th style="width: 46%;">Désignation des prestations</th>
                <th style="width: 18%;">Unité de compte</th>
                <th style="width: 14%;">Quantité</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($doc['prestations_receptionnees']) && is_array($doc['prestations_receptionnees']))
                @foreach($doc['prestations_receptionnees'] as $item)
                    @if(data_get($item, 'receptionne', true))
                        <tr>
                            <td class="center">{{ data_get($item, 'numero_prix', $loop->iteration) }}</td>
                            <td>{{ data_get($item, 'designation') }}</td>
                            <td class="center">{{ data_get($item, 'unite', data_get($item, 'unite_mesure', 'Unité')) }}</td>
                            <td class="center">{{ data_get($item, 'quantite_receptionnee', data_get($item, 'quantite', 1)) }}</td>
                        </tr>
                    @endif
                @endforeach
            @else
                @foreach($doc['articles'] as $article)
                    <tr>
                        <td class="center">{{ data_get($article, 'numero_prix', $loop->iteration) }}</td>
                        <td>{{ data_get($article, 'designation') }}</td>
                        <td class="center">{{ data_get($article, 'unite', 'Unité') }}</td>
                        <td class="center">{{ data_get($article, 'quantite', 1) }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
    @endif

    <table style="width: 100%; margin-bottom: 14px;">
        <tr>
            <td style="width: 55%;">La séance est levée le même jour à <span class="bold">{{ $doc['heure_fin'] }}</span></td>
            <td style="width: 45%; text-align: right;">Fait à Kénitra, le : <span class="bold">{{ $doc['date_document'] }}</span></td>
        </tr>
    </table>

    <div class="center bold underline" style="margin-bottom: 8px;">Les membres de la commission : Signataires</div>

    @php
        $rawMembers = data_get($doc, 'commission', []);
        if (!is_array($rawMembers) || empty($rawMembers)) {
            $rawMembers = data_get($doc, 'membres_commission', []);
        }

        $president = null;
        $others = [];
        if (is_array($rawMembers)) {
            foreach ($rawMembers as $m) {
                $q = strtolower(data_get($m, 'qualite', ''));
                if (!$president && (str_contains($q, 'président') || str_contains($q, 'president'))) {
                    $president = $m;
                } else {
                    $others[] = $m;
                }
            }
        }

        if ($president) {
            $commissionMembers = array_merge([$president], $others);
        } else {
            $commissionMembers = is_array($rawMembers) && !empty($rawMembers) ? array_values($rawMembers) : [];
        }

        $memberCount = count($commissionMembers);
        $colWidth = floor(100 / max(1, $memberCount));
    @endphp

    <table class="signature" style="width: 100%;">
        <thead>
            <tr>
                @foreach($commissionMembers as $index => $m)
                    @php
                        $qualite = data_get($m, 'qualite', '');
                        if ($index === 0) {
                            $headerTitle = !empty($qualite) ? $qualite : 'Présidente';
                        } else {
                            $headerTitle = 'Membre ' . $index;
                        }
                    @endphp
                    <th style="width: {{ $colWidth }}%;">
                        {{ $headerTitle }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                @foreach($commissionMembers as $m)
                    <td class="center bold" style="padding: 5px; background: #fff;">
                        {{ data_get($m, 'nom', data_get($m, 'nom_prenom', '')) }}
                    </td>
                @endforeach
            </tr>
            <tr>
                @foreach($commissionMembers as $m)
                    <td style="height: 75px;"></td>
                @endforeach
            </tr>
        </tbody>
    </table>
</div>
</body>
</html>
