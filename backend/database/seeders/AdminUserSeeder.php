<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_INITIAL_PASSWORD');
        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('ADMIN_EMAIL must be set to a valid email address.');
        }
        if (! is_string($password) || strlen($password) < 12) {
            throw new \RuntimeException('ADMIN_INITIAL_PASSWORD must be set and contain at least 12 characters.');
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin Onca',
                'password' => Hash::make($password),
                'is_admin' => true,
                'is_active' => true,
            ]
        );
    }
}
