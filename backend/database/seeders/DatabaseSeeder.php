<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Appeler le seeder des fournisseurs
        $this->call([
            FournisseurSeeder::class,
            AdminUserSeeder::class,
            DirecteurUserSeeder::class,
            WorkflowEngineSeeder::class,
        ]);

        // Récupérer les fournisseurs créés
        $fournisseurs = \App\Models\Fournisseur::all();

        // Créer 5 consultations avec leurs budgets
        \App\Models\Consultation::factory(5)->make()->each(function ($consultation) use ($fournisseurs) {
            // Attribuer un fournisseur aléatoire
            $consultation->fournisseur_id = $fournisseurs->random()->id;
            $consultation->save();

            // Créer un budget pour cette consultation
            \App\Models\Budget::factory()->create([
                'consultation_id' => $consultation->id,
            ]);
        });
    }
}
