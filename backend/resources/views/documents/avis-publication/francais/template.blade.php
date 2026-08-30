<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Avis d'Appel d'Offres</title>
    <style>
        <?php echo file_get_contents(resource_path('views/documents/avis-publication/francais/style.css')); ?>
    </style>
</head>
<body>

    {{-- ===== EN-TETE AVEC LOGOS ===== --}}
    <table class="header-table">
        <tr>
            <td class="logo-left">
                <img src="{{ public_path('images/logo-onca.png') }}" alt="Logo ONCA">
            </td>
            <td class="header-center">
                <p class="org-title">Office National du Conseil Agricole</p>
                <p class="org-subtitle">Direction Régionale du Conseil Agricole de Rabat-Salé-Kénitra</p>
            </td>
            <td class="logo-right">
                <img src="{{ public_path('images/sceau-maroc.png') }}" alt="Logo Ministère">
            </td>
        </tr>
    </table>

    <p class="separator">********************</p>

    {{-- ===== TITRE DE L'AVIS ===== --}}
    <p class="avis-title">AVIS D'APPEL D'OFFRES OUVERT NATIONAL</p>
    <p class="avis-subtitle">SUR OFFRES DE PRIX N° {{ $ao->num_aoo }}</p>
    <p class="avis-seance"><u>(Séance publique)</u></p>

    {{-- ===== PARAGRAPHE D'INTRODUCTION ===== --}}
    <p>
        <strong>Le {{ \Carbon\Carbon::parse($ao->date_ouverture)->format('d/m/Y') }} à {{ $ao->heure_ouverture }} Heures</strong>,
        il sera procédé, à {{ $ao->lieu_ouverture }}
        à l'ouverture des plis relatif à l'appel d'offres ouvert national sur offres de prix
        n°{{ $ao->num_aoo }}, ayant pour objet {{ $ao->objet }}
        @if($lots->count() > 1)
            en {{ $lots->count() }} lots
            ({{ $lots->map(fn($lot, $i) => 'Lot ' . ($i + 1) . ' : ' . $lot->objet_lot)->implode(' et ') }}).
        @else
            .
        @endif
    </p>

    <p>
        Le dossier d'appel d'offres doit être téléchargé à partir du portail des marchés publics
        accessible à l'adresse : <span class="link-ref">www.marchespublics.gov.ma</span>
    </p>

    {{-- ===== ESTIMATION PAR LOT ===== --}}
    <p>L'estimation des coûts des prestations établie par le maitre d'ouvrage est fixée à la somme de :</p>
    <ul class="lots-list">
        @foreach($lots as $index => $lot)
        <li>
            Lot {{ $index + 1 }} : {{ ucfirst(\App\Helpers\NumberToWordsHelper::toFrenchWords($lot->estimation)) }}
            ({{ number_format($lot->estimation, 2, ',', ' ') }}) Dirhams TTC.
        </li>
        @endforeach
    </ul>

    {{-- ===== CAUTIONNEMENT PAR LOT ===== --}}
    <p>Le cautionnement provisoire est fixé à la somme de :</p>
    <ul class="caution-list">
        @foreach($lots as $index => $lot)
        <li>
            Lot {{ $index + 1 }} : {{ ucfirst(\App\Helpers\NumberToWordsHelper::toFrenchWords($lot->cautionnement_provisoire)) }}
            ({{ number_format($lot->cautionnement_provisoire, 2, ',', ' ') }}) Dirhams.
        </li>
        @endforeach
    </ul>

    {{-- ===== PARAGRAPHES REGLEMENTAIRES ===== --}}
    <p>
        Le contenu, la présentation ainsi que le dépôt des dossiers des concurrents doivent être conformes
        aux dispositions des articles 30 à 34 et <strong>135</strong> du décret n°2-22-431 du 15 chaabane 1444 (8 mars 2023)
        relatif aux marchés publics ainsi que les <strong>articles 9 et 12</strong> de l'arrêté du Ministre Délégué auprès
        de la Ministre de l'économie et des finances, chargé du budget n° 1692-23 du 4 hija 1444
        (23 juin 2023) relatif à la dématérialisation des procédures, des documents et des pièces
        relatives aux marchés publics.
    </p>

    <p>
        Les concurrents doivent déposer leurs dossiers par voie électronique dans le portail des marchés
        publics accessible à l'adresse : <span class="link-ref">www.marchespublics.gov.ma</span>
    </p>

    <p>
        Le cautionnement provisoire doit être constitué de <strong>façon dématérialisée</strong> et ce, conformément
        aux dispositions de l'arrêté 1692-23 du 23 juin 2023 susvisé.
    </p>

    <p>
        Les pièces justificatives à fournir sont celles prévues par les <strong>articles {{ $ao->articles_rc ?? '08 et 10' }}</strong>
        du règlement de consultation.
    </p>

</body>
</html>
