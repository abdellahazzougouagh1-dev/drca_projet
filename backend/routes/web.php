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

Route::get('/test-pv-convention', function () {
    $doc = [
        'numero_consultation' => '7/2023/DRCA-RSK',
        'date_reunion' => '06/07/2023',
        'heure_reunion' => '12:30',
        'numero_decision' => '65/2023/DRCA-RSK',
        'date_decision' => '27/06/2023',
        'commission' => [
            ['nom' => 'Enneddam Wafaa', 'fonction' => 'Chef de Service de la Programmation du conseil agricole', 'qualite' => 'Président'],
            ['nom' => 'LAARISI HOUDA', 'fonction' => 'Chef de service/SPSO', 'qualite' => 'Membre'],
            ['nom' => 'BEKOUCHE Karim', 'fonction' => 'Administrateur de 2éme grade/SAF', 'qualite' => 'Membre'],
        ],
        'objet' => 'Organisation de voyages d’agriculteurs et de techniciens',
        'lettres_consultation_refs' => '62,63 et 64 / 2023/DRCA-RSK',
        'fournisseurs_consultes' => [
            ['nom' => 'GENERATION MULISERVICES', 'adresse' => 'AV MOUKAOUAMA DALIA B2/2APPT NO 7 MARRAKECH.', 'ville' => 'Marrakech'],
        ],
        'concurrents' => [
            ['nom' => 'GENERATION MULISERVICES', 'montant_avant' => null, 'montant_apres' => null, 'montant' => 0],
        ],
        'attributaire' => 'GENERATION MULISERVICES',
        'montant_retenu' => 0,
        'montant_en_lettres' => '-',
        'heure_fin' => '13:15',
        'date_document' => '06/07/2023',
    ];

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.pv_ouverture_convention', [
        'doc' => $doc,
    ])->setPaper('A4', 'portrait');

    return $pdf->download('test_pv_convention.pdf');
});
