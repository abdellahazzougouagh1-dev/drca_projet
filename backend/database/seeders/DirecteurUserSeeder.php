<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DirecteurUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('DIRECTOR_EMAIL');
        $password = env('DIRECTOR_INITIAL_PASSWORD');
        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('DIRECTOR_EMAIL must be set to a valid email address.');
        }
        if (! is_string($password) || strlen($password) < 12) {
            throw new \RuntimeException('DIRECTOR_INITIAL_PASSWORD must be set and contain at least 12 characters.');
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Directeur Régional',
                'password' => Hash::make($password),
                'is_admin' => true,
                'is_active' => true,
                'role' => 'directeur',
            ]
        );
    }
}
