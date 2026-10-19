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
        // Appeler les seeders de base et de données réelles
        $this->call([
            LigneBudgetaireSeeder::class,
            FournisseurSeeder::class,
            AdminUserSeeder::class,
            DirecteurUserSeeder::class,
            WorkflowEngineSeeder::class,
            ProductionRealisticDataSeeder::class,
        ]);
    }
}
