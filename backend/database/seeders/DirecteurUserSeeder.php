<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DirecteurUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'directeur@onca.ma'],
            [
                'name' => 'Directeur Régional',
                'password' => Hash::make('Directeur@2026'),
                'is_admin' => true,
                'role' => 'directeur',
            ]
        );
    }
}
