<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Consultation;
use App\Models\Fournisseur;
use Carbon\Carbon;

class CustomConsultationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // --------------------------------------------------------------
        // 1️⃣ OPTIONAL – pick an existing supplier or create a dummy one
        // --------------------------------------------------------------
        $fournisseur = Fournisseur::first(); // use the first supplier we have
        if (! $fournisseur) {
            // create a placeholder supplier if none exists
            $fournisseur = \App\Models\Fournisseur::create([
                'nom'               => 'Fournisseur Exemple SARL',
                'adresse'           => '123 Rue Exemple, Kénitra',
                'telephone'         => '+212 6 00 00 00 00',
                'email'             => 'contact@exemple.ma',
                'registre_commerce' => '123456',
            ]);
        }

        // --------------------------------------------------------------
        // 2️⃣ Insert the consultation
        // --------------------------------------------------------------
        $consultation = Consultation::create([
            // Core identifiers
            'numero_consultation' => uniqid('DEMO-'),
            'annee'               => 2026,
            'date_consultation'   => '2026-04-15',

            // Descriptive fields
            'objet_consultation'  => 'Fourniture de matériel informatique pour les CCA de Kénitra, Sidi Kacem et Sidi Slimane',
            'description_detaillee'=> 'Acquisition de postes de travail, imprimantes réseau, écrans 24", câblage et consommables afin de moderniser les Centres de Conseil Agricole.',
            'categorie'           => 'MATÉRIEL INFORMATIQUE',
            'type_prestation'     => 'Fourniture',
            'intitule'            => 'Matériel informatique CCA 2026',
            'mode_engagement'    => 'BC', // Bon de commande
            'type_budget'        => 'Investissement',
            'delai_execution'    => 30,
            'statut_dossier'     => 'Programmation',

            // Linking to a supplier (optional)
            'fournisseur_id'     => $fournisseur->id,

            // Additional optional columns (you can keep or remove)
            'numero_avis'         => 'AVIS-2026-001',
            'date_limite_devis'   => '2026-04-20',
            'heure_limite_devis'  => '12:00',
            'lieu_execution'     => 'Kénitra, Sidi Kacem, Sidi Slimane',
            'numero_bc' => uniqid('BC-'),
            'numero_engagement'   => uniqid('ENG-'),
            'credit_ouvert_cp'   => 150000.00,
            'montant_engager_neuf'=> 120000.00,
        ]);

        // --------------------------------------------------------------
        // 3️⃣ (Optional) – create a budget line linked to this consultation
        // --------------------------------------------------------------
        \App\Models\Budget::create([
            'consultation_id' => $consultation->id,
            'art'            => '210',
            'par'            => '10',
            'lig'            => '05',
            'code_imputation'=> '2101005',
            'intitule_ligne' => 'Matériel informatique – CCA',
            'exercice_budgetaire'=> 2026,
            'montant_estimatif_ht'=> 100000.00,
            'tva'            => 20.00,
            'montant_ttc'    => 120000.00,
        ]);

        // --------------------------------------------------------------
        // 4️⃣ (Optional) – add a couple of prestations (line items)
        // --------------------------------------------------------------
        \App\Models\Prestation::create([
            'consultation_id' => $consultation->id,
            'designation'     => 'Poste de travail HP EliteDesk 800 G6',
            'unite'           => 'Unité',
            'quantite'        => 25,
            'prix_unitaire_ht'=> 3000.00,
            'tva'             => 20.00,
        ]);
        \App\Models\Prestation::create([
            'consultation_id' => $consultation->id,
            'designation'     => 'Écran 24" Dell UltraSharp',
            'unite'           => 'Unité',
            'quantite'        => 25,
            'prix_unitaire_ht'=> 1500.00,
            'tva'             => 20.00,
        ]);

        $this->command->info("✅ Custom consultation DEMO-2026-001 created (id {$consultation->id})");
    }
}
