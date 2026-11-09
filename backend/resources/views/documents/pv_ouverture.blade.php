<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>PV d'ouverture des plis</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 16px 34px 96px 34px;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 10.5px;
            color: #000;
            margin: 0;
            padding: 0;
            line-height: 1.32;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            margin-bottom: 4px;
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-center {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            line-height: 1.3;
            padding: 0 8px;
        }
        .blue-line {
            border-bottom: 2px solid #000080;
            margin-bottom: 8px;
        }
        .title-box {
            border: 2px solid #000;
            text-align: center;
            font-weight: bold;
            font-size: 13.5px;
            padding: 5px 8px;
            margin: 0 auto 6px auto;
        }
        .ref-table {
            width: 100%;
            border: 1.5px solid #000;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .ref-table td {
            padding: 4px 10px;
            font-size: 11px;
            font-weight: bold;
            border: 1px solid #000;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            margin-bottom: 7px;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 4.5px 6px;
            font-size: 10.5px;
        }
        .data-table th {
            font-weight: bold;
            text-align: center;
            background-color: #f2f2f2;
        }
        .data-table td {
            text-align: center;
        }
        .data-table .col-left {
            text-align: left;
        }
        .text-block {
            margin-bottom: 5px;
            text-align: justify;
            font-size: 10.5px;
            line-height: 1.32;
        }
        .bold-text {
            font-weight: bold;
        }
        .closing-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 6px;
            font-size: 10.5px;
        }
        .closing-table td {
            padding: 2px 0;
            vertical-align: middle;
        }
        .signature-table {
            margin-top: 4px;
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            page-break-inside: avoid;
        }
        .signature-table th, .signature-table td {
            border: 1px solid #000;
            text-align: center;
            width: 33.33%;
            padding: 4px;
            font-size: 10.5px;
        }
        .signature-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .signature-table .sig-space {
            height: 44px;
        }
    </style>
    @include('documents.partials.bc_typography')
</head>
<body>

@php
    // Sécurisation des données avec les relations correctes de Consultation.php
    $offres = isset($consultation) && $consultation->offres ? $consultation->offres()->with('fournisseur')->get() : collect([]);
    $gagnant = $offres->where('retenu', true)->first();
    
    // Décodage des membres de commission depuis la DB
    $president = $consultation->president_commission ?? '.........................';
    $membres = is_array($consultation->membres_commission) ? $consultation->membres_commission : [];
    
    // Si la collection d'offres est vide, on simule pour le design afin de ne pas casser le rendu
    if($offres->isEmpty()) {
        $offres = collect([
            (object)[
                'id' => '1',
                'montant_propose' => 0,
                'montant_apres_verification' => 0,
                'fournisseur' => (object)['raison_sociale' => '.........................', 'adresse' => '...', 'ville' => '...']
            ]
        ]);
    }
    
    // Numéros de lettres pour la phrase "Objet des lettres..."
    $numerosLettres = $offres->pluck('id')->join(',');
    if(empty($numerosLettres)) $numerosLettres = '....................';
@endphp

    @include('documents.partials.bc_footer')

    <table class="header-table">
        <tr>
            <td style="width: 24%; text-align: left;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo-onca.png'))) }}" alt="Logo ONCA" style="height: 64px; width: auto;">
            </td>
            <td class="header-center" style="width: 52%;">
                Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
            </td>
            <td style="width: 24%; text-align: right;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) }}" alt="Sceau Maroc" style="height: 64px; width: auto;">
            </td>
        </tr>
    </table>
    <div class="blue-line"></div>

    <div class="title-box">
        PROCÈS-VERBAL D'OUVERTURE DES PLIS
    </div>

    <table class="ref-table">
        <tr>
            <td style="width: 50%; text-align: right; background: #f2f2f2;">Consultation</td>
            <td style="width: 50%; text-align: left;">{{ $consultation->numero_consultation }}</td>
        </tr>
    </table>

    <div class="text-block">
        En date du <span class="bold-text">{{ \Carbon\Carbon::parse($consultation->date_reunion ?? now())->format('d/m/Y') }}</span> à <span class="bold-text">{{ $consultation->heure_reunion ? \Carbon\Carbon::parse($consultation->heure_reunion)->format('H:i') : '10:00' }}</span>, la commission d'ouverture des plis instituée par la décision N° <span class="bold-text">{{ $consultation->numero_consultation }}</span> en date du <span class="bold-text">{{ \Carbon\Carbon::parse($consultation->date_consultation ?? now())->format('d/m/Y') }}</span>, composée de :
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 40%;" class="col-left">Nom et Prénom</th>
                <th style="width: 40%;" class="col-left">Fonction</th>
                <th style="width: 20%;" class="col-left">Qualité</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="col-left bold-text">{{ $president }}</td>
                <td class="col-left">Administrateur / SAF</td>
                <td class="col-left">Présidente</td>
            </tr>
            @if(count($membres) > 0)
                @foreach($membres as $membre)
                <tr>
                    <td class="col-left bold-text">{{ is_string($membre) ? $membre : ($membre['nom'] ?? '.........................') }}</td>
                    <td class="col-left">{{ is_string($membre) ? 'Technicien / SAF' : ($membre['fonction'] ?? '.........................') }}</td>
                    <td class="col-left">Membre</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td class="col-left">.........................</td>
                    <td class="col-left">.........................</td>
                    <td class="col-left">Membre</td>
                </tr>
                <tr>
                    <td class="col-left">.........................</td>
                    <td class="col-left">.........................</td>
                    <td class="col-left">Membre</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="text-block">
        S'est réunie au siège de la DRCA-RSK en vue de procéder à l'ouverture des plis concernant l'offre de prix N° <span class="bold-text">{{ $consultation->numero_consultation }}</span> relative à la prestation de :
    </div>

    <div class="text-block bold-text" style="font-size: 11px; margin-left: 8px;">
        {{ $consultation->objet_consultation }}
    </div>

    <div class="text-block">
        Objet des lettres de consultation N° : <span class="bold-text">{{ $numerosLettres }}/{{ \Carbon\Carbon::parse($consultation->date_consultation ?? now())->format('Y') }}/DRCA-RSK</span>
    </div>

    <div class="text-block">
        La liste des fournisseurs qui ont été respectivement consultés est la suivante :
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 6%;">N°</th>
                <th style="width: 44%;">Les Participants</th>
                <th style="width: 35%;">Adresses</th>
                <th style="width: 15%;">Ville</th>
            </tr>
        </thead>
        <tbody>
            @foreach($offres as $key => $offre)
            <tr>
                <td>{{ $key + 1 }}</td>
                <td class="col-left bold-text">{{ $offre->fournisseur->raison_sociale ?? '.........................' }}</td>
                <td class="col-left">{{ $offre->fournisseur->adresse ?? '.........................' }}</td>
                <td>{{ $offre->fournisseur->ville ?? '.........................' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="text-block">
        L'ouverture des plis contenant les propositions faites par les concurrents et parvenues par courrier ou déposées auprès de la DRCA-RSK a donné les résultats suivants :
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 6%;">N°</th>
                <th rowspan="2" style="width: 44%;">Nom des concurrents</th>
                <th colspan="2" style="width: 50%;">Montant de l'offre (TTC)</th>
            </tr>
            <tr>
                <th style="width: 25%;">Avant vérification</th>
                <th style="width: 25%;">Après vérification</th>
            </tr>
        </thead>
        <tbody>
            @foreach($offres as $key => $offre)
            <tr>
                <td>{{ $key + 1 }}</td>
                <td class="col-left bold-text">{{ $offre->fournisseur->raison_sociale ?? '.........................' }}</td>
                <td style="text-align: right; padding-right: 10px;">{{ number_format($offre->montant_propose ?? 0, 2, ',', ' ') }}</td>
                <td style="text-align: right; padding-right: 10px;">{{ number_format($offre->montant_apres_verification ?? $offre->montant_propose ?? 0, 2, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="text-block">
        Enfin, la commission a décidé de proposer à l'autorité compétente de retenir l'offre qu'elle juge la plus avantageuse pour l'administration, présentée par la société :
    </div>

    <table class="data-table">
        <tr>
            <td colspan="2" style="text-align: center; font-weight: bold; font-size: 11px; text-transform: uppercase;">
                {{ $gagnant->fournisseur->raison_sociale ?? data_get($doc ?? [], 'attributaire', '......................................................') }}
            </td>
        </tr>
        @if(!empty(data_get($doc ?? [], 'motif_attribution')) || !empty(data_get($documentData ?? [], 'motif_attribution')))
        <tr>
            <td class="col-left" style="width: 35%; font-weight: bold; background: #f2f2f2;">Motif de retenue :</td>
            <td class="col-left" style="width: 65%; font-style: italic;">{{ data_get($doc ?? [], 'motif_attribution', data_get($documentData ?? [], 'motif_attribution', '')) }}</td>
        </tr>
        @endif
        <tr>
            <td class="col-left" style="width: 35%; font-weight: bold; background: #f2f2f2;">Montant global (TTC) :</td>
            <td class="col-left bold-text" style="width: 65%; text-align: center;">{{ number_format($gagnant->montant_apres_verification ?? data_get($doc ?? [], 'montant_retenu', 0), 2, ',', ' ') }} DH TTC</td>
        </tr>
        <tr>
            <td class="col-left" style="font-weight: bold; background: #f2f2f2;">En lettres :</td>
            <td class="col-left bold-text">{{ \App\Support\MontantEnLettres::convert((float) ($gagnant->montant_apres_verification ?? data_get($doc ?? [], 'montant_retenu', 0))) }}</td>
        </tr>
    </table>

    <table class="closing-table">
        <tr>
            <td style="width: 55%;">
                La séance est levée le même jour à <span class="bold-text">{{ \Carbon\Carbon::parse($consultation->heure_reunion ?? '10:00')->addMinutes(45)->format('H:i') }}</span>
            </td>
            <td style="width: 45%; text-align: right;">
                Fait à Kénitra, le : <span class="bold-text">{{ \Carbon\Carbon::parse($consultation->date_reunion ?? now())->format('d/m/Y') }}</span>
            </td>
        </tr>
    </table>

    <table class="signature-table">
        <thead>
            <tr>
                <th>Présidente</th>
                <th>Membre 1</th>
                <th>Membre 2</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="bold-text" style="border-bottom: none;">{{ $president }}</td>
                <td class="bold-text" style="border-bottom: none;">{{ isset($membres[0]) ? (is_string($membres[0]) ? $membres[0] : ($membres[0]['nom'] ?? '...')) : '.........................' }}</td>
                <td class="bold-text" style="border-bottom: none;">{{ isset($membres[1]) ? (is_string($membres[1]) ? $membres[1] : ($membres[1]['nom'] ?? '...')) : '.........................' }}</td>
            </tr>
            <tr>
                <td class="sig-space" style="border-top: none;"></td>
                <td class="sig-space" style="border-top: none;"></td>
                <td class="sig-space" style="border-top: none;"></td>
            </tr>
        </tbody>
    </table>

</body>
</html>
