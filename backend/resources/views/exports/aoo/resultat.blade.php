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
            line-height: 1.4;
        }

        .page-wrapper {
            margin: 10px;
            padding: 10px;
        }

        /* --- En-tête (Logos et Titres) --- */
        .header-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-title {
            text-align: center;
            font-size: 15px;
        }

        .main-title-container {
            text-align: center;
            margin: 5px 0 20px 0;
        }
        .main-title {
            display: block;
            border: 2px solid #000;
            padding: 5px;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            width: 80%;
            margin: 0 auto;
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
            text-decoration: underline;
        }
        
        .section-title {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 15px;
            margin-bottom: 5px;
            font-size: 13px;
        }
        
        .sub-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
            font-size: 13px;
        }

        /* --- Tableaux des listes --- */
        .list-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .list-table td {
            border: 1.5px solid #000;
            padding: 4px 8px;
            width: 50%;
            height: 25px;
            vertical-align: middle;
        }

        .signature {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <div class="page-wrapper">
        <div class="header-table">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 25%; vertical-align: bottom;">
                        @if(file_exists(public_path('images/logo-onca.png')))
                            <img src="{{ public_path('images/logo-onca.png') }}" height="85" style="display: block; margin-bottom: 5px;" alt="ONCA">
                        @endif
                    </td>
                    <td style="width: 50%; vertical-align: bottom; padding-bottom: 15px;" class="header-title">
                        <span style="font-family: 'Times New Roman', Times, serif; font-weight: normal; font-size: 16px;">
                            Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
                        </span>
                    </td>
                    <td style="width: 25%; vertical-align: bottom; text-align: right;">
                        @if(file_exists(public_path('images/sceau-maroc.png')))
                            <img src="{{ public_path('images/sceau-maroc.png') }}" height="85" style="display: block; margin-left: auto; margin-bottom: 5px;" alt="Royaume du Maroc">
                        @endif
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
                <td style="width: 25%;"><span class="label">1.Appel d'offres Numéro</span></td>
                <td style="width: 75%; font-weight: bold; padding-left: 10px;">{{ $aoo->num_aoo }} &nbsp;&nbsp;&nbsp;&nbsp; du {{ $aoo->date_ouverture ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') : '-' }} &nbsp;&nbsp;&nbsp;&nbsp; lot unique</td>
            </tr>
            <tr>
                <td><span class="label">2.Objet de l'appel d'offres</span></td>
                <td style="font-weight: bold; text-transform: uppercase; padding-left: 10px;">{{ $aoo->objet }}</td>
            </tr>
            <tr>
                <td><span class="label">3.Maitre d'ouvrage</span></td>
                <td style="font-weight: bold; padding-left: 10px;">Directeur régional du conseil agricole Rabat-Salé-Kénitra</td>
            </tr>
            <tr>
                <td><span class="label">4.Lieu d'ouverture des plis:</span></td>
                <td style="font-weight: bold; padding-left: 10px;">{{ $aoo->lieu_ouverture }}</td>
            </tr>
            <tr>
                <td><span class="label">5. journaux de publication</span></td>
                <td style="font-weight: bold; padding-left: 10px;">
                    Arabe &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: &nbsp;&nbsp;{{ (!empty($aoo->journal_ar) && !preg_match('/^\?+$/', trim($aoo->journal_ar))) ? $aoo->journal_ar : 'الصحراء المغربية' }}<br>
                    Français : &nbsp;&nbsp;{{ (!empty($aoo->journal_fr) && !preg_match('/^\?+$/', trim($aoo->journal_fr))) ? $aoo->journal_fr : 'Le Matin' }}
                </td>
            </tr>
            <tr>
                <td><span class="label">6.Site électronique</span></td>
                <td style="font-weight: bold; padding-left: 10px; text-decoration: underline;">www.marchespublics.gov.ma</td>
            </tr>
        </table>

        @php
            // Rejetés
            $ecartes = $aoo->concurrents->whereIn('statut_analyse', ['ecarte', 'rejete', 'rejete_admin', 'rejete_tech']);
            
            // Admissibles
            $admissibles = $aoo->concurrents->whereIn('statut_analyse', ['admis', 'retenu', 'retenu_provisoire']);
            
            // Attributaire
            $attributaire = $aoo->concurrents->whereIn('statut_analyse', ['retenu', 'retenu_provisoire'])->first();
            if (!$attributaire) {
                $attributaire = $aoo->concurrents->where('statut_analyse', 'admis')->where('classement', 1)->first();
            }
        @endphp

        <div class="section-title">7.Lise des concurrents ayant déposé les plis:</div>
        <div class="sub-title">Dépôt support papier</div>
        <table class="list-table">
            @for ($i = 0; $i < 4; $i++)
                <tr>
                    <td>* Société : </td>
                    <td>* Société : </td>
                </tr>
            @endfor
        </table>

        <div class="sub-title">Dépôt électronique</div>
        <table class="list-table">
            @php $concurrentsArray = $aoo->concurrents->values(); @endphp
            @for ($i = 0; $i < max(6, ceil($concurrentsArray->count() / 2)); $i++)
                <tr>
                    <td>* Société : {{ isset($concurrentsArray[$i*2]) ? $concurrentsArray[$i*2]->nom_soumissionnaire : '' }}</td>
                    <td>* Société : {{ isset($concurrentsArray[$i*2+1]) ? $concurrentsArray[$i*2+1]->nom_soumissionnaire : '' }}</td>
                </tr>
            @endfor
        </table>

        <div class="section-title">8.Liste des concurrents évincés à l'issue de l'examen des dossiers administratifs et techniques:</div>
        <table class="list-table">
            @php $ecartesArray = $ecartes->values(); @endphp
            @for ($i = 0; $i < max(2, ceil($ecartesArray->count() / 2)); $i++)
                <tr>
                    <td>* Société : {{ isset($ecartesArray[$i*2]) ? $ecartesArray[$i*2]->nom_soumissionnaire : '' }}</td>
                    <td>* Société : {{ isset($ecartesArray[$i*2+1]) ? $ecartesArray[$i*2+1]->nom_soumissionnaire : '' }}</td>
                </tr>
            @endfor
        </table>

        <div class="section-title">9.Liste des concurrents admissibles sans reserves:</div>
        <table class="list-table">
            @php $admissiblesArray = $admissibles->values(); @endphp
            @for ($i = 0; $i < max(3, ceil($admissiblesArray->count() / 2)); $i++)
                <tr>
                    <td>* Société : {{ isset($admissiblesArray[$i*2]) ? $admissiblesArray[$i*2]->nom_soumissionnaire : '' }}</td>
                    <td>* Société : {{ isset($admissiblesArray[$i*2+1]) ? $admissiblesArray[$i*2+1]->nom_soumissionnaire : '' }}</td>
                </tr>
            @endfor
        </table>

        <div class="section-title">10.Liste des concurrents admissibles avec reserve:</div>
        <table class="list-table">
            @for ($i = 0; $i < 2; $i++)
                <tr>
                    <td>* </td>
                    <td>* </td>
                </tr>
            @endfor
        </table>

        <div class="section-title">11.Liste des concurrents évincés à l'issue de l'examen des Dossier Additif:</div>
        <table class="list-table">
            <tr>
                <td>* Société : </td>
                <td>* Société : </td>
            </tr>
        </table>

        <div class="section-title">12.Offes Financières: montants des actes d'engagements des soumissionnaires:</div>
        <table class="list-table">
            @for ($i = 0; $i < max(4, ceil($admissiblesArray->count() / 2)); $i++)
                <tr>
                    <td>* Société : {{ isset($admissiblesArray[$i*2]) ? $admissiblesArray[$i*2]->nom_soumissionnaire . ' (' . number_format((float)($admissiblesArray[$i*2]->montant_engagement ?? 0), 2, ',', ' ') . ' DH)' : '' }}</td>
                    <td>* Société : {{ isset($admissiblesArray[$i*2+1]) ? $admissiblesArray[$i*2+1]->nom_soumissionnaire . ' (' . number_format((float)($admissiblesArray[$i*2+1]->montant_engagement ?? 0), 2, ',', ' ') . ' DH)' : '' }}</td>
                </tr>
            @endfor
            <tr>
                <td>* 0</td>
                <td>* </td>
            </tr>
            <tr>
                <td>* Néant</td>
                <td>* </td>
            </tr>
        </table>

        <div class="section-title">13.Soumissionnaire retenu - Attrbutaire du Marché:</div>
        <div style="margin-bottom: 10px;">
            Société : &nbsp;&nbsp;&nbsp; <span style="font-weight: bold; border-bottom: 1px solid #000; padding: 0 20px;">{{ $attributaire ? $attributaire->nom_soumissionnaire : '-' }}</span>
        </div>

        <div class="section-title">14.Justification du choix de l'attrbutaire:</div>
        <div style="margin-bottom: 15px;">
            Offre interessante et satisfait les conditions techniques et administratives exigées par le réglemnt de l'appel d'offre
        </div>

        <div class="section-title" style="display: inline-block;">15. Date d'achévement des travaux de la commission :</div>
        <div style="display: inline-block; background-color: #fce4e4; padding: 2px 15px; margin-left: 20px;">
            {{ \Carbon\Carbon::parse($aoo->updated_at)->format('d/m/Y') }}
        </div>

        <div class="signature">
            Signé : Le président de la commission
        </div>

    </div>

</body>
</html>
