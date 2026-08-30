<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Fournisseur;
use App\Models\Aoo;
use App\Models\Lot;
use App\Models\Marche;
use Carbon\Carbon;

class AbdellahAzzougouaghSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the Fournisseur
        $fournisseur = Fournisseur::firstOrCreate(
            ['ice' => '123456789012345'],
            [
                'raison_sociale' => 'Entreprise Abdellah Azzougouagh',
                'if' => '12345678',
                'patente' => '12345678',
                'rc' => '12345',
                'adresse' => 'Quartier Industriel, Agadir',
                'ville' => 'Agadir',
                'telephone' => '0600000000',
                'email' => 'contact@azzougouagh.ma',
                'representant' => 'Abdellah Azzougouagh',
                'qualite_representant' => 'Directeur Général',
                'cnss' => '1234567',
                'banque' => 'Attijariwafa Bank',
                'agence_bancaire' => 'Agence Centre',
                'rib' => '007010000000000000000000',
                'titulaire_compte' => 'Abdellah Azzougouagh',
                'domaine_activite' => 'Travaux de construction et aménagement',
            ]
        );

        // Create AOOs
        for ($i = 1; $i <= 3; $i++) {
            $aoo = Aoo::firstOrCreate(
                ['num_aoo' => "0$i/2026/DRCA"],
                [
                    'objet' => "Travaux d'aménagement et d'entretien des locaux - Lot $i",
                    'reference' => "Ref-0$i-2026",
                    'date_preparation' => Carbon::now()->subMonths(3),
                    'journal_fr' => 'Le Matin',
                    'reference_publication_fr' => "Pub-FR-0$i",
                    'date_publication_fr' => Carbon::now()->subMonths(2),
                    'journal_ar' => 'Assahraa Al Maghribia',
                    'reference_publication_ar' => "Pub-AR-0$i",
                    'date_publication_ar' => Carbon::now()->subMonths(2),
                    'date_ouverture' => Carbon::now()->subMonth(),
                    'heure_ouverture' => '10:00:00',
                    'nombre_lots' => 1,
                    'budget' => rand(100000, 500000),
                    'statut' => 'Cloturé',
                ]
            );

            // Create Lot
            $lot = Lot::firstOrCreate(
                ['aoo_id' => $aoo->id, 'num_lot' => 1],
                [
                    'objet_lot' => "Lot unique - " . $aoo->objet,
                    'estimation' => $aoo->budget,
                    'tva_taux' => 20,
                    'cautionnement_provisoire' => $aoo->budget * 0.02,
                    'attributaire_fournisseur_id' => $fournisseur->id,
                ]
            );

            // Create Marche
            Marche::firstOrCreate(
                ['num_marche' => "M0$i/2026"],
                [
                    'aoo_id' => $aoo->id,
                    'lot_id' => $lot->id,
                    'fournisseur_id' => $fournisseur->id,
                    'lot' => '1',
                    'titulaire' => $fournisseur->raison_sociale,
                    'objet_marche' => $aoo->objet,
                    'qualite_gerant' => $fournisseur->qualite_representant,
                    'exercice' => '2026',
                    'type_budget' => 'Fonctionnement',
                    'code_budget' => '10.10.10',
                    'intitule_budget' => 'Entretien des bâtiments',
                    'montant' => $aoo->budget * 0.95, // 5% moins cher
                    'date_signature' => Carbon::now()->subDays(15),
                    'date_approbation' => Carbon::now()->subDays(10),
                    'date_notification_marche' => Carbon::now()->subDays(5),
                    'statut' => 'En cours d\'exécution',
                    'statut_acte_engagement' => 'Validé',
                    'statut_marche' => 'Approuvé',
                    'taux_tva' => 20,
                    'delai_execution' => rand(30, 120),
                ]
            );
        }
    }
}
