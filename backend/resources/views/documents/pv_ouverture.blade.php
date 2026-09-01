<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>PV d'ouverture des plis</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #000;
            margin: 10px;
            line-height: 1.4;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            margin-bottom: 5px;
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-center {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
        }
        .blue-line {
            border-bottom: 2px solid #000080;
            margin-bottom: 20px;
        }
        .title-box {
            border: 2px solid #000;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            padding: 5px;
            width: 60%;
            margin: 0 auto 15px auto;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }
        .info-grid td {
            padding: 3px;
        }
        .info-grid .border-cell {
            border: 1px solid #000;
            text-align: center;
            font-weight: bold;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 5px;
        }
        .data-table th {
            font-weight: bold;
            text-align: center;
            background-color: #e6f2ff; /* Bleu très clair style Excel */
        }
        .data-table td {
            text-align: center;
        }
        .data-table .col-left {
            text-align: left;
        }
        .text-block {
            margin-bottom: 15px;
            text-align: justify;
        }
        .bold-text {
            font-weight: bold;
        }
        .winner-box {
            margin-top: 10px;
            margin-bottom: 20px;
            padding-left: 20px;
        }
        .winner-name {
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
        }
        .amount-box {
            display: inline-block;
            border: 1px solid #000;
            padding: 2px 10px;
            font-weight: bold;
        }
        .signature-table {
            margin-top: 30px;
            width: 100%;
            border-collapse: collapse;
        }
        .signature-table th, .signature-table td {
            border: 1px solid #000;
            text-align: center;
            width: 33.33%;
            padding: 5px;
        }
        .signature-table th {
            background-color: #e6f2ff;
            font-weight: normal;
        }
        .signature-table .sig-space {
            height: 100px;
        }
    </style>
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
            <td style="width: 25%;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo-onca.png'))) }}" alt="Logo ONCA" style="height: 70px; width: auto;">
            </td>
            <td class="header-center" style="width: 50%;">
                Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
            </td>
            <td style="width: 25%; text-align: right;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) }}" alt="Sceau Maroc" style="height: 70px; width: auto;">
            </td>
        </tr>
    </table>
    <div class="blue-line"></div>

    <div class="title-box">
        PROCES VERBAL D'OUVERTURE DE PLIS
    </div>

    <table class="info-grid">
        <tr>
            <td style="text-align: right; width: 30%;">Consultation</td>
            <td class="border-cell" style="width: 30%;">{{ $consultation->numero_consultation }}</td>
            <td style="width: 40%;"></td>
        </tr>
    </table>

    <table class="info-grid" style="margin-bottom: 20px;">
        <tr>
            <td style="text-align: right; width: 15%;">En date du</td>
            <td class="border-cell" style="width: 15%;">{{ \Carbon\Carbon::parse($consultation->date_reunion ?? now())->format('d/m/Y') }}</td>
            <td style="text-align: center; width: 5%;">à</td>
            <td class="border-cell" style="width: 10%;">{{ $consultation->heure_reunion ? \Carbon\Carbon::parse($consultation->heure_reunion)->format('H:i') : '10:00' }}</td>
            <td style="padding-left: 10px;">la commission d'ouverture des plis instituée par la décision N°</td>
        </tr>
        <tr>
            <td class="border-cell" colspan="2">{{ $consultation->numero_consultation }}</td>
            <td style="text-align: center;" colspan="2">en date du</td>
            <td class="border-cell" style="width: 15%;">{{ \Carbon\Carbon::parse($consultation->date_consultation ?? now())->format('d/m/Y') }}</td>
            <td style="padding-left: 10px;">composée de :</td>
        </tr>
    </table>

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
                <td class="col-left">{{ $president }}</td>
                <td class="col-left">Administrateur / SAF</td>
                <td class="col-left">Présidente</td>
            </tr>
            @if(count($membres) > 0)
                @foreach($membres as $membre)
                <tr>
                    <td class="col-left">{{ is_string($membre) ? $membre : ($membre['nom'] ?? '.........................') }}</td>
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
        S'est réunie au siège de la DRCA-RSK en vue de procéder à l'ouverture des plis concernant l'offre de prix N° <span class="border-cell" style="display:inline-block; padding:0 5px;">{{ $consultation->numero_consultation }}</span> relative au prestation de :
    </div>

    <div class="text-block bold-text" style="font-size: 14px;">
        {{ $consultation->objet_consultation }}
    </div>

    <div class="text-block">
        Objet des lettres de consultation N° : <span class="bold-text">{{ $numerosLettres }}/{{ \Carbon\Carbon::parse($consultation->date_consultation ?? now())->format('Y') }}/DRCA-RSK</span>
    </div>

    <div class="text-block">
        La liste des fournisseurs qui ont été respectivement consultés sont:
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">N°</th>
                <th style="width: 45%;">Les Participants</th>
                <th style="width: 35%;">Adresses</th>
                <th style="width: 15%;">Ville</th>
            </tr>
        </thead>
        <tbody>
            @foreach($offres as $key => $offre)
            <tr>
                <td>{{ $key + 1 }}</td>
                <td class="col-left">{{ $offre->fournisseur->raison_sociale ?? '.........................' }}</td>
                <td class="col-left">{{ $offre->fournisseur->adresse ?? '.........................' }}</td>
                <td>{{ $offre->fournisseur->ville ?? '.........................' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="text-block">
        L'ouverture des plis contenant les propositions faites par les concurrents et parvenus par courrier ou déposés auprès de la DRCA-RSK a donné les résultats suivants:
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 5%;">N°</th>
                <th rowspan="2" style="width: 45%;">Nom des concurrents</th>
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
                <td class="col-left">{{ $offre->fournisseur->raison_sociale ?? '.........................' }}</td>
                <td style="text-align: right; padding-right: 10px;">{{ number_format($offre->montant_propose ?? 0, 2, ',', ' ') }}</td>
                <td style="text-align: right; padding-right: 10px;">{{ number_format($offre->montant_apres_verification ?? $offre->montant_propose ?? 0, 2, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="winner-box">
        Enfin, la commission a décidé de proposer à l'autorité compétente de retenir l'offre qu'elle juge la plus avantageuse pour l'administration présentée ici par la société :<br>
        <div class="winner-name">
            {{ $gagnant->fournisseur->raison_sociale ?? data_get($doc ?? [], 'attributaire', '......................................................') }}
        </div>
        @if(!empty(data_get($doc ?? [], 'motif_attribution')) || !empty(data_get($documentData ?? [], 'motif_attribution')))
        <div style="margin: 5px 0; font-size: 11px; font-style: italic;">
            <strong>Motif de retenu :</strong> {{ data_get($doc ?? [], 'motif_attribution', data_get($documentData ?? [], 'motif_attribution', '')) }}
        </div>
        @endif
        Pour un montant global (TTC) de <div class="amount-box">{{ number_format($gagnant->montant_apres_verification ?? data_get($doc ?? [], 'montant_retenu', 0), 2, ',', ' ') }}</div> DH<br>
        <div style="margin-top: 5px;">
            En lettres : <span class="border-cell" style="display:inline-block; padding: 2px 10px; min-width: 50%;">{{ $montantGagnantEnLettres ?? data_get($doc ?? [], 'montant_en_lettres', '#NOM?') }}</span>
        </div>
    </div>

    <table class="info-grid" style="margin-top: 20px;">
        <tr>
            <td style="width: 30%;">La séance est levée le même jour</td>
            <td style="width: 5%; text-align: center;">à</td>
            <td class="border-cell" style="width: 15%;">{{ \Carbon\Carbon::parse($consultation->heure_reunion ?? '10:00')->addMinutes(45)->format('H:i') }}</td>
            <td style="width: 10%;"></td>
            <td style="width: 15%; text-align: right;">Fait à Kénitra le:</td>
            <td style="width: 25%;">{{ \Carbon\Carbon::parse($consultation->date_reunion ?? now())->format('d/m/Y') }}</td>
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
                <td style="border-bottom: none;">{{ $president }}</td>
                <td style="border-bottom: none;">{{ isset($membres[0]) ? (is_string($membres[0]) ? $membres[0] : ($membres[0]['nom'] ?? '...')) : '.........................' }}</td>
                <td style="border-bottom: none;">{{ isset($membres[1]) ? (is_string($membres[1]) ? $membres[1] : ($membres[1]['nom'] ?? '...')) : '.........................' }}</td>
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
