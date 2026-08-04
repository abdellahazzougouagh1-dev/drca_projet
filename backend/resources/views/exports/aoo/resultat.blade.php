<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Résultat d'Appel d'Offres Ouvert</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #000;
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }

        .page-wrapper {
            margin: 15px;
            padding: 15px;
            border: 4px double #0000cc; /* Double bordure bleue comme sur la capture */
            min-height: 950px;
        }

        /* --- En-tête (Logos et Titres) --- */
        .header-table {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
        }

        /* --- Titre Principal --- */
        .main-title-container {
            text-align: center;
            margin: 20px 0;
        }
        .main-title {
            display: inline-block;
            border: 2px solid #000;
            padding: 8px 15px;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* --- Structure des Données --- */
        .data-table {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }
        .data-table td {
            padding: 4px 0;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            text-decoration: underline;
        }
        
        .section-title {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 15px;
            margin-bottom: 5px;
        }

        /* --- Tableaux des listes --- */
        .list-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .list-table td {
            border: 1px solid #000;
            padding: 4px 8px;
            width: 50%;
        }

        .finance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .finance-table td {
            border: 1px solid #000;
            padding: 4px 8px;
        }
        
        .signature {
            text-align: center;
            margin-top: 40px;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <div class="page-wrapper">
        <div class="header-table">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 30%; vertical-align: middle;">
                        <img src="data:image/png;base64,{{ file_exists(public_path('images/logo-onca.png')) ? base64_encode(file_get_contents(public_path('images/logo-onca.png'))) : '' }}" height="55" style="display: block; margin-bottom: 5px;" alt="ONCA">
                    </td>
                    <td style="width: 40%; vertical-align: middle;" class="header-title">
                        Direction Régionale du Conseil Agricole<br>
                        Rabat-Salé-Kénitra
                    </td>
                    <td style="width: 30%; vertical-align: middle; text-align: right;">
                        <img src="data:image/png;base64,{{ file_exists(public_path('images/sceau-maroc.png')) ? base64_encode(file_get_contents(public_path('images/sceau-maroc.png'))) : '' }}" height="50" style="display: block; margin-left: auto;" alt="Royaume du Maroc">
                    </td>
                </tr>
            </table>
        </div>

        <div class="main-title-container">
            <div class="main-title">
                RESULTAT D'APPEL D'OFFRES OUVERT
            </div>
        </div>

        <table class="data-table">
            <tr>
                <td style="width: 30%;"><span class="label">1. Appel d'offres Numéro :</span></td>
                <td style="width: 70%; font-weight: bold;">{{ $aoo->num_aoo }} du {{ $aoo->date_ouverture ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') : '-' }} &nbsp;&nbsp;&nbsp; lot unique</td>
            </tr>
            <tr>
                <td><span class="label">2. Objet de l'appel d'offres :</span></td>
                <td style="text-transform: uppercase;">{{ $aoo->objet }}</td>
            </tr>
            <tr>
                <td><span class="label">3. Maître d'ouvrage :</span></td>
                <td>Directeur régional du conseil agricole Rabat-Salé-Kénitra</td>
            </tr>
            <tr>
                <td><span class="label">4. Lieu d'ouverture des plis :</span></td>
                <td>{{ $aoo->lieu_ouverture }}</td>
            </tr>
            <tr>
                <td><span class="label">5. Journaux de publication :</span></td>
                <td>
                    Arabe : {{ $aoo->journal_ar ?? '-' }}<br>
                    Français : {{ $aoo->journal_fr ?? '-' }}
                </td>
            </tr>
            <tr>
                <td><span class="label">6. Site électronique :</span></td>
                <td style="font-weight: bold; text-decoration: underline;">www.marchespublics.gov.ma</td>
            </tr>
        </table>

        @php
            $ecartes = $aoo->concurrents->where('statut_analyse', 'ecarte');
            $retenus = $aoo->concurrents->where('statut_analyse', 'retenu');
            $attributaire = $retenus->first();
        @endphp

        <div class="section-title">7. Liste des concurrents ayant déposé les plis :</div>
        <div style="font-weight: bold; margin-bottom: 5px;">Dépôt support papier</div>
        <table class="list-table">
            @foreach($aoo->concurrents->chunk(2) as $chunk)
                <tr>
                    @foreach($chunk as $concurrent)
                        <td>* Société : {{ $concurrent->nom_soumissionnaire }}</td>
                    @endforeach
                    @if($chunk->count() == 1)
                        <td></td>
                    @endif
                </tr>
            @endforeach
        </table>

        <div class="section-title">8. Liste des concurrents évincés à l'issue de l'examen des dossiers administratifs et techniques :</div>
        @if($ecartes->count() > 0)
            <table class="list-table">
                @foreach($ecartes->chunk(2) as $chunk)
                    <tr>
                        @foreach($chunk as $concurrent)
                            <td>* Société : {{ $concurrent->nom_soumissionnaire }}</td>
                        @endforeach
                        @if($chunk->count() == 1)
                            <td></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        @else
            <div>* Néant</div>
        @endif

        <div class="section-title">9. Liste des concurrents admissibles sans réserves :</div>
        @if($retenus->count() > 0)
            <table class="list-table">
                @foreach($retenus->chunk(2) as $chunk)
                    <tr>
                        @foreach($chunk as $concurrent)
                            <td>* Société : {{ $concurrent->nom_soumissionnaire }}</td>
                        @endforeach
                        @if($chunk->count() == 1)
                            <td></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        @else
            <div>* Néant</div>
        @endif

        <div class="section-title">10. Liste des concurrents admissibles avec réserve :</div>
        <div>* Néant</div>

        <div class="section-title">11. Liste des concurrents évincés à l'issue de l'examen des dossiers additifs :</div>
        <div>* Néant</div>

        <div class="section-title">12. Offres financières : montants des actes d'engagements des soumissionnaires :</div>
        <table class="finance-table">
            @foreach($aoo->concurrents->chunk(2) as $chunk)
                <tr>
                    @foreach($chunk as $concurrent)
                        <td style="width: 35%;">* Société : {{ $concurrent->nom_soumissionnaire }}</td>
                        <td style="width: 15%; text-align: right;">{{ $concurrent->montant_engagement ? number_format($concurrent->montant_engagement, 2, ',', ' ') . ' DH' : '-' }}</td>
                    @endforeach
                    @if($chunk->count() == 1)
                        <td style="width: 35%;"></td><td style="width: 15%;"></td>
                    @endif
                </tr>
            @endforeach
        </table>

        <table style="width: 100%; margin-top: 15px;">
            <tr>
                <td style="width: 35%;"><span class="section-title">13. Soumissionnaire retenu :</span></td>
                <td style="width: 65%;">Société : <span style="font-weight: bold;">{{ $attributaire ? $attributaire->nom_soumissionnaire : '__________________' }}</span></td>
            </tr>
            <tr>
                <td colspan="2"><span class="section-title" style="display: block; margin-top: 10px;">14. Justification du choix de l'attributaire :</span></td>
            </tr>
            <tr>
                <td colspan="2">Offre intéressante et satisfait les conditions techniques et administratives exigées par le règlement de l'appel d'offre</td>
            </tr>
            <tr>
                <td colspan="2"><span class="section-title" style="display: inline-block; margin-top: 10px; margin-right: 10px;">15. Date d'achèvement des travaux de la commission :</span> <span style="background-color: #fce4e4; padding: 2px 10px; font-weight: bold;">{{ \Carbon\Carbon::parse($aoo->updated_at)->format('d/m/Y') }}</span></td>
            </tr>
        </table>

        <div class="signature">
            Signé : Le président de la commission
        </div>

    </div>

</body>
</html>
