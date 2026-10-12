<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Acte d'engagement - {{ $titulaire_nom }}</title>
    <style>
        @page {
            margin: 12mm 15mm 15mm 15mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .font-bold {
            font-weight: bold;
        }
        .piece-header {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .piece-title {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 15px;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 12px;
            margin-bottom: 6px;
            text-decoration: underline;
        }
        .info-row {
            margin-bottom: 4px;
        }
        .indent {
            padding-left: 15px;
        }
        .amount-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            margin: 10px 0;
            line-height: 1.6;
        }
        .signature-box {
            margin-top: 25px;
            float: right;
            width: 50%;
            text-align: center;
        }
        .clearfix {
            clear: both;
        }
        .footer-line {
            margin-top: 30px;
            border-top: 1px solid #cbd5e1;
            padding-top: 5px;
            font-size: 8pt;
            text-align: center;
            color: #64748b;
        }
    </style>
</head>
<body>

    <div class="piece-header">PIÈCE N° 2</div>
    <div class="piece-header">MODÈLE D'ACTE D'ENGAGEMENT</div>
    <div class="piece-title">ACTE D'ENGAGEMENT</div>

    <!-- A - Partie réservée à l'Administration -->
    <div class="section-title">A - Partie réservée à l'Administration :</div>
    
    <div class="info-row">
        Appel d'offres ouvert national sur offres de prix n° <strong>{{ $num_aoo }}</strong> du <strong>{{ $date_ouverture }}</strong> à <strong>{{ $heure_ouverture }}</strong>.
    </div>
    <div class="info-row">
        <strong>Objet du marché :</strong> {{ $objet_marche }}
    </div>
    @if(!empty($lot_info))
    <div class="info-row">
        <strong>Lot concerné :</strong> {{ $lot_info }}
    </div>
    @endif

    <div class="info-row" style="margin-top: 6px; text-align: justify; font-size: 9.5pt;">
        Passé en application de l'alinéa 1 du paragraphe 1, l'alinéa a) du paragraphe 3 de la partie I) de l'article 19 et du paragraphe 1 de l'article 20 et de l'alinéa b du paragraphe 3 de l'article 20 du décret n° 2-22-431 du 15 Chaabane 1444 (08 mars 2023) relatif aux marchés publics.
    </div>

    <!-- B - Partie réservée au concurrent -->
    <div class="section-title">B - Partie réservée au concurrent :</div>
    
    <div class="info-row">
        Je soussigné : <strong>{{ $representant_nom }}</strong>, {{ $qualite_gerant ?: 'Gérant' }}, agissant au nom et pour le compte de <strong>{{ $titulaire_nom }}</strong>.
    </div>
    @if(!empty($capital))
    <div class="info-row">Au capital social de : <strong>{{ $capital }} DH</strong></div>
    @endif
    
    <div class="grid-info" style="margin-top: 4px;">
        @if(!empty($telephone)) <div>Téléphone : <strong>{{ $telephone }}</strong></div> @endif
        @if(!empty($email)) <div>Adresse électronique : <strong>{{ $email }}</strong></div> @endif
        @if(!empty($adresse)) <div>Adresse du siège social : <strong>{{ $adresse }} {{ !empty($ville) ? '- ' . $ville : '' }}</strong></div> @endif
        @if(!empty($cnss)) <div>Affilié à la CNSS sous le n° : <strong>{{ $cnss }}</strong></div> @endif
        @if(!empty($rc)) <div>Inscrit au registre du commerce de {{ $ville_rc ?: $ville }} sous le n° : <strong>{{ $rc }}</strong></div> @endif
        @if(!empty($patente)) <div>N° de la taxe professionnelle (Patente) : <strong>{{ $patente }}</strong></div> @endif
        @if(!empty($ice)) <div>Identifiant commun de l'entreprise (ICE) : <strong>{{ $ice }}</strong></div> @endif
    </div>

    <div style="margin-top: 10px; font-weight: bold;">En vertu des pouvoirs qui me sont conférés :</div>
    <div style="text-align: justify; margin-top: 4px;">
        Après avoir pris connaissance du dossier d'appel d'offres concernant les prestations précisées en objet de la partie A ci-dessus ;<br>
        Après avoir apprécié à mon point de vue et sous ma responsabilité la nature et les difficultés que comportent ces prestations :
    </div>

    <div class="indent" style="margin-top: 6px;">
        1. Remets, revêtu de ma signature un bordereau de prix, un détail estimatif établi conformément aux modèles figurant au dossier d'appel d'offres ;<br>
        2. M'engage à exécuter lesdites prestations conformément au cahier des prescriptions spéciales et moyennant les prix que j'ai établis moi-même, lesquels font ressortir :
    </div>

    <div class="amount-box">
        <div>➢ <strong>Montant Hors T.V.A :</strong> {{ $montant_ht }} DH (<em>{{ $montant_ht_lettres }}</em>)</div>
        <div>➢ <strong>Taux de la T.V.A :</strong> {{ $tva_rate }}%</div>
        <div>➢ <strong>Montant de la T.V.A :</strong> {{ $montant_tva }} DH (<em>{{ $montant_tva_lettres }}</em>)</div>
        <div>➢ <strong>Montant T.V.A Comprise :</strong> <strong>{{ $montant_ttc }} DH</strong> (<em>{{ $montant_ttc_lettres }}</em>)</div>
    </div>

    <div style="text-align: justify; margin-top: 8px;">
        La Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra se libérera des sommes dues par lui en faisant donner crédit au compte bancaire 
        <strong>{{ $banque ?: 'Bancaire' }}</strong> ouvert à <strong>{{ $agence_bancaire ?: ($ville ?: 'Kénitra') }}</strong>, 
        sous le relevé d'identification bancaire (RIB) n° : <strong>{{ $rib ?: 'N/A' }}</strong>.
    </div>

    <div class="signature-box">
        Fait à <strong>{{ $ville ?: 'Kénitra' }}</strong>, le <strong>{{ $date_acte }}</strong><br><br>
        <strong>[Signature et cachet du concurrent]</strong><br>
        <span style="font-size: 8pt; color: #475569;">(Précédé par la mention « Lu et Accepté »)</span>
    </div>
    <div class="clearfix"></div>

    <div class="footer-line">
        APPEL D'OFFRES OUVERT NATIONAL N° {{ $num_aoo }} - Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra
    </div>

</body>
</html>
