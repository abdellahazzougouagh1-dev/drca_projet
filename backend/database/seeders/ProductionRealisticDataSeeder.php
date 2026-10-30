<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Fournisseur;
use App\Models\Notification;
use App\Models\NotificationLigne;
use App\Models\Consultation;
use App\Models\Budget;
use App\Models\Prestation;
use App\Models\Offre;
use App\Models\Engagement;
use App\Models\RegistreEngagement;
use App\Models\Aoo;
use App\Models\Lot;
use App\Models\Marche;
use App\Models\Liquidation;
use App\Models\LiquidationFinanciere;
use App\Models\Ordonnancement;
use App\Models\OrdonnancementOrdre;
use App\Models\CommissionMembre;
use App\Models\OuverturePlisConcurrent;

class ProductionRealisticDataSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } else {
            Schema::disableForeignKeyConstraints();
        }

        // =========================================================================
        // 1. FOURNISSEURS MAROCAINS RÉELS
        // =========================================================================
        $fournisseursData = [
            [
                'raison_sociale' => 'Solutions IT Plus EURL',
                'ice' => '002234567890001',
                'if' => 'IF87654321',
                'patente' => 'PA87654321',
                'rc' => 'RC87654',
                'ville_rc' => 'Rabat',
                'adresse' => 'Avenue Mohammed V, Lot 456, Centre Affaires',
                'ville' => 'Rabat',
                'telephone' => '+212 537 72 34 56',
                'email' => 'contact@solutionsit.ma',
                'representant' => 'Fatima Azahaf',
                'qualite_representant' => 'Directrice Générale',
                'domaine_activite' => 'Services informatiques et bureautique',
                'cnss' => 'CNSS003456789',
                'banque' => 'Crédit Agricole du Maroc',
                'agence_bancaire' => 'Agence Hassan - Rabat',
                'rib' => '225 810 012 345 678 901 234 56',
                'titulaire_compte' => 'Solutions IT Plus EURL',
            ],
            [
                'raison_sociale' => 'Entreprise Construct Maroc SARL',
                'ice' => '001234567890001',
                'if' => 'IF12345678',
                'patente' => 'PA12345678',
                'rc' => 'RC12345',
                'ville_rc' => 'Casablanca',
                'adresse' => 'Boulevard Zerktouni, Immeuble Al Manar N° 45',
                'ville' => 'Casablanca',
                'telephone' => '+212 522 25 18 90',
                'email' => 'contact@constructmaroc.ma',
                'representant' => 'Mohamed Benalou',
                'qualite_representant' => 'Gérant',
                'domaine_activite' => 'Travaux de construction et réhabilitation',
                'cnss' => 'CNSS002345678',
                'banque' => 'Attijariwafa Bank',
                'agence_bancaire' => 'Agence Zerktouni - Casablanca',
                'rib' => '007 780 001 234 567 890 123 45',
                'titulaire_compte' => 'Entreprise Construct Maroc SARL',
            ],
            [
                'raison_sociale' => 'Bureau d\'Études & Conseil Agricole SARL',
                'ice' => '003456789012345',
                'if' => 'IF34567890',
                'patente' => 'PA34567890',
                'rc' => 'RC34567',
                'ville_rc' => 'Rabat',
                'adresse' => 'Rue Oued Baht, Agdal',
                'ville' => 'Rabat',
                'telephone' => '+212 537 68 12 34',
                'email' => 'contact@beca-maroc.com',
                'representant' => 'Dr. Khalid Tazi',
                'qualite_representant' => 'Directeur Général',
                'domaine_activite' => 'Études, conseil et formation agricole',
                'cnss' => 'CNSS004567890',
                'banque' => 'Crédit Agricole du Maroc',
                'agence_bancaire' => 'Agence Agdal - Rabat',
                'rib' => '225 810 034 567 890 123 456 78',
                'titulaire_compte' => 'Bureau d\'Études & Conseil Agricole SARL',
            ],
            [
                'raison_sociale' => 'Garage Moderne & Services Auto SARL',
                'ice' => '004567890123456',
                'if' => 'IF45678901',
                'patente' => 'PA45678901',
                'rc' => 'RC45678',
                'ville_rc' => 'Kénitra',
                'adresse' => 'Zone Industrielle Bir Rami',
                'ville' => 'Kénitra',
                'telephone' => '+212 537 37 45 67',
                'email' => 'contact@garagemoderne-kenitra.ma',
                'representant' => 'Rachid Mansouri',
                'qualite_representant' => 'Gérant',
                'domaine_activite' => 'Entretien et réparation de véhicules',
                'cnss' => 'CNSS005678901',
                'banque' => 'Banque Populaire',
                'agence_bancaire' => 'Agence Bir Rami - Kénitra',
                'rib' => '127 810 045 678 901 234 567 89',
                'titulaire_compte' => 'Garage Moderne & Services Auto SARL',
            ],
            [
                'raison_sociale' => 'Société Bâtiment & Travaux du Gharb SARL',
                'ice' => '005678901234567',
                'if' => 'IF56789012',
                'patente' => 'PA56789012',
                'rc' => 'RC56789',
                'ville_rc' => 'Kénitra',
                'adresse' => 'Avenue Diouri, N° 78',
                'ville' => 'Kénitra',
                'telephone' => '+212 537 36 21 00',
                'email' => 'contact@sbtg-kenitra.ma',
                'representant' => 'Hassan El Alami',
                'qualite_representant' => 'Gérant',
                'domaine_activite' => 'Bâtiment et travaux publics',
                'cnss' => 'CNSS006789012',
                'banque' => 'BMCE Bank of Africa',
                'agence_bancaire' => 'Agence Diouri - Kénitra',
                'rib' => '011 810 056 789 012 345 678 90',
                'titulaire_compte' => 'Société Bâtiment & Travaux du Gharb SARL',
            ],
            [
                'raison_sociale' => 'Auto Hall Maroc SA',
                'ice' => '006789012345678',
                'if' => 'IF67890123',
                'patente' => 'PA67890123',
                'rc' => 'RC67890',
                'ville_rc' => 'Casablanca',
                'adresse' => 'Km 12, Route de Rabat, Ain Sebâa',
                'ville' => 'Casablanca',
                'telephone' => '+212 522 35 40 00',
                'email' => 'flottes@autohall.ma',
                'representant' => 'Youssef Chraibi',
                'qualite_representant' => 'Directeur des Ventes Flottes Publiques',
                'domaine_activite' => 'Vente de véhicules utilitaires et 4x4',
                'cnss' => 'CNSS007890123',
                'banque' => 'Attijariwafa Bank',
                'agence_bancaire' => 'Agence Centrale - Casablanca',
                'rib' => '007 780 006 789 012 345 678 90',
                'titulaire_compte' => 'Auto Hall Maroc SA',
            ],
            [
                'raison_sociale' => 'Société Marocaine de Valorisation Agricole (SMVA)',
                'ice' => '007890123456789',
                'if' => 'IF78901234',
                'patente' => 'PA78901234',
                'rc' => 'RC78901',
                'ville_rc' => 'Sidi Slimane',
                'adresse' => 'Route Principale de Kénitra, Lotissement Industriel',
                'ville' => 'Sidi Slimane',
                'telephone' => '+212 537 50 18 22',
                'email' => 'contact@smva-maroc.ma',
                'representant' => 'Karim Berrada',
                'qualite_representant' => 'Directeur des Opérations',
                'domaine_activite' => 'Conseil et valorisation des filières agricoles',
                'cnss' => 'CNSS008901234',
                'banque' => 'Crédit Agricole du Maroc',
                'agence_bancaire' => 'Agence Sidi Slimane',
                'rib' => '225 810 078 901 234 567 890 12',
                'titulaire_compte' => 'Société Marocaine de Valorisation Agricole',
            ],
            [
                'raison_sociale' => 'Papeterie & Bureautique Moderne SARL',
                'ice' => '008901234567890',
                'if' => 'IF89012345',
                'patente' => 'PA89012345',
                'rc' => 'RC89012',
                'ville_rc' => 'Kénitra',
                'adresse' => 'Boulevard Mohammed Diouri, N° 112',
                'ville' => 'Kénitra',
                'telephone' => '+212 537 38 10 90',
                'email' => 'pbm.kenitra@gmail.com',
                'representant' => 'Said Idrissi',
                'qualite_representant' => 'Gérant',
                'domaine_activite' => 'Fournitures de bureau et consommables',
                'cnss' => 'CNSS009012345',
                'banque' => 'Banque Populaire',
                'agence_bancaire' => 'Agence Maamora - Kénitra',
                'rib' => '127 810 089 012 345 678 901 23',
                'titulaire_compte' => 'Papeterie & Bureautique Moderne SARL',
            ]
        ];

        $fournisseursMap = [];
        foreach ($fournisseursData as $fData) {
            $f = Fournisseur::updateOrCreate(['ice' => $fData['ice']], $fData);
            $fournisseursMap[$f->raison_sociale] = $f;
        }

        // =========================================================================
        // 2. MEMBRES DE COMMISSION
        // =========================================================================
        $membres = [
            ['nom_prenom' => 'Dr. Boubker BOUAAMA', 'fonction' => 'Chef de Service Administratif et Financier'],
            ['nom_prenom' => 'Abdellah AZZOUGOUAGH', 'fonction' => 'Cadre Financier et Gestionnaire des Marchés'],
            ['nom_prenom' => 'Hamza BAADI', 'fonction' => 'Ingénieur SI / Administrateur des Systèmes'],
            ['nom_prenom' => 'Ing. Mohamed RADI', 'fonction' => 'Chef de Service du Conseil Agricole'],
        ];

        foreach ($membres as $m) {
            CommissionMembre::updateOrCreate(['nom_prenom' => $m['nom_prenom']], $m);
        }

        // =========================================================================
        // 3. NOTIFICATIONS BUDGÉTAIRES 2026 (Partie Directeur & Consolidation)
        // =========================================================================
        Notification::whereIn('exercice', [2025, 2026])->delete();

        // 3.1 Notification 2026 - INVESTISSEMENT
        $notifInv2026 = Notification::create([
            'numero' => '01/2026/ONCA/DRCA-RSK',
            'type_budget' => 'REPORT + NOTIFIER',
            'domaine' => 'INVESTISSEMENT',
            'exercice' => 2026,
            'date_notification' => '2026-01-12',
            'montant' => 6830000.00,
            'objet' => 'Dotation budgétaire initiale d\'investissement - Exercice 2026 - DRCA Rabat-Salé-Kénitra',
            'reference' => 'Décision DG/ONCA N° 14/2026',
        ]);

        $lignesInv2026Data = [
            [
                'article' => '415', 'paragraphe' => '10', 'ligne_budgetaire' => '13',
                'libelle' => 'Travaux d\'aménagement des bâtiments administratifs et des Centres de Conseil Agricole (CCA)',
                'domaine' => 'INVESTISSEMENT',
                'reports' => 150000.00,
                'diminution_report' => 0.00,
                'credits_neufs' => 1800000.00,
                'diminution_credit_neuf' => 0.00,
                'credits_engagements' => 0.00,
                'diminution_credit_engagement' => 0.00,
            ],
            [
                'article' => '415', 'paragraphe' => '10', 'ligne_budgetaire' => '20',
                'libelle' => 'Acquisition de matériel informatique, serveurs et équipements numériques',
                'domaine' => 'INVESTISSEMENT',
                'reports' => 0.00,
                'diminution_report' => 0.00,
                'credits_neufs' => 950000.00,
                'diminution_credit_neuf' => 0.00,
                'credits_engagements' => 0.00,
                'diminution_credit_engagement' => 0.00,
            ],
            [
                'article' => '415', 'paragraphe' => '30', 'ligne_budgetaire' => '10',
                'libelle' => 'Acquisition de matériel roulant (véhicules utilitaires tout-terrain pour missions)',
                'domaine' => 'INVESTISSEMENT',
                'reports' => 280000.00,
                'diminution_report' => 0.00,
                'credits_neufs' => 2400000.00,
                'diminution_credit_neuf' => 0.00,
                'credits_engagements' => 0.00,
                'diminution_credit_engagement' => 0.00,
            ],
            [
                'article' => '415', 'paragraphe' => '10', 'ligne_budgetaire' => '15',
                'libelle' => 'Matériel audiovisuel et équipements de démonstration agricole',
                'domaine' => 'INVESTISSEMENT',
                'reports' => 0.00,
                'diminution_report' => 0.00,
                'credits_neufs' => 650000.00,
                'diminution_credit_neuf' => 0.00,
                'credits_engagements' => 0.00,
                'diminution_credit_engagement' => 0.00,
            ],
            [
                'article' => '415', 'paragraphe' => '20', 'ligne_budgetaire' => '10',
                'libelle' => 'Aménagement hydro-agricole et points d\'eau dans les parcelles de démonstration',
                'domaine' => 'INVESTISSEMENT',
                'reports' => 0.00,
                'diminution_report' => 0.00,
                'credits_neufs' => 600000.00,
                'diminution_credit_neuf' => 0.00,
                'credits_engagements' => 0.00,
                'diminution_credit_engagement' => 0.00,
            ],
        ];

        $notifLignesMap = [];
        foreach ($lignesInv2026Data as $lData) {
            $lData['notification_id'] = $notifInv2026->id;
            $ligne = NotificationLigne::create($lData);
            $key = 'INV_' . $lData['article'] . '/' . $lData['paragraphe'] . '/' . $lData['ligne_budgetaire'];
            $notifLignesMap[$key] = $ligne;
        }

        // 3.2 Notification 2026 - FONCTIONNEMENT
        $notifFnc2026 = Notification::create([
            'numero' => '02/2026/ONCA/DRCA-RSK',
            'type_budget' => 'REPORT + NOTIFIER',
            'domaine' => 'FONCTIONNEMENT',
            'exercice' => 2026,
            'date_notification' => '2026-01-18',
            'montant' => 2400000.00,
            'objet' => 'Crédits de fonctionnement ouverts pour les services régionaux et provinciaux DRCA-RSK',
            'reference' => 'Décision DG/ONCA N° 22/2026',
        ]);

        $lignesFnc2026Data = [
            [
                'article' => '210', 'paragraphe' => '10', 'ligne_budgetaire' => '05',
                'libelle' => 'Fournitures de bureau, papier et consommables informatiques',
                'domaine' => 'FONCTIONNEMENT',
                'reports' => 30000.00,
                'diminution_report' => 0.00,
                'credits_neufs' => 420000.00,
                'diminution_credit_neuf' => 0.00,
                'credits_engagements' => 0.00,
                'diminution_credit_engagement' => 0.00,
            ],
            [
                'article' => '210', 'paragraphe' => '20', 'ligne_budgetaire' => '01',
                'libelle' => 'Entretien et réparation des véhicules de service',
                'domaine' => 'FONCTIONNEMENT',
                'reports' => 0.00,
                'diminution_report' => 0.00,
                'credits_neufs' => 380000.00,
                'diminution_credit_neuf' => 0.00,
                'credits_engagements' => 0.00,
                'diminution_credit_engagement' => 0.00,
            ],
            [
                'article' => '210', 'paragraphe' => '30', 'ligne_budgetaire' => '01',
                'libelle' => 'Organisation des sessions de formation et journées de vulgarisation agricole',
                'domaine' => 'FONCTIONNEMENT',
                'reports' => 50000.00,
                'diminution_report' => 0.00,
                'credits_neufs' => 850000.00,
                'diminution_credit_neuf' => 0.00,
                'credits_engagements' => 0.00,
                'diminution_credit_engagement' => 0.00,
            ],
            [
                'article' => '210', 'paragraphe' => '20', 'ligne_budgetaire' => '10',
                'libelle' => 'Frais postaux, télécommunications et liaisons internet haut débit',
                'domaine' => 'FONCTIONNEMENT',
                'reports' => 0.00,
                'diminution_report' => 0.00,
                'credits_neufs' => 220000.00,
                'diminution_credit_neuf' => 0.00,
                'credits_engagements' => 0.00,
                'diminution_credit_engagement' => 0.00,
            ],
            [
                'article' => '210', 'paragraphe' => '10', 'ligne_budgetaire' => '01',
                'libelle' => 'Indemnités de déplacement et missions des conseillers agricoles',
                'domaine' => 'FONCTIONNEMENT',
                'reports' => 0.00,
                'diminution_report' => 0.00,
                'credits_neufs' => 450000.00,
                'diminution_credit_neuf' => 0.00,
                'credits_engagements' => 0.00,
                'diminution_credit_engagement' => 0.00,
            ],
        ];

        foreach ($lignesFnc2026Data as $lData) {
            $lData['notification_id'] = $notifFnc2026->id;
            $ligne = NotificationLigne::create($lData);
            $key = 'FNC_' . $lData['article'] . '/' . $lData['paragraphe'] . '/' . $lData['ligne_budgetaire'];
            $notifLignesMap[$key] = $ligne;
        }

        // 3.3 Notification 2025 (Historique)
        $notifInv2025 = Notification::create([
            'numero' => '01/2025/ONCA/DRCA-RSK',
            'type_budget' => 'NOTIFIER',
            'domaine' => 'INVESTISSEMENT',
            'exercice' => 2025,
            'date_notification' => '2025-01-15',
            'montant' => 5400000.00,
            'objet' => 'Dotation budgétaire d\'investissement 2025 - DRCA-RSK',
        ]);
        NotificationLigne::create([
            'notification_id' => $notifInv2025->id,
            'article' => '415', 'paragraphe' => '10', 'ligne_budgetaire' => '13',
            'libelle' => 'Travaux d\'aménagement des bâtiments administratifs',
            'domaine' => 'INVESTISSEMENT',
            'credits_neufs' => 2500000.00,
        ]);
        NotificationLigne::create([
            'notification_id' => $notifInv2025->id,
            'article' => '415', 'paragraphe' => '30', 'ligne_budgetaire' => '10',
            'libelle' => 'Acquisition de matériel de transport',
            'domaine' => 'INVESTISSEMENT',
            'credits_neufs' => 2900000.00,
        ]);

        // =========================================================================
        // 4. RÉINITIALISATION PROPRE DES TABLES MÉTIERS
        // =========================================================================
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('TRUNCATE TABLE consultations, budgets, prestations, offres, engagements, registre_engagements, liquidation_financieres, liquidations, ordonnancements, ordonnancement_ordres, aoos, lots, marches, ouverture_plis_concurrents CASCADE;');
        } else {
            Consultation::truncate();
            Budget::truncate();
            Prestation::truncate();
            Offre::truncate();
            Engagement::truncate();
            RegistreEngagement::truncate();
            LiquidationFinanciere::truncate();
            Liquidation::truncate();
            Ordonnancement::truncate();
            OrdonnancementOrdre::truncate();
            Aoo::truncate();
            Lot::truncate();
            Marche::truncate();
            OuverturePlisConcurrent::truncate();
        }

        // =========================================================================
        // 5. BONS DE COMMANDE RÉELS (mode_engagement = 'BC' pour /bons-commande)
        // =========================================================================

        // 5.1 BC N° 01/2026 : FOURNITURES DE BUREAU (Cycle complet -> Ordonnancé / Payé)
        $fSolutionsIt = $fournisseursMap['Solutions IT Plus EURL'];
        $ligneFncFournitures = $notifLignesMap['FNC_210/10/05'];

        $bc1 = Consultation::create([
            'numero_consultation' => '01/2026/DRCA-RSK',
            'numero_bc' => 'BC-01/2026/DRCA-RSK',
            'annee' => 2026,
            'date_consultation' => '2026-01-20',
            'objet_consultation' => 'Acquisition de fournitures de bureau, papier et consommables informatiques pour les Centres de Conseil Agricole (CCA) de Kénitra, Sidi Kacem et Sidi Slimane',
            'description_detaillee' => 'Fourniture de rames de papier A4 80g, toners originaux pour imprimantes réseau, boîtes d\'archives et fournitures administratives diverses.',
            'categorie' => 'FOURNITURES',
            'type_prestation' => 'Fournitures de bureau',
            'intitule' => 'Fournitures de bureau et consommables CCA 2026',
            'mode_engagement' => 'BC',
            'type_budget' => 'Fonctionnement',
            'delai_execution' => 15,
            'statut_dossier' => 'Terminé',
            'fournisseur_id' => $fSolutionsIt->id,
            'numero_avis' => 'AVIS-01/2026',
            'date_limite_devis' => '2026-01-28',
            'heure_limite_devis' => '12:00',
            'lieu_execution' => 'Kénitra, Sidi Kacem, Sidi Slimane',
            'numero_engagement' => 'ENG-BC-2026-001',
            'credit_ouvert_cp' => 450000.00,
            'montant_engager_neuf' => 86400.00,
            'notification_ligne_id' => $ligneFncFournitures->id,
        ]);

        Budget::create([
            'consultation_id' => $bc1->id,
            'art' => '210', 'par' => '10', 'lig' => '05',
            'code_imputation' => '2101005',
            'intitule_ligne' => $ligneFncFournitures->libelle,
            'exercice_budgetaire' => 2026,
            'montant_estimatif_ht' => 72000.00,
            'tva' => 20.00,
            'montant_ttc' => 86400.00,
        ]);

        Prestation::create(['consultation_id' => $bc1->id, 'designation' => 'Rames de papier A4 80g (Carton de 5 rames)', 'unite' => 'Carton', 'quantite' => 120, 'prix_unitaire_ht' => 220.00, 'tva' => 20.00]);
        Prestation::create(['consultation_id' => $bc1->id, 'designation' => 'Toners laser noir HP W1106A (106A)', 'unite' => 'Unité', 'quantite' => 30, 'prix_unitaire_ht' => 750.00, 'tva' => 20.00]);
        Prestation::create(['consultation_id' => $bc1->id, 'designation' => 'Fournitures de classement (Boîtes d\'archives, classeurs dos 8cm)', 'unite' => 'Lot', 'quantite' => 1, 'prix_unitaire_ht' => 23100.00, 'tva' => 20.00]);

        Offre::create(['consultation_id' => $bc1->id, 'fournisseur_id' => $fSolutionsIt->id, 'date_envoi' => '2026-01-26', 'montant_propose' => 86400.00, 'statut_reponse' => 'Reçu', 'montant_apres_verification' => 86400.00, 'retenu' => true]);
        Offre::create(['consultation_id' => $bc1->id, 'fournisseur_id' => $fournisseursMap['Papeterie & Bureautique Moderne SARL']->id, 'date_envoi' => '2026-01-27', 'montant_propose' => 91200.00, 'statut_reponse' => 'Reçu', 'montant_apres_verification' => 91200.00, 'retenu' => false]);

        Engagement::create([
            'numero' => 'ENG-BC-2026-001',
            'consultation_id' => $bc1->id,
            'fournisseur_id' => $fSolutionsIt->id,
            'montant_global' => 86400.00,
            'objet' => $bc1->objet_consultation,
            'statut_actuel' => 'Validé',
            'date_engagement' => '2026-02-02',
            'delai_execution' => 15,
            'date_notification' => '2026-02-04',
            'date_commencement' => '2026-02-05',
        ]);

        RegistreEngagement::create([
            'consultation_id' => $bc1->id,
            'numero_ordre' => 1,
            'date_engagement' => '2026-02-02',
            'numero_rubrique' => '2101005',
            'mode_engagement' => 'BC',
            'reference' => 'BC-01/2026/DRCA-RSK',
            'budget' => 'Fonctionnement',
            'code' => '2101005',
            'art' => '210', 'par' => '10', 'lig' => '05',
            'intitule' => $ligneFncFournitures->libelle,
            'credit_ouvert_cp' => 450000.00,
            'credit_consolide' => 450000.00,
            'montant_engager_neuf' => 86400.00,
            'objet' => $bc1->objet_consultation,
            'beneficiaire' => $fSolutionsIt->raison_sociale,
        ]);

        LiquidationFinanciere::create([
            'consultation_id' => $bc1->id,
            'montant_a_payer' => 86400.00,
            'reference_facture' => 'FAC-2026-0182',
            'date_facture' => '2026-02-18',
            'ordre_imputation' => '210/10/05',
            'ordre_paiement' => 'OP-BC-01',
            'ordre_virement' => 'VIR-BC-01',
        ]);

        $ordBc1 = Ordonnancement::create([
            'num_ordonnancement' => 'ORD-2026-002',
            'num_op' => 'OP N° 12',
            'exercice' => '2026',
            'date_ordonnancement' => '2026-02-25',
            'consultation_id' => $bc1->id,
            'fournisseur_id' => $fSolutionsIt->id,
            'notification_ligne_id' => $ligneFncFournitures->id,
            'type_procedure' => 'Bon de commande',
            'reference' => 'BC-01/2026/DRCA-RSK',
            'beneficiaire_nom' => $fSolutionsIt->raison_sociale,
            'budget_type' => 'Fonctionnement',
            'creance' => 'Crédit neuf',
            'code_imputation' => '2101005',
            'article' => '210', 'paragraphe' => '10', 'ligne' => '05',
            'intitule_depense' => $bc1->objet_consultation,
            'montant_brut' => 86400.00,
            'retenue_tva' => 14400.00,
            'retenue_ias' => 0.00,
            'net_a_payer' => 72000.00,
            'credit_neuf' => 86400.00,
            'ras_total' => 14400.00,
            'rap_total' => 72000.00,
            'statut' => 'Payé',
            'observations' => 'Règlement intégral des fournitures de bureau CCA.',
        ]);

        OrdonnancementOrdre::create([
            'ordonnancement_id' => $ordBc1->id,
            'num_ordre' => 'OP N° 12',
            'type_mouvement' => 'Paiement fournisseur',
            'mode_paiement' => 'Virement',
            'beneficiaire' => $fSolutionsIt->raison_sociale,
            'rib_compte' => $fSolutionsIt->rib,
            'montant' => 72000.00,
            'statut' => 'Payé',
        ]);

        // 5.2 BC N° 02/2026 : ENTRETIEN DES VÉHICULES (Phase : Liquidation)
        $fGarage = $fournisseursMap['Garage Moderne & Services Auto SARL'];
        $ligneFncAuto = $notifLignesMap['FNC_210/20/01'];

        $bc2 = Consultation::create([
            'numero_consultation' => '02/2026/DRCA-RSK',
            'numero_bc' => 'BC-02/2026/DRCA-RSK',
            'annee' => 2026,
            'date_consultation' => '2026-02-05',
            'objet_consultation' => 'Entretien préventif et révision mécanique des véhicules utilitaires de service de la DRCA-RSK',
            'description_detaillee' => 'Vidanges, changement filtres, plaquettes de frein, pneumatiques et révision générale du parc automobile de liaison.',
            'categorie' => 'SERVICES',
            'type_prestation' => 'Entretien et réparation',
            'intitule' => 'Entretien périodique du parc automobile 2026',
            'mode_engagement' => 'BC',
            'type_budget' => 'Fonctionnement',
            'delai_execution' => 30,
            'statut_dossier' => 'Liquidation',
            'fournisseur_id' => $fGarage->id,
            'numero_avis' => 'AVIS-02/2026',
            'date_limite_devis' => '2026-02-12',
            'numero_engagement' => 'ENG-BC-2026-002',
            'credit_ouvert_cp' => 380000.00,
            'montant_engager_neuf' => 64800.00,
            'notification_ligne_id' => $ligneFncAuto->id,
        ]);

        Budget::create([
            'consultation_id' => $bc2->id,
            'art' => '210', 'par' => '20', 'lig' => '01',
            'code_imputation' => '2102001',
            'intitule_ligne' => $ligneFncAuto->libelle,
            'exercice_budgetaire' => 2026,
            'montant_estimatif_ht' => 54000.00,
            'tva' => 20.00,
            'montant_ttc' => 64800.00,
        ]);

        Engagement::create([
            'numero' => 'ENG-BC-2026-002',
            'consultation_id' => $bc2->id,
            'fournisseur_id' => $fGarage->id,
            'montant_global' => 64800.00,
            'objet' => $bc2->objet_consultation,
            'statut_actuel' => 'Validé',
            'date_engagement' => '2026-02-16',
            'delai_execution' => 30,
        ]);

        RegistreEngagement::create([
            'consultation_id' => $bc2->id,
            'numero_ordre' => 2,
            'date_engagement' => '2026-02-16',
            'numero_rubrique' => '2102001',
            'mode_engagement' => 'BC',
            'reference' => 'BC-02/2026/DRCA-RSK',
            'budget' => 'Fonctionnement',
            'code' => '2102001',
            'art' => '210', 'par' => '20', 'lig' => '01',
            'intitule' => $ligneFncAuto->libelle,
            'credit_ouvert_cp' => 380000.00,
            'montant_engager_neuf' => 64800.00,
            'objet' => $bc2->objet_consultation,
            'beneficiaire' => $fGarage->raison_sociale,
        ]);

        // 5.3 BC N° 03/2026 : MATÉRIEL INFORMATIQUE ET MULTIMÉDIA (Phase : Engagement visé)
        $ligneInvInfo = $notifLignesMap['INV_415/10/20'];

        $bc3 = Consultation::create([
            'numero_consultation' => '03/2026/DRCA-RSK',
            'numero_bc' => 'BC-03/2026/DRCA-RSK',
            'annee' => 2026,
            'date_consultation' => '2026-02-10',
            'objet_consultation' => 'Fourniture de 12 ordinateurs portables professionnels et 4 vidéoprojecteurs pour les conseillers agricoles',
            'description_detaillee' => 'Équipement informatique mobile destiné à l\'animation des ateliers de formation agricole en milieu rural.',
            'categorie' => 'FOURNITURES',
            'type_prestation' => 'Matériel informatique',
            'intitule' => 'Matériel informatique mobile pour conseillers',
            'mode_engagement' => 'BC',
            'type_budget' => 'Investissement',
            'delai_execution' => 20,
            'statut_dossier' => 'Engagement',
            'fournisseur_id' => $fSolutionsIt->id,
            'numero_engagement' => 'ENG-BC-2026-003',
            'credit_ouvert_cp' => 950000.00,
            'montant_engager_neuf' => 142800.00,
            'notification_ligne_id' => $ligneInvInfo->id,
        ]);

        Budget::create([
            'consultation_id' => $bc3->id,
            'art' => '415', 'par' => '10', 'lig' => '20',
            'code_imputation' => '4151020',
            'intitule_ligne' => $ligneInvInfo->libelle,
            'exercice_budgetaire' => 2026,
            'montant_estimatif_ht' => 119000.00,
            'tva' => 20.00,
            'montant_ttc' => 142800.00,
        ]);

        Engagement::create([
            'numero' => 'ENG-BC-2026-003',
            'consultation_id' => $bc3->id,
            'fournisseur_id' => $fSolutionsIt->id,
            'montant_global' => 142800.00,
            'objet' => $bc3->objet_consultation,
            'statut_actuel' => 'En cours d\'exécution',
            'date_engagement' => '2026-02-22',
            'delai_execution' => 20,
        ]);

        RegistreEngagement::create([
            'consultation_id' => $bc3->id,
            'numero_ordre' => 3,
            'date_engagement' => '2026-02-22',
            'numero_rubrique' => '4151020',
            'mode_engagement' => 'BC',
            'reference' => 'BC-03/2026/DRCA-RSK',
            'budget' => 'Investissement',
            'code' => '4151020',
            'art' => '415', 'par' => '10', 'lig' => '20',
            'intitule' => $ligneInvInfo->libelle,
            'credit_ouvert_cp' => 950000.00,
            'montant_engager_neuf' => 142800.00,
            'objet' => $bc3->objet_consultation,
            'beneficiaire' => $fSolutionsIt->raison_sociale,
        ]);

        // 5.4 BC N° 04/2026 : IMPRESSION GUIDES ET BROCHURES (Phase : Consultation des fournisseurs)
        $ligneFncForm = $notifLignesMap['FNC_210/30/01'];

        $bc4 = Consultation::create([
            'numero_consultation' => '04/2026/DRCA-RSK',
            'numero_bc' => 'BC-04/2026/DRCA-RSK',
            'annee' => 2026,
            'date_consultation' => '2026-02-20',
            'objet_consultation' => 'Impression de 5 000 guides techniques et dépliants de vulgarisation sur les bonnes pratiques culturales (Céréales et Oléagineux)',
            'description_detaillee' => 'Impression quadrichromie sur papier couché brillant 150g avec vernis protecteur, façonnage et livraison au siège de la DRCA.',
            'categorie' => 'SERVICES',
            'type_prestation' => 'Impression et documentation',
            'intitule' => 'Impression supports de vulgarisation 2026',
            'mode_engagement' => 'BC',
            'type_budget' => 'Fonctionnement',
            'delai_execution' => 15,
            'statut_dossier' => 'Consultation',
            'notification_ligne_id' => $ligneFncForm->id,
        ]);

        Budget::create([
            'consultation_id' => $bc4->id,
            'art' => '210', 'par' => '30', 'lig' => '01',
            'code_imputation' => '2103001',
            'intitule_ligne' => $ligneFncForm->libelle,
            'exercice_budgetaire' => 2026,
            'montant_estimatif_ht' => 45000.00,
            'tva' => 20.00,
            'montant_ttc' => 54000.00,
        ]);

        Offre::create(['consultation_id' => $bc4->id, 'fournisseur_id' => $fournisseursMap['Papeterie & Bureautique Moderne SARL']->id, 'date_envoi' => '2026-02-26', 'montant_propose' => 52800.00, 'statut_reponse' => 'Reçu']);
        Offre::create(['consultation_id' => $bc4->id, 'fournisseur_id' => $fSolutionsIt->id, 'date_envoi' => '2026-02-27', 'montant_propose' => 56400.00, 'statut_reponse' => 'Reçu']);

        // 5.5 BC N° 05/2026 : RÉFECTION CENTRE CCA SIDI YAHYA (Investissement - En cours d'engagement)
        $ligneInvBatiment = $notifLignesMap['INV_415/10/13'];
        $fConstruct = $fournisseursMap['Entreprise Construct Maroc SARL'];

        $bc5 = Consultation::create([
            'numero_consultation' => '05/2026/DRCA-RSK',
            'numero_bc' => 'BC-05/2026/DRCA-RSK',
            'annee' => 2026,
            'date_consultation' => '2026-02-25',
            'objet_consultation' => 'Travaux urgents d\'étanchéité et de rafraîchissement des façades du Centre de Conseil Agricole de Sidi Yahya du Gharb',
            'categorie' => 'TRAVAUX',
            'type_prestation' => 'Travaux de réfection',
            'intitule' => 'Travaux réfection CCA Sidi Yahya',
            'mode_engagement' => 'BC',
            'type_budget' => 'Investissement',
            'delai_execution' => 30,
            'statut_dossier' => 'Engagement',
            'fournisseur_id' => $fConstruct->id,
            'numero_engagement' => 'ENG-BC-2026-005',
            'credit_ouvert_cp' => 1800000.00,
            'montant_engager_neuf' => 115200.00,
            'notification_ligne_id' => $ligneInvBatiment->id,
        ]);

        Budget::create([
            'consultation_id' => $bc5->id,
            'art' => '415', 'par' => '10', 'lig' => '13',
            'code_imputation' => '4151013',
            'intitule_ligne' => $ligneInvBatiment->libelle,
            'exercice_budgetaire' => 2026,
            'montant_estimatif_ht' => 96000.00,
            'tva' => 20.00,
            'montant_ttc' => 115200.00,
        ]);

        RegistreEngagement::create([
            'consultation_id' => $bc5->id,
            'numero_ordre' => 4,
            'date_engagement' => '2026-03-01',
            'numero_rubrique' => '4151013',
            'mode_engagement' => 'BC',
            'reference' => 'BC-05/2026/DRCA-RSK',
            'budget' => 'Investissement',
            'code' => '4151013',
            'art' => '415', 'par' => '10', 'lig' => '13',
            'intitule' => $ligneInvBatiment->libelle,
            'credit_ouvert_cp' => 1800000.00,
            'montant_engager_neuf' => 115200.00,
            'objet' => $bc5->objet_consultation,
            'beneficiaire' => $fConstruct->raison_sociale,
        ]);

        // =========================================================================
        // 6. CONVENTIONS RÉELLES (mode_engagement = 'Convention' pour /convention)
        // =========================================================================

        // 6.1 CONV N° 01/2026 : FORMATION COOPÉRATIVES AGRICOLES
        $fBeca = $fournisseursMap['Bureau d\'Études & Conseil Agricole SARL'];

        $conv1 = Consultation::create([
            'numero_consultation' => 'CONV-01/2026/DRCA-RSK',
            'numero_bc' => 'CONV-01/2026/DRCA-RSK',
            'annee' => 2026,
            'date_consultation' => '2026-01-25',
            'objet_consultation' => 'Convention de partenariat pour l\'accompagnement technique, le renforcement institutionnel et la formation des coopératives agricoles de la région Rabat-Salé-Kénitra',
            'description_detaillee' => 'Assistance technique pour 40 coopératives agricoles cibles : gestion administrative, traçabilité des récoltes, marketing digital et valorisation des produits du terroir.',
            'categorie' => 'SERVICES',
            'type_prestation' => 'Assistance technique et formation',
            'intitule' => 'Convention d\'accompagnement des coopératives agricoles RSK',
            'mode_engagement' => 'Convention',
            'type_budget' => 'Fonctionnement',
            'delai_execution' => 270,
            'statut_dossier' => 'En cours',
            'fournisseur_id' => $fBeca->id,
            'numero_engagement' => 'ENG-CONV-2026-001',
            'credit_ouvert_cp' => 850000.00,
            'montant_engager_neuf' => 324000.00,
            'notification_ligne_id' => $ligneFncForm->id,
        ]);

        Budget::create([
            'consultation_id' => $conv1->id,
            'art' => '210', 'par' => '30', 'lig' => '01',
            'code_imputation' => '2103001',
            'intitule_ligne' => $ligneFncForm->libelle,
            'exercice_budgetaire' => 2026,
            'montant_estimatif_ht' => 270000.00,
            'tva' => 20.00,
            'montant_ttc' => 324000.00,
        ]);

        Engagement::create([
            'numero' => 'ENG-CONV-2026-001',
            'consultation_id' => $conv1->id,
            'fournisseur_id' => $fBeca->id,
            'montant_global' => 324000.00,
            'objet' => $conv1->objet_consultation,
            'statut_actuel' => 'Validé',
            'date_engagement' => '2026-02-05',
            'delai_execution' => 270,
        ]);

        RegistreEngagement::create([
            'consultation_id' => $conv1->id,
            'numero_ordre' => 5,
            'date_engagement' => '2026-02-05',
            'numero_rubrique' => '2103001',
            'mode_engagement' => 'Convention',
            'reference' => 'CONV-01/2026/DRCA-RSK',
            'budget' => 'Fonctionnement',
            'code' => '2103001',
            'art' => '210', 'par' => '30', 'lig' => '01',
            'intitule' => $ligneFncForm->libelle,
            'credit_ouvert_cp' => 850000.00,
            'montant_engager_neuf' => 324000.00,
            'objet' => $conv1->objet_consultation,
            'beneficiaire' => $fBeca->raison_sociale,
        ]);

        $ordConv1 = Ordonnancement::create([
            'num_ordonnancement' => 'ORD-2026-003',
            'num_op' => 'OP N° 19',
            'exercice' => '2026',
            'date_ordonnancement' => '2026-03-05',
            'consultation_id' => $conv1->id,
            'fournisseur_id' => $fBeca->id,
            'notification_ligne_id' => $ligneFncForm->id,
            'type_procedure' => 'Convention',
            'reference' => 'CONV-01/2026/DRCA-RSK',
            'beneficiaire_nom' => $fBeca->raison_sociale,
            'budget_type' => 'Fonctionnement',
            'creance' => 'Crédit neuf',
            'code_imputation' => '2103001',
            'article' => '210', 'paragraphe' => '30', 'ligne' => '01',
            'intitule_depense' => 'Convention d\'accompagnement des coopératives agricoles (Tranche 1)',
            'montant_brut' => 108000.00,
            'retenue_tva' => 18000.00,
            'retenue_ias' => 4500.00,
            'net_a_payer' => 85500.00,
            'credit_neuf' => 108000.00,
            'ras_total' => 22500.00,
            'rap_total' => 85500.00,
            'statut' => 'À payer',
            'observations' => 'Paiement de la tranche de démarrage (30%) de la convention.',
        ]);

        // 6.2 CONV N° 02/2026 : DÉVELOPPEMENT FILIÈRES MARAÎCHÈRES ET ÉCONOMIE D'EAU
        $ligneInvEau = $notifLignesMap['INV_415/20/10'];
        $fSmva = $fournisseursMap['Société Marocaine de Valorisation Agricole (SMVA)'];

        $conv2 = Consultation::create([
            'numero_consultation' => 'CONV-02/2026/DRCA-RSK',
            'numero_bc' => 'CONV-02/2026/DRCA-RSK',
            'annee' => 2026,
            'date_consultation' => '2026-02-01',
            'objet_consultation' => 'Convention cadre d\'assistance technique pour l\'optimisation de l\'irrigation et la valorisation des filières maraîchères dans le Gharb',
            'description_detaillee' => 'Mise en place de parcelles de démonstration d\'irrigation de précision et formation des agriculteurs sur la fertigation efficiente.',
            'categorie' => 'SERVICES',
            'type_prestation' => 'Expertise technique agricole',
            'intitule' => 'Convention irrigation et maraîchage Gharb',
            'mode_engagement' => 'Convention',
            'type_budget' => 'Investissement',
            'delai_execution' => 365,
            'statut_dossier' => 'En cours',
            'fournisseur_id' => $fSmva->id,
            'numero_engagement' => 'ENG-CONV-2026-002',
            'credit_ouvert_cp' => 600000.00,
            'montant_engager_neuf' => 480000.00,
            'notification_ligne_id' => $ligneInvEau->id,
        ]);

        Budget::create([
            'consultation_id' => $conv2->id,
            'art' => '415', 'par' => '20', 'lig' => '10',
            'code_imputation' => '4152010',
            'intitule_ligne' => $ligneInvEau->libelle,
            'exercice_budgetaire' => 2026,
            'montant_estimatif_ht' => 400000.00,
            'tva' => 20.00,
            'montant_ttc' => 480000.00,
        ]);

        RegistreEngagement::create([
            'consultation_id' => $conv2->id,
            'numero_ordre' => 6,
            'date_engagement' => '2026-02-15',
            'numero_rubrique' => '4152010',
            'mode_engagement' => 'Convention',
            'reference' => 'CONV-02/2026/DRCA-RSK',
            'budget' => 'Investissement',
            'code' => '4152010',
            'art' => '415', 'par' => '20', 'lig' => '10',
            'intitule' => $ligneInvEau->libelle,
            'credit_ouvert_cp' => 600000.00,
            'montant_engager_neuf' => 480000.00,
            'objet' => $conv2->objet_consultation,
            'beneficiaire' => $fSmva->raison_sociale,
        ]);

        // 6.3 CONV N° 03/2026 : DIGITALISATION DU CONSEIL AGRICOLE
        $conv3 = Consultation::create([
            'numero_consultation' => 'CONV-03/2026/DRCA-RSK',
            'numero_bc' => 'CONV-03/2026/DRCA-RSK',
            'annee' => 2026,
            'date_consultation' => '2026-02-18',
            'objet_consultation' => 'Convention pour le développement et la maintenance de la cartographie interactive des exploitations agricoles de la région RSK',
            'categorie' => 'SERVICES',
            'type_prestation' => 'Digitalisation et géoréférencement',
            'intitule' => 'Convention géoréférencement agricole RSK',
            'mode_engagement' => 'Convention',
            'type_budget' => 'Investissement',
            'delai_execution' => 180,
            'statut_dossier' => 'Préparation',
            'fournisseur_id' => $fSolutionsIt->id,
            'notification_ligne_id' => $ligneInvInfo->id,
        ]);

        Budget::create([
            'consultation_id' => $conv3->id,
            'art' => '415', 'par' => '10', 'lig' => '20',
            'code_imputation' => '4151020',
            'intitule_ligne' => $ligneInvInfo->libelle,
            'exercice_budgetaire' => 2026,
            'montant_estimatif_ht' => 200000.00,
            'tva' => 20.00,
            'montant_ttc' => 240000.00,
        ]);

        // =========================================================================
        // 7. APPELS D'OFFRES OUVERTS (AOO) RÉELS (pour /dashboard & /aoos)
        // =========================================================================

        // 7.1 AOO N° 01/2026 : RÉHABILITATION DES CCA (Attribué avec 2 Marchés)
        $aoo1 = Aoo::create([
            'num_aoo' => '01/2026/DRCA-RSK',
            'num_aoo_interne' => 'AOO-2026-001',
            'objet' => 'Travaux de réhabilitation et de mise à niveau des Centres de Conseil Agricole (CCA) de Sidi Kacem, Khemisset et Tiflet',
            'objet_ar' => 'أشغال تأهيل وتحديث مراكز الاستشارة الفلاحية بسيدي قاسم، الخميسات وتيفلت',
            'reference' => 'AOO-BAT-2026-01',
            'date_preparation' => '2026-01-10',
            'date_lettre' => '2026-01-15',
            'date_ouverture' => '2026-02-12',
            'heure_ouverture' => '10:30',
            'lieu_ouverture' => 'Salle des réunions de la DRCA-RSK, Kénitra',
            'nombre_lots' => 2,
            'budget' => 1250000.00,
            'type_budget' => 'Investissement',
            'art' => '415', 'par' => '10', 'lig' => '13',
            'notification_ligne_id' => $ligneInvBatiment->id,
            'statut' => 'Clôturé',
            'etat_avancement' => 'Attribué',
            'president_commission' => 'Dr. Boubker BOUAAMA',
            'rapporteur_commission' => 'Abdellah AZZOUGOUAGH',
            'commission_validee' => true,
            'journal_fr' => 'Le Matin',
            'date_publication_fr' => '2026-01-18',
            'reference_publication_fr' => 'PUB-LM-2026-88',
            'journal_ar' => 'Al Alam',
            'date_publication_ar' => '2026-01-18',
            'reference_publication_ar' => 'PUB-AA-2026-42',
            'date_publication_portail' => '2026-01-17',
            'ref_publication_portail' => 'PMP-2026-01-089',
        ]);

        // Lot 1 : CCA Sidi Kacem
        $lot1 = Lot::create([
            'aoo_id' => $aoo1->id,
            'num_lot' => 1,
            'objet_lot' => 'Travaux de réhabilitation du Centre de Conseil Agricole de Sidi Kacem',
            'estimation' => 650000.00,
            'tva_taux' => 20.00,
            'cautionnement_provisoire' => 10000.00,
            'notification_ligne_id' => $ligneInvBatiment->id,
            'art' => '415', 'par' => '10', 'lig' => '13',
            'imputation' => '4151013',
            'attributaire_fournisseur_id' => $fConstruct->id,
            'montant_attribue_ht' => 516666.67,
            'tva_taux_attribue' => 20.00,
            'montant_attribue_ttc' => 620000.00,
            'delai_execution_jours' => 90,
        ]);

        // Lot 2 : CCA Khemisset & Tiflet
        $fSbtg = $fournisseursMap['Société Bâtiment & Travaux du Gharb SARL'];
        $lot2 = Lot::create([
            'aoo_id' => $aoo1->id,
            'num_lot' => 2,
            'objet_lot' => 'Travaux de réhabilitation des Centres de Conseil Agricole de Khemisset et Tiflet',
            'estimation' => 600000.00,
            'tva_taux' => 20.00,
            'cautionnement_provisoire' => 9000.00,
            'notification_ligne_id' => $ligneInvBatiment->id,
            'art' => '415', 'par' => '10', 'lig' => '13',
            'imputation' => '4151013',
            'attributaire_fournisseur_id' => $fSbtg->id,
            'montant_attribue_ht' => 487500.00,
            'tva_taux_attribue' => 20.00,
            'montant_attribue_ttc' => 585000.00,
            'delai_execution_jours' => 90,
        ]);

        // Marchés associés
        $m1 = Marche::create([
            'num_marche' => 'M-01/2026/DRCA-RSK',
            'aoo_id' => $aoo1->id,
            'lot_id' => $lot1->id,
            'fournisseur_id' => $fConstruct->id,
            'lot' => 'Lot 1 - Sidi Kacem',
            'titulaire' => $fConstruct->raison_sociale,
            'objet_marche' => 'Réhabilitation du Centre de Conseil Agricole de Sidi Kacem',
            'qualite_gerant' => $fConstruct->qualite_representant,
            'exercice' => '2026',
            'type_budget' => 'Investissement',
            'code_budget' => '4151013',
            'intitule_budget' => $ligneInvBatiment->libelle,
            'article_budget' => '415', 'paragraphe_budget' => '10', 'ligne_budget' => '13',
            'montant' => 620000.00,
            'date_signature' => '2026-02-18',
            'date_approbation' => '2026-02-22',
            'date_notification_marche' => '2026-02-24',
            'os_numero' => 'OS N° 01/2026',
            'os_date_signature' => '2026-02-25',
            'os_date_effet' => '2026-03-01',
            'statut_marche' => 'En cours d\'exécution',
            'statut_acte_engagement' => 'Validé',
            'num_engagement' => 'ENG-M-2026-001',
            'delai_execution' => 90,
            'notification_ligne_id' => $ligneInvBatiment->id,
        ]);

        $m2 = Marche::create([
            'num_marche' => 'M-02/2026/DRCA-RSK',
            'aoo_id' => $aoo1->id,
            'lot_id' => $lot2->id,
            'fournisseur_id' => $fSbtg->id,
            'lot' => 'Lot 2 - Khemisset et Tiflet',
            'titulaire' => $fSbtg->raison_sociale,
            'objet_marche' => 'Réhabilitation des Centres de Conseil Agricole de Khemisset et Tiflet',
            'qualite_gerant' => $fSbtg->qualite_representant,
            'exercice' => '2026',
            'type_budget' => 'Investissement',
            'code_budget' => '4151013',
            'intitule_budget' => $ligneInvBatiment->libelle,
            'article_budget' => '415', 'paragraphe_budget' => '10', 'ligne_budget' => '13',
            'montant' => 585000.00,
            'date_signature' => '2026-02-19',
            'date_approbation' => '2026-02-23',
            'date_notification_marche' => '2026-02-25',
            'os_numero' => 'OS N° 02/2026',
            'os_date_signature' => '2026-02-26',
            'os_date_effet' => '2026-03-01',
            'statut_marche' => 'En cours d\'exécution',
            'statut_acte_engagement' => 'Validé',
            'num_engagement' => 'ENG-M-2026-002',
            'delai_execution' => 90,
            'notification_ligne_id' => $ligneInvBatiment->id,
        ]);

        // Registre des engagements pour les marchés
        RegistreEngagement::create([
            'marche_id' => $m1->id,
            'numero_ordre' => 7,
            'date_engagement' => '2026-02-20',
            'numero_rubrique' => '4151013',
            'mode_engagement' => 'Marché',
            'reference' => 'M-01/2026/DRCA-RSK',
            'budget' => 'Investissement',
            'code' => '4151013',
            'art' => '415', 'par' => '10', 'lig' => '13',
            'intitule' => $ligneInvBatiment->libelle,
            'credit_ouvert_cp' => 1800000.00,
            'montant_engager_neuf' => 620000.00,
            'objet' => $m1->objet_marche,
            'beneficiaire' => $fConstruct->raison_sociale,
        ]);

        RegistreEngagement::create([
            'marche_id' => $m2->id,
            'numero_ordre' => 8,
            'date_engagement' => '2026-02-20',
            'numero_rubrique' => '4151013',
            'mode_engagement' => 'Marché',
            'reference' => 'M-02/2026/DRCA-RSK',
            'budget' => 'Investissement',
            'code' => '4151013',
            'art' => '415', 'par' => '10', 'lig' => '13',
            'intitule' => $ligneInvBatiment->libelle,
            'credit_ouvert_cp' => 1800000.00,
            'montant_engager_neuf' => 585000.00,
            'objet' => $m2->objet_marche,
            'beneficiaire' => $fSbtg->raison_sociale,
        ]);

        // Liquidation et Ordonnancement du décompte N°1 du Marché M1
        $liqM1 = Liquidation::create([
            'num_liquidation' => 'LIQ-M-01/2026',
            'exercice_budgetaire' => 2026,
            'objet_liquidation' => 'Décompte N°1 - Travaux CCA Sidi Kacem',
            'marche_id' => $m1->id,
            'num_decompte' => 1,
            'date_decompte' => '2026-03-10',
            'montant_brut_ttc' => 248000.00,
            'montant_brut_ht' => 206666.67,
            'montant_ttc' => 248000.00,
            'retenue_garantie' => 24800.00,
            'net_a_payer' => 223200.00,
            'statut' => 'Validé',
        ]);

        $ordM1 = Ordonnancement::create([
            'num_ordonnancement' => 'ORD-2026-001',
            'num_op' => 'OP N° 05',
            'exercice' => '2026',
            'date_ordonnancement' => '2026-03-12',
            'liquidation_id' => $liqM1->id,
            'marche_id' => $m1->id,
            'fournisseur_id' => $fConstruct->id,
            'notification_ligne_id' => $ligneInvBatiment->id,
            'type_procedure' => 'Marché',
            'reference' => 'M-01/2026/DRCA-RSK',
            'beneficiaire_nom' => $fConstruct->raison_sociale,
            'budget_type' => 'Investissement',
            'creance' => 'Crédit neuf',
            'code_imputation' => '4151013',
            'article' => '415', 'paragraphe' => '10', 'ligne' => '13',
            'intitule_depense' => 'Décompte N°1 - Travaux CCA Sidi Kacem',
            'montant_brut' => 248000.00,
            'retenue_tva' => 41333.33,
            'retenue_ias' => 10333.33,
            'net_a_payer' => 196333.34,
            'credit_neuf' => 248000.00,
            'ras_total' => 51666.66,
            'rap_total' => 196333.34,
            'statut' => 'À payer',
            'observations' => 'Ordonnancement du décompte N°1 du Marché M-01/2026.',
        ]);

        // 7.2 AOO N° 02/2026 : VÉHICULES UTILITAIRES TOUT-TERRAIN
        $ligneInvVehicules = $notifLignesMap['INV_415/30/10'];
        $fAutoHall = $fournisseursMap['Auto Hall Maroc SA'];

        $aoo2 = Aoo::create([
            'num_aoo' => '02/2026/DRCA-RSK',
            'num_aoo_interne' => 'AOO-2026-002',
            'objet' => 'Acquisition de 8 véhicules utilitaires tout-terrain 4x4 au profit des conseillers agricoles de la région Rabat-Salé-Kénitra',
            'objet_ar' => 'اقتناء 8 سيارات نفعية رباعية الدفع لفائدة المستشارين الفلاحيين بجهة الرباط-سلا-القنيطرة',
            'reference' => 'AOO-VEH-2026-02',
            'date_preparation' => '2026-01-20',
            'date_ouverture' => '2026-02-25',
            'heure_ouverture' => '11:00',
            'lieu_ouverture' => 'Siège de la DRCA-RSK, Kénitra',
            'nombre_lots' => 1,
            'budget' => 1850000.00,
            'type_budget' => 'Investissement',
            'art' => '415', 'par' => '30', 'lig' => '10',
            'notification_ligne_id' => $ligneInvVehicules->id,
            'statut' => 'Attribué',
            'etat_avancement' => 'Attribué',
            'president_commission' => 'Dr. Boubker BOUAAMA',
            'rapporteur_commission' => 'Abdellah AZZOUGOUAGH',
            'commission_validee' => true,
            'journal_fr' => 'L\'Opinion',
            'journal_ar' => 'Rissalat Al Oumma',
        ]);

        $lotVeh = Lot::create([
            'aoo_id' => $aoo2->id,
            'num_lot' => 1,
            'objet_lot' => 'Acquisition de 8 véhicules tout-terrain 4x4 double cabine',
            'estimation' => 1850000.00,
            'tva_taux' => 20.00,
            'cautionnement_provisoire' => 25000.00,
            'notification_ligne_id' => $ligneInvVehicules->id,
            'art' => '415', 'par' => '30', 'lig' => '10',
            'imputation' => '4153010',
            'attributaire_fournisseur_id' => $fAutoHall->id,
            'montant_attribue_ht' => 1491666.67,
            'tva_taux_attribue' => 20.00,
            'montant_attribue_ttc' => 1790000.00,
            'delai_execution_jours' => 45,
        ]);

        $m3 = Marche::create([
            'num_marche' => 'M-03/2026/DRCA-RSK',
            'aoo_id' => $aoo2->id,
            'lot_id' => $lotVeh->id,
            'fournisseur_id' => $fAutoHall->id,
            'lot' => 'Lot Unique',
            'titulaire' => $fAutoHall->raison_sociale,
            'objet_marche' => 'Fourniture de 8 véhicules tout-terrain 4x4',
            'qualite_gerant' => $fAutoHall->qualite_representant,
            'exercice' => '2026',
            'type_budget' => 'Investissement',
            'code_budget' => '4153010',
            'intitule_budget' => $ligneInvVehicules->libelle,
            'article_budget' => '415', 'paragraphe_budget' => '30', 'ligne_budget' => '10',
            'montant' => 1790000.00,
            'date_signature' => '2026-03-02',
            'date_approbation' => '2026-03-06',
            'date_notification_marche' => '2026-03-08',
            'statut_marche' => 'Notifié',
            'statut_acte_engagement' => 'Validé',
            'num_engagement' => 'ENG-M-2026-003',
            'delai_execution' => 45,
            'notification_ligne_id' => $ligneInvVehicules->id,
        ]);

        RegistreEngagement::create([
            'marche_id' => $m3->id,
            'numero_ordre' => 9,
            'date_engagement' => '2026-03-05',
            'numero_rubrique' => '4153010',
            'mode_engagement' => 'Marché',
            'reference' => 'M-03/2026/DRCA-RSK',
            'budget' => 'Investissement',
            'code' => '4153010',
            'art' => '415', 'par' => '30', 'lig' => '10',
            'intitule' => $ligneInvVehicules->libelle,
            'credit_ouvert_cp' => 2400000.00,
            'montant_engager_neuf' => 1790000.00,
            'objet' => $m3->objet_marche,
            'beneficiaire' => $fAutoHall->raison_sociale,
        ]);

        // 7.3 AOO N° 03/2026 : GARDIENNAGE DES SITES (Évaluation des offres)
        $ligneFncTelecom = $notifLignesMap['FNC_210/20/10'];

        $aoo3 = Aoo::create([
            'num_aoo' => '03/2026/DRCA-RSK',
            'num_aoo_interne' => 'AOO-2026-003',
            'objet' => 'Prestation de gardiennage et surveillance des locaux de la DRCA-RSK et des centres provinciaux rattachés',
            'objet_ar' => 'خدمات الحراسة والمراقبة لمقرات الإدارة الجهوية والمراكز التابعة لها',
            'reference' => 'AOO-SEC-2026-03',
            'date_preparation' => '2026-02-01',
            'date_ouverture' => '2026-03-15',
            'heure_ouverture' => '10:00',
            'lieu_ouverture' => 'Siège de la DRCA-RSK, Kénitra',
            'nombre_lots' => 1,
            'budget' => 360000.00,
            'type_budget' => 'Fonctionnement',
            'art' => '210', 'par' => '20', 'lig' => '10',
            'notification_ligne_id' => $ligneFncTelecom->id,
            'statut' => 'En cours d\'évaluation',
            'etat_avancement' => 'Commission en cours',
            'president_commission' => 'Dr. Boubker BOUAAMA',
            'rapporteur_commission' => 'Abdellah AZZOUGOUAGH',
            'journal_fr' => 'Le Matin',
            'journal_ar' => 'Al Alam',
        ]);

        Lot::create([
            'aoo_id' => $aoo3->id,
            'num_lot' => 1,
            'objet_lot' => 'Gardiennage et sécurité des sites DRCA-RSK',
            'estimation' => 360000.00,
            'tva_taux' => 20.00,
            'cautionnement_provisoire' => 6000.00,
            'notification_ligne_id' => $ligneFncTelecom->id,
            'art' => '210', 'par' => '20', 'lig' => '10',
            'imputation' => '2102010',
        ]);

        // 7.4 AOO N° 04/2026 : INFRASTRUCTURE RÉSEAU ET SERVEURS (Publié)
        $aoo4 = Aoo::create([
            'num_aoo' => '04/2026/DRCA-RSK',
            'num_aoo_interne' => 'AOO-2026-004',
            'objet' => 'Fourniture, installation et configuration d\'équipements d\'interconnexion réseau et serveurs d\'hébergement régional',
            'objet_ar' => 'توريد وتركيب وتجهيز معدات الربط الشبكي والخوادم المعلوماتية',
            'reference' => 'AOO-INFO-2026-04',
            'date_preparation' => '2026-02-15',
            'date_ouverture' => '2026-04-05',
            'heure_ouverture' => '11:00',
            'lieu_ouverture' => 'Siège de la DRCA-RSK, Kénitra',
            'nombre_lots' => 1,
            'budget' => 650000.00,
            'type_budget' => 'Investissement',
            'art' => '415', 'par' => '10', 'lig' => '20',
            'notification_ligne_id' => $ligneInvInfo->id,
            'statut' => 'Publié',
            'etat_avancement' => 'Publié',
            'president_commission' => 'Dr. Boubker BOUAAMA',
            'journal_fr' => 'Le Matin',
            'journal_ar' => 'Al Alam',
        ]);

        Lot::create([
            'aoo_id' => $aoo4->id,
            'num_lot' => 1,
            'objet_lot' => 'Infrastructure réseau et serveurs',
            'estimation' => 650000.00,
            'tva_taux' => 20.00,
            'cautionnement_provisoire' => 10000.00,
            'notification_ligne_id' => $ligneInvInfo->id,
            'art' => '415', 'par' => '10', 'lig' => '20',
            'imputation' => '4151020',
        ]);

        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } else {
            Schema::enableForeignKeyConstraints();
        }
    }
}
