<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Estimation de l'Administration</title>
    <style>
        <?php echo file_get_contents(resource_path('views/documents/estimation/style.css')); ?>
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="logo-left">
                <!-- Logos officiels du projet -->
                <img src="{{ public_path('images/logo-onca.png') }}" alt="ONCA">
            </td>
            <td class="header-center">
                <div class="org-title">Direction Régionale du Conseil Agricole Rabat-Salé-Kénitra</div>
            </td>
            <td class="logo-right">
                <img src="{{ public_path('images/sceau-maroc.png') }}" alt="Royaume du Maroc">
            </td>
        </tr>
    </table>

    <!-- Ligne pleine sous l'en-tête -->
    <div class="header-line"></div>

    <!-- Le titre encadré avec la faute de frappe intentionnelle reproduite du modèle -->
    <div class="titre-encadre">
        ESTIMATION DE L'ADMINISTRTATION
    </div>

    <div class="info-box">
        <div class="info-ligne">
            <span class="info-label">Appel d'offre Numéro: </span>
            <span class="info-valeur" style="margin-right: 15px;">{{ $donnees['numero'] }}</span>
            <span class="info-label">en date du </span>
            <span class="info-valeur" style="margin-right: 10px;">{{ $donnees['date'] }}</span>
            <span class="info-label">lot unique</span>
        </div>
        <div class="info-ligne objet-titre">
            Ayant pour Objet
        </div>
        <div class="info-ligne objet-texte">
            {{ $donnees['objet'] }}
        </div>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th class="col-num">N°</th>
                <th class="col-des">Designation</th>
                <th class="col-uni">Unité</th>
                <th class="col-qte">Quantité</th>
                <th class="col-pu">PU HT</th>
                <th class="col-mnt">Montant HT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($donnees['lignes'] as $ligne)
            <tr>
                <td>{{ $ligne['numero'] ?? '' }}</td>
                <td style="text-align: left;">{{ $ligne['designation'] ?? '' }}</td>
                <td>{{ $ligne['unite'] ?? '' }}</td>
                <td>{{ $ligne['quantite'] ?? '' }}</td>
                <td>{{ $ligne['pu'] ?? '' }}</td>
                <td>{{ $ligne['montant'] ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-container">
        <table class="total-table">
            <tr>
                <th>Total Hors Taxe</th>
                <td>{{ $donnees['total_ht'] }}</td>
            </tr>
            <tr>
                <th><strong>TVA (20%)</strong></th>
                <td>{{ $donnees['tva'] }}</td>
            </tr>
            <tr>
                <th>Total TTC</th>
                <td>{{ $donnees['total_ttc'] }}</td>
            </tr>
        </table>
    </div>

    <div class="footer-text">
        L'estimation du maître d'ouvrage est arrêtée à {{ $donnees['arret_montant'] }}<br>
        En lettres &nbsp;&nbsp;&nbsp;&nbsp; {{ $donnees['arret_lettres'] }}
    </div>

    <div class="sign-box">
        Le maître d'Ouvrage
    </div>

</body>
</html>
