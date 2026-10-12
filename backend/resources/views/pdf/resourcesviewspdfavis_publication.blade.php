<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Avis d'Appel d'Offres</title>
    <style>
        @page {
            size: A4;
            margin: 15mm 20mm 15mm 20mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000000;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .header-table td {
            vertical-align: top;
            border: none;
            padding: 0;
        }

        .institution-title {
            text-align: center;
            margin-bottom: 20px;
        }

        .institution-title h2, .institution-title h3 {
            font-size: 11pt;
            font-weight: bold;
            margin: 0 0 4px 0;
        }

        .notice-title-box {
            text-align: center;
            margin: 20px 0 25px 0;
        }

        .notice-title-box h1, .notice-title-box h2 {
            font-size: 11pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 0 0 4px 0;
            text-transform: uppercase;
        }

        .content {
            text-align: justify;
        }

        .content p {
            margin-bottom: 10px;
        }

        .list-items {
            margin: 5px 0 12px 20px;
        }

        .keep-together {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>

    <!-- En-tête Logos -->
    <table class="header-table">
        <tr>
            <td style="width: 50%; text-align: left;">
                <img src="{{ public_path('images/logo-onca.png') }}" style="max-width: 150px;">
            </td>
            <td style="width: 50%; text-align: right;">
                <img src="{{ public_path('images/logo-royaume.png') }}" style="max-width: 150px;">
            </td>
        </tr>
    </table>

    <!-- Structure Institutionnelle -->
    <div class="institution-title">
        <h2>{{ $donnees['etablissement'] }}</h2>
        <h3>{{ $donnees['direction_regionale'] }}</h3>
        <div>**********************</div>
    </div>

    <!-- Titre de l'Avis -->
    <div class="notice-title-box">
        <h1>AVIS D'APPEL D'OFFRES OUVERT {{ $donnees['type_ao'] }}</h1>
        <h2>SUR OFFRES DE PRIX N° {{ $donnees['numero_ao'] }}</h2>
        <p style="text-decoration: underline; font-style: italic;">({{ $donnees['type_seance'] }})</p>
    </div>

    <!-- Corps du Document -->
    <div class="content">
        <p>
            Le <strong>{{ $donnees['date_ouverture'] }} à {{ $donnees['heure_ouverture'] }} Heures</strong>, il sera procédé, à la {{ $donnees['lieu_ouverture'] }}, 
            à l'ouverture des plis relatif à l'appel d'offres ouvert national sur offres de prix n°<strong>{{ $donnees['numero_ao'] }}</strong>, 
            ayant pour objet <strong>{{ $donnees['objet_ao'] }}</strong>.
        </p>

        <p>
            Le dossier d'appel d'offres doit être téléchargé à partir du portail des marchés publics accessible à l'adresse : 
            <u>{{ $donnees['url_portail'] }}</u>
        </p>

        <p>L'estimation des coûts des prestations établie par le maître d’ouvrage est fixée à la somme de :</p>
        <div class="list-items">
            @foreach($donnees['estimations'] as $item)
                <div>- <strong>{{ $item['description'] }} : {{ $item['montant_lettres'] }} ({{ $item['montant_chiffres'] }}) Dirhams TTC.</strong></div>
            @endforeach
        </div>

        <p>Le cautionnement provisoire est fixé à la somme de :</p>
        <div class="list-items">
            @foreach($donnees['cautionnements'] as $item)
                <div>- <strong>{{ $item['description'] }} : {{ $item['montant_lettres'] }} ({{ $item['montant_chiffres'] }}) Dirhams.</strong></div>
            @endforeach
        </div>

        <div class="keep-together">
            <p>
                Le contenu, la présentation ainsi que le dépôt des dossiers des concurrents doivent être conformes aux dispositions 
                des articles {{ $donnees['articles_decret'] }} du décret n°{{ $donnees['numero_decret'] }}...
            </p>
            <p>
                Les concurrents doivent déposer leurs dossiers par voie électronique dans le portail des marchés publics accessible à l'adresse : 
                <u>{{ $donnees['url_portail'] }}</u>
            </p>
        </div>
    </div>

</body>
</html>