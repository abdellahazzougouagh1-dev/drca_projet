<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Avis d'Appel d'Offres</title>
    <style>
        @page {
            margin: 90px 60px 70px 60px;
        }

        body {
            font-family: "DejaVu Serif", serif;
            font-size: 12px;
            line-height: 1.5;
            color: #000;
        }

        /* ===== EN-TETE ===== */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .header-table td {
            border: none;
            vertical-align: top;
        }
        .logo-left {
            width: 15%;
            text-align: left;
        }
        .logo-right {
            width: 15%;
            text-align: right;
        }
        .header-center {
            width: 70%;
            text-align: center;
        }
        .logo-left img,
        .logo-right img {
            width: 70px;
        }

        .org-title {
            font-weight: bold;
            font-size: 14px;
            margin: 0;
        }
        .org-subtitle {
            font-weight: bold;
            font-size: 13px;
            margin: 2px 0 8px 0;
        }
        .separator {
            text-align: center;
            font-weight: bold;
            letter-spacing: 3px;
            margin-bottom: 15px;
        }

        /* ===== TITRE AVIS ===== */
        .avis-title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            text-decoration: underline;
            margin: 0;
        }
        .avis-subtitle {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            text-decoration: underline;
            margin: 2px 0;
        }
        .avis-seance {
            text-align: center;
            font-style: italic;
            margin-bottom: 20px;
        }

        /* ===== CORPS ===== */
        p {
            text-align: justify;
            margin: 0 0 12px 0;
        }

        ul.lots-list, ul.caution-list {
            margin: 0 0 12px 0;
            padding-left: 25px;
        }
        ul.lots-list li, ul.caution-list li {
            margin-bottom: 4px;
        }

        .link-ref {
            text-decoration: underline;
        }

        strong.souligne {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    {{-- ===== EN-TETE AVEC LOGOS ===== --}}
    <table class="header-table">
        <tr>
            <td class="logo-left">
                <img src="{{ public_path('images/logo_onca.png') }}" alt="Logo ONCA">
            </td>
            <td class="header-center">
                <p class="org-title">Office National du Conseil Agricole</p>
                <p class="org-subtitle">Direction Régionale du Conseil Agricole de {{ $ao->region }}</p>
            </td>
            <td class="logo-right">
                <img src="{{ public_path('images/logo_ministere.png') }}" alt="Logo Ministère">
            </td>
        </tr>
    </table>

    <p class="separator">********************</p>

    {{-- ===== TITRE DE L'AVIS ===== --}}
    <p class="avis-title">AVIS D'APPEL D'OFFRES OUVERT NATIONAL</p>
    <p class="avis-subtitle">SUR OFFRES DE PRIX N° {{ $ao->numero_aoo }}</p>
    <p class="avis-seance">(Séance publique)</p>

    {{-- ===== PARAGRAPHE D'INTRODUCTION ===== --}}
    <p>
        <strong>Le {{ \Carbon\Carbon::parse($ao->date_ouverture)->format('d/m/Y') }} à {{ $ao->heure_ouverture }} Heures</strong>,
        il sera procédé, à {{ $ao->lieu_ouverture_fr }}
        à l'ouverture des plis relatif à l'appel d'offres ouvert national sur offres de prix
        n°{{ $ao->numero_aoo }}, ayant pour objet {{ $ao->objet_fr }}
        @if($lots->count() > 1)
            en {{ $lots->count() }} lots
            ({{ $lots->map(fn($lot, $i) => 'Lot ' . ($i + 1) . ' : ' . $lot->objet)->implode(' et ') }}).
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
            <strong>Lot {{ $index + 1 }}</strong> : {{ $lot->estimation_lettres }}
            ({{ number_format($lot->estimation_ttc, 2, ',', ' ') }}) Dirhams TTC.
        </li>
        @endforeach
    </ul>

    {{-- ===== CAUTIONNEMENT PAR LOT ===== --}}
    <p>Le cautionnement provisoire est fixé à la somme de :</p>
    <ul class="caution-list">
        @foreach($lots as $index => $lot)
        <li>
            <strong>Lot {{ $index + 1 }}</strong> : {{ $lot->cautionnement_lettres }}
            ({{ number_format($lot->cautionnement, 2, ',', ' ') }}) Dirhams.
        </li>
        @endforeach
    </ul>

    {{-- ===== PARAGRAPHES REGLEMENTAIRES ===== --}}
    <p>
        Le contenu, la présentation ainsi que le dépôt des dossiers des concurrents doivent être conformes
        aux dispositions des articles 30 à 34 et 135 du décret n°2-22-431 du 15 chaabane 1444 (8 mars 2023)
        relatif aux marchés publics ainsi que les articles 9 et 12 de l'arrêté du Ministre Délégué auprès
        de la Ministre de l'économie et des finances, chargé du budget n° 1692-23 du 4 hija 1444
        (23 juin 2023) relatif à la dématérialisation des procédures, des documents et des pièces
        relatives aux marchés publics.
    </p>

    <p>
        Les concurrents doivent déposer leurs dossiers par voie électronique dans le portail des marchés
        publics accessible à l'adresse : <span class="link-ref">www.marchespublics.gov.ma</span>
    </p>

    <p>
        Le cautionnement provisoire doit être constitué de façon dématérialisée et ce, conformément
        aux dispositions de l'arrêté 1692-23 du 23 juin 2023 susvisé.
    </p>

    <p>
        Les pièces justificatives à fournir sont celles prévues par les articles {{ $ao->articles_reglement }}
        du règlement de consultation.
    </p>

</body>
</html>
