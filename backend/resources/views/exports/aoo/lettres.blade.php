<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Lettres d'Écartement et d'Attribution</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }

        /* --- En-tête (Logos et Titres) --- */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #000;
            margin-bottom: 30px;
            padding-bottom: 10px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo-onca {
            width: 180px;
            height: auto;
        }
        .logo-royaume {
            width: 120px;
            height: auto;
            text-align: right;
        }
        .header-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
        }

        /* --- Structure de la Lettre --- */
        .letter-content {
            margin: 20px 40px;
        }
        
        .destinataire-block {
            margin-left: 50%;
            margin-bottom: 40px;
            font-weight: bold;
            font-size: 14px;
            line-height: 1.6;
        }

        .reference-block {
            margin-bottom: 30px;
            font-size: 13px;
        }
        .reference-block table {
            width: 100%;
        }
        .reference-block td {
            padding: 3px 0;
        }
        .ref-label {
            font-weight: bold;
            text-decoration: underline;
            width: 120px;
        }

        .corps-texte {
            text-align: justify;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .motif-block {
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
            font-style: italic;
        }

        .pieces-fournir {
            margin-top: 20px;
        }
        .pieces-fournir ul {
            margin-top: 5px;
            padding-left: 20px;
        }
        .pieces-fournir li {
            margin-bottom: 5px;
            text-align: justify;
        }

        .politesse {
            margin-top: 40px;
            margin-bottom: 60px;
            text-align: center;
            font-weight: bold;
            font-size: 13px;
        }

        /* --- Footer Officiel --- */
        .footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            border-top: 1px solid #000;
            padding-top: 10px;
            font-size: 10px;
            text-align: center;
        }
        .footer-table {
            width: 100%;
        }
        .footer-logo {
            width: 80px;
            height: auto;
        }
        .footer-text {
            color: #666;
            line-height: 1.3;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    @foreach($concurrents as $index => $concurrent)
        
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

        <div class="letter-content">
            <div class="destinataire-block">
                {{ $concurrent->signataire_titre ?? 'Le Président de la commission d\'appel d\'offre' }}<br>
                A<br>
                {{ $concurrent->gerant_nom ?? 'Mme/Mr Le Gérant de la Société' }}<br>
                {{ $concurrent->nom_soumissionnaire }}
            </div>

            <div class="reference-block">
                <table>
                    <tr>
                        <td class="ref-label">Objet :</td>
                        <td>
                            @if($concurrent->statut_analyse === 'retenu')
                                Lettre d'attribution
                            @else
                                Lettre d'écartement
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="ref-label">Référence :</td>
                        <td>{{ $concurrent->ref_courrier ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="ref-label">Appel d'offre N° :</td>
                        <td>
                            {{ $aoo->num_aoo }} du {{ $aoo->date_ouverture ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') : '-' }}
                        </td>
                    </tr>
                </table>
            </div>

            <div style="font-weight: bold; margin-bottom: 20px;">Monsieur / Madame,</div>

            <div class="corps-texte">
                @if($concurrent->statut_analyse === 'retenu')
                    J'ai l'honneur de vous informer que votre offre concernant l'appel d'offres ouvert N° <strong>{{ $aoo->num_aoo }}</strong> du <strong>{{ $aoo->date_ouverture ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') : '-' }}</strong> ayant pour objet : <strong>{{ $aoo->objet }}</strong>, a été retenue par la commission d'appel d'offres pour un montant de <strong>{{ number_format($concurrent->montant_engagement, 2, ',', ' ') }} DH TTC</strong>.
                    
                    <div class="pieces-fournir">
                        <br><strong>NB: Les pièces à fournir</strong>
                        <ul>
                            <li>Une attestation ou sa copie certifiée conforme délivrée depuis moins d'un an par la CNSS certifiant que le concurrent est en situation régulière envers cet organisme.</li>
                            <li>La ou les pièces justifiant les pouvoirs conférés à la personne agissant au nom de la Société. Ces pièces varient selon la forme juridique du concurrent.</li>
                            <li>Une attestation ou sa copie certifiée conforme délivrée depuis moins d'un an par l'Administration compétente du lieu d'imposition certifiant que le concurrent est en situation fiscale régulière.</li>
                            <li>Un certificat d'immatriculation au registre de commerce.</li>
                        </ul>
                    </div>
                @else
                    En application des dispositions de l'article 41 du Décret n°2-12-349 du 8 joumada I 1433 (20 Mars 2013) relatif aux marchés publics publié au bulletin officiel n° 6140 du 4 avril 2013, j'ai le regret de vous informer sur l'écartement de votre offre concernant l'appel d'offres ouvert N°: <strong>{{ $aoo->num_aoo }}</strong> du <strong>{{ $aoo->date_ouverture ? \Carbon\Carbon::parse($aoo->date_ouverture)->format('d/m/Y') : '-' }}</strong>.
                    <br><br>
                    Ayant pour objet : <strong>{{ $aoo->objet }}</strong>
                    <br><br>
                    Il est à signaler que votre offre a été écartée pour le (s) motif (s) ci-dessous :
                    <div class="motif-block">
                        {!! nl2br(e($concurrent->motif_ecartement ?? 'Motif non spécifié.')) !!}
                    </div>
                @endif
            </div>

            <div class="politesse">
                Veuillez agréer, Messieurs, mes salutations.
            </div>
        </div>

        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td style="width: 20%;">
                        <!-- Optional footer left logo like Generation Green if needed -->
                    </td>
                    <td style="width: 60%;" class="footer-text">
                        Direction Régionale du Conseil Agricole de la Région de Rabat Salé Kénitra, Angle Rue Sebta - Bd Mohamed V (à côté de Bank Al-Maghreb) - Kénitra<br>
                        Tél. : +212 (0) 537 32 55 99 - Fax : +212 (0) 537 36 13 20 - Site web : www.onca.gov.ma<br>
                        <strong>Génération Green 2020-2030</strong>
                    </td>
                    <td style="width: 20%; text-align: right;">
                        <!-- Optional footer right logo -->
                    </td>
                </tr>
            </table>
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif

    @endforeach

</body>
</html>
