<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-avis-fr', function () {
    $ao = (object)[
        'num_aoo' => '06/2026/DRCA-RSK',
        'date_ouverture' => '2026-06-10',
        'heure_ouverture' => '10',
        'lieu_ouverture' => 'la salle des réunions au siège de la Direction Régionale de l\'Office National du Conseil Agricole de Rabat-Salé-Kénitra, sis à angle avenue Mohamed V et Rue Sebta Kenitra',
        'objet' => 'l\'organisation des journées de formation au profit des agriculteurs et femmes rurales au niveau de la région Rabat-Salé-Kenitra',
        'articles_rc' => '08 et 10'
    ];

    $lots = collect([
        (object)[
            'objet_lot' => 'Organisation des journées de formation au profit des agriculteurs',
            'estimation' => 658020.00,
            'cautionnement_provisoire' => 10000.00
        ],
        (object)[
            'objet_lot' => 'Organisation des journées de formation au profit des femmes rurales',
            'estimation' => 197920.00,
            'cautionnement_provisoire' => 3000.00
        ]
    ]);

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.avis-publication.francais.template', [
        'ao' => $ao,
        'lots' => $lots
    ])->setPaper('A4', 'portrait');

    return $pdf->download('avis_publication_fr_test.pdf');
});

Route::get('/test-estimation', function () {
    $donnees = [
        'numero' => '01/2024/DRCA-RSK',
        'date' => '01/04/2024',
        'objet' => 'LA REALISATION DES PRESTATIONS DE PRISE EN CHARGE (RESTAURATION ET HEBERGEMENT) DES AGRICULTEURS, DES AGRICULTRICES ET DES TECHNICIENS DE LA REGION DE CASA-SETTAT DANS LE CADRE DES VISITES ET VOYAGES D’ETUDES PROGRAMMES A L’OCCASION DE LA 16EME EDITION DU',
        'lignes' => [
            ['numero' => '#N/A', 'designation' => '#N/A', 'unite' => '#N/A', 'quantite' => '#N/A', 'pu' => '', 'montant' => '#N/A'],
            ['numero' => '', 'designation' => '', 'unite' => '', 'quantite' => '0', 'pu' => '', 'montant' => '-'],
            ['numero' => '', 'designation' => '', 'unite' => '', 'quantite' => '0', 'pu' => '', 'montant' => '-'],
            ['numero' => '', 'designation' => '', 'unite' => '', 'quantite' => '0', 'pu' => '', 'montant' => '-'],
            ['numero' => '', 'designation' => '', 'unite' => '', 'quantite' => '0', 'pu' => '', 'montant' => '-'],
            ['numero' => '', 'designation' => '', 'unite' => '', 'quantite' => '0', 'pu' => '', 'montant' => '-'],
            ['numero' => '', 'designation' => '', 'unite' => '', 'quantite' => '0', 'pu' => '', 'montant' => '-']
        ],
        'total_ht' => '#N/A',
        'tva' => '#N/A',
        'total_ttc' => '#N/A',
        'arret_montant' => '#N/A',
        'arret_lettres' => '#NOM?'
    ];

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.estimation.template', [
        'donnees' => $donnees
    ])->setPaper('A4', 'portrait');

    return $pdf->download('Estimation_Test.pdf');
});
