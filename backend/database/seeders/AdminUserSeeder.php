<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@onca.ma'],
            [
                'name' => 'Admin Onca',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
            ]
        );
    }
}
