<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Acte d'Engagement</title>
    <style>
        @page {
            margin: 20mm 15mm;
        }
        body { 
            font-family: "Times New Roman", Times, serif; 
            font-size: 14px; 
            line-height: 1.3; 
            margin: 0; 
            padding: 0;
            color: #000;
        }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .underline { text-decoration: underline; }
        .italic { font-style: italic; }
        .mb-1 { margin-bottom: 5px; }
        .mb-2 { margin-bottom: 10px; }
        .mb-4 { margin-bottom: 15px; }
        .mt-4 { margin-top: 15px; }
        .title { 
            font-size: 15px; 
            font-weight: bold; 
            text-align: center; 
            margin-bottom: 20px; 
            text-transform: uppercase; 
            line-height: 1.2; 
        }
        .section { margin-bottom: 8px; text-align: justify; }
        .table-prices { width: 100%; border: none; margin-left: 0px; margin-top: 10px; margin-bottom: 15px; }
        .table-prices td { padding: 2px; vertical-align: top; font-size: 14px; }
        .bullet { width: 15px; font-weight: normal; }
        .footer { font-size: 11px; margin-top: 30px; border-top: 1px solid #000; padding-top: 5px; }
    </style>
</head>
<body>

    <div class="title">
        PIECE N° 2<br>
        MODELE D'ACTE D'ENGAGEMENT<br>
        ACTE D'ENGAGEMENT
    </div>

    <div class="section font-bold underline italic mb-4">
        A - Partie réservée à l'Administration :
    </div>

    <div class="section mb-2">
        Appel d'offres ouvert national sur offres de prix n° <strong>{{ $marche->aoo->num_aoo ?? '06/2026/DRCA-RSK' }}</strong> du <strong>{{ $marche->aoo->date_ouverture ? \Carbon\Carbon::parse($marche->aoo->date_ouverture)->format('d/m/Y') : '10/06/2026' }}</strong> à <strong>{{ $marche->aoo->heure_ouverture ? \Carbon\Carbon::parse($marche->aoo->heure_ouverture)->format('H\h i\m\n') : '10 Heures 00 mn' }}</strong>.
    </div>

    <div class="section mb-2">
        <span class="font-bold underline">Objet du marché</span> : <strong>{{ $marche->aoo->objet ?? $marche->objet_marche }}</strong><br>
        @if($marche->lot && strtolower(trim($marche->lot)) !== 'lot unique' && strtolower(trim($marche->lot)) !== 'unique')
        {{ stripos($marche->lot, 'lot') !== false ? $marche->lot : 'Lot ' . $marche->lot }} : {{ $marche->objet_marche }}
        @endif
    </div>

    <div class="section mb-4">
        Passé en application de l'alinéa 1 du paragraphe 1, l'alinéa a) du paragraphe 3 de la partie I) de l'article 19 et du paragraphe 1 de l'article 20 et de l'alinéa b du paragraphe 3 de l'article 20 du décret n° 2-22-431 du 15 Chaabane 1444 (08 mars 2023) relatif aux marchés publics.
    </div>

    <div class="section font-bold text-center mt-4 mb-1">
        B - Partie réservée au concurrent
    </div>
    <div class="section font-bold mb-2 text-center">
        a) Pour les personnes morales
    </div>

    <div class="section mb-2">
        Je soussigné <strong>{{ strtoupper($marche->fournisseur->representant ?? '........................................') }}</strong>, gérant, agissant au nom et pour le compte de <strong>{{ strtoupper($marche->fournisseur->raison_sociale ?? '........................................') }}</strong>,<br>
        Au capital social de : <strong>10 000,00 Dh</strong><br>
        Numéro téléphone : .<strong>{{ $marche->fournisseur->telephone ?? '........................' }}</strong>..........................................<br>
        Numéro du fax : ....................................................................................................................<br>
        Adresse électronique : ...<strong>{{ $marche->fournisseur->email ?? '........................' }}</strong>.<br>
        Adresse du siège social de la société : <strong>{{ $marche->fournisseur->adresse ?? '........................................' }}, {{ $marche->fournisseur->ville ?? '' }} - MAROC</strong><br>
        Adresse du domicile élu : ............ <strong>{{ $marche->fournisseur->adresse ?? '........................................' }}, {{ $marche->fournisseur->ville ?? '' }} - MAROC</strong><br>
        Affiliée à la CNSS, sous le numéro :(2) ............. <strong>{{ $marche->fournisseur->cnss ?? '........................' }}</strong>...................................................<br>
        Inscrite au registre du commerce...<strong>{{ $marche->fournisseur->ville ?? '........................' }}</strong> sous le numéro : .......<strong>{{ $marche->fournisseur->rc ?? '........................' }}</strong>........................<br>
        N°de la taxe professionnelle sous le numéro : ......... <strong>{{ $marche->fournisseur->patente ?? '........................' }}</strong>..................................<br>
        Numéro de l'identifiant commun de l'entreprise : .............. <strong>{{ $marche->fournisseur->ice ?? '........................' }}</strong>.
    </div>

    <div class="section font-bold mb-1">
        En vertu des pouvoirs qui me sont conférés :
    </div>
    <div class="section mb-1">
        Après avoir pris connaissance du dossier d'appel d'offres concernant les prestations précisées en objet de la 
        partie A ci-dessus ;
    </div>
    <div class="section mb-1">
        Après avoir apprécié à mon point de vue et sous ma responsabilité la nature et les difficultés que comportent 
        ces prestations :
    </div>

    <div class="section mb-2" style="margin-left: 15px;">
        1. Remets, revêtu de ma signature un bordereau de prix, un détail estimatif et/ou la décomposition du montant global) établi (s) conformément aux modèles figurant au dossier d'appel d'offres ;<br>
        2. M'engage à exécuter lesdites prestations conformément au cahier des prescriptions spéciales et moyennant les prix que j'ai établi moi-même, lesquels font ressortir :
    </div>

    @php
        $montantHt = $marche->montant_ht ?? 0;
        $montantTva = $marche->montant_tva ?? 0;
        $montantTtc = $marche->montant ?? 0;
        $tauxTva = $marche->taux_tva ?? 20;

        $htWords = \App\Helpers\NumberToWordsHelper::toFrenchWords($montantHt);
        $tvaWords = \App\Helpers\NumberToWordsHelper::toFrenchWords($montantTva);
        $ttcWords = \App\Helpers\NumberToWordsHelper::toFrenchWords($montantTtc);
    @endphp

    <table class="table-prices mb-2">
        <tr>
            <td class="bullet">➢</td>
            <td>Montant Hors T.V.A : <strong>{{ ucfirst($htWords) }} Dirhams [{{ number_format($montantHt, 2, ',', ' ') }}Dh]</strong></td>
        </tr>
        <tr>
            <td class="bullet">➢</td>
            <td>Taux de la T.V.A &nbsp;&nbsp;&nbsp;&nbsp; Prix 1 = &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>{{ number_format($tauxTva, 0, ',', ' ') }}%</strong> - &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; .............................................[en pourcentage]</td>
        </tr>
        <tr>
            <td class="bullet">➢</td>
            <td>Montant de la T.V.A Prix 1 = <strong>{{ ucfirst($tvaWords) }} Dirhams &nbsp;&nbsp;&nbsp; ({{ number_format($montantTva, 2, ',', ' ') }} Dh)</strong></td>
        </tr>
        <tr>
            <td class="bullet">➢</td>
            <td>Taux de la T.V.A &nbsp;&nbsp;&nbsp;&nbsp; Prix 2 = &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>10%</strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; .............................................[en pourcentage]</td>
        </tr>
        <tr>
            <td class="bullet">➢</td>
            <td>Montant de la T.V.A : Prix 2 = .................................................... Dirhams (........................ Dh)</td>
        </tr>
        <tr>
            <td class="bullet">➢</td>
            <td>Montant T.V.A Comprise : <strong>{{ ucfirst($ttcWords) }} Dirhams ({{ number_format($montantTtc, 2, ',', ' ') }} Dh)</strong></td>
        </tr>
    </table>

    <div class="section mb-2">
        La DRCA Rabat-Salé-Kénitra se libérera des sommes dues par lui en faisant donner crédit au compte bancaire (5), ouvert Banque populaire au nom <strong>{{ strtoupper($marche->fournisseur->raison_sociale ?? '........................................') }}</strong> (5), à {{ $marche->fournisseur->ville ?? '........................' }} - {{ $marche->fournisseur->agence_bancaire ?? '........................' }}..], sous relevé d'identification bancaire (RIB*) numéro <strong>{{ $marche->fournisseur->rib ?? '........................................................' }}</strong> (6)
    </div>

    <table style="width: 100%; border: none; margin-top: 20px;">
        <tr>
            <td style="width: 40%; border: none;"></td>
            <td style="width: 60%; border: none; text-align: center; font-weight: bold;">
                Fait à {{ $marche->fournisseur->ville ?? '........................' }} le {{ $marche->date_signature ? \Carbon\Carbon::parse($marche->date_signature)->format('d/m/Y') : '........................' }}<br>
                [Signature et cachet du concurrent]
            </td>
        </tr>
    </table>

    <div class="footer">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="text-align: left;">APPEL D'OFFRES OUVERT NATIONAL N° {{ $marche->aoo->num_aoo ?? '........................' }} - Règlement de consultation</td>
                <td style="text-align: right;">20</td>
            </tr>
        </table>
    </div>

</body>
</html>
