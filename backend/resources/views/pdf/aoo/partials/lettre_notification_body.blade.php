@php
    $logoOnca = \App\Support\AooDocumentHelper::embedImage('images/logo-onca.png')
        ?? \App\Support\AooDocumentHelper::embedImage('images/logo_onca.png');
    $sceauMaroc = \App\Support\AooDocumentHelper::embedImage('images/sceau-maroc.png')
        ?? \App\Support\AooDocumentHelper::embedImage('images/logo_royaume.png');
@endphp

<table class="header-table">
    <tr>
        <td style="width: 28%; text-align: left;">
            @if($logoOnca)
                <img src="{{ $logoOnca }}" alt="ONCA" class="header-logo">
            @endif
        </td>
        <td class="header-center" style="width: 44%;">
            Direction Régionale du Conseil Agricole<br>
            Rabat-Salé-Kénitra
        </td>
        <td style="width: 28%; text-align: right;">
            @if($sceauMaroc)
                <img src="{{ $sceauMaroc }}" alt="Royaume du Maroc" class="header-logo">
            @endif
        </td>
    </tr>
</table>

<div class="letter-content">
    <div class="destinataire-block">
        {{ $lettre['signataire'] }}<br>
        A<br>
        {{ $lettre['gerant'] }}<br>
        {{ $lettre['societe'] }}
    </div>

    <table class="reference-block">
        <tr>
            <td class="ref-label">Objet</td>
            <td class="ref-value">{{ $lettre['objet_lettre'] }}</td>
        </tr>
        <tr>
            <td class="ref-label">Référence</td>
            <td class="ref-value">{{ $lettre['reference'] }}</td>
        </tr>
    </table>

    <div class="salutation">Monsieur / Madame,</div>

    <div class="corps-texte">
        @if($lettre['admis'])
            j'ai le plaisir de vous informer que votre offre a été retenue concernant l'appel d'offres ouvert N°: <strong>{{ $aoo->num_aoo }}</strong> du <strong>{{ \App\Support\AooDocumentHelper::formatDate($aoo->date_ouverture) !== '................' ? \App\Support\AooDocumentHelper::formatDate($aoo->date_ouverture) : '' }}</strong>
            Ayant pour objet: <strong>{{ $lettre['objet_complet'] }}</strong>.
            @if(!empty($lettre['montant']))
                <br><br>
                Montant de l'offre retenue : <strong>{{ number_format((float) $lettre['montant'], 2, ',', ' ') }} DH TTC</strong>.
            @endif
        @else
            En application des dispositions de l'article 41 du Décret n°2-12-349 du 8 joumada I 1433 (20 Mars 2013) relatif aux marchés publics publié au bulletin officiel n° 6140 du 4 avril 2013,
            j'ai le regret de vous informer sur l'écartement de votre offre concernant l'appel d'offres ouvert N°: <strong>{{ $aoo->num_aoo }}</strong> du <strong>{{ \App\Support\AooDocumentHelper::formatDate($aoo->date_ouverture) !== '................' ? \App\Support\AooDocumentHelper::formatDate($aoo->date_ouverture) : '' }}</strong>
            Ayant pour objet: <strong>{{ $lettre['objet_complet'] }}</strong>.
            <br><br>
            Il est à signaler que votre offre a été écartée pour le (s) motif (s) ci-dessous :
            <div class="motif-block">
                {!! nl2br(e($lettre['motif_ecartement'] ?: 'Motif non spécifié.')) !!}
            </div>

            <div class="pieces-fournir">
                <div class="nb-title">NB</div>
                <div class="nb-subtitle">Les pièces à fournir</div>
                <ul>
                    <li>Une attestation ou sa copie certifiée conforme délivrée depuis moins d'un an par la CNSS certifiant que le concurrent est en situation régulière envers cet organisme.</li>
                    <li>La ou les pièces justifiant les pouvoirs conférés à la personne agissant au nom de la Société. Ces pièces varient selon la forme juridique du concurrent.</li>
                    <li>Une attestation ou sa copie certifiée conforme délivrée depuis moins d'un an par l'Administration compétente du lieu d'imposition certifiant que le concurrent est en situation fiscale régulière.</li>
                    <li>Un certificat d'immatriculation au registre de commerce.</li>
                </ul>
            </div>
        @endif
    </div>

    <div class="politesse">Veuillez agréer, Messieurs, mes salutations.</div>
</div>
