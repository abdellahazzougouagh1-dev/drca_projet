<?php

namespace Database\Seeders;

use App\Models\Aoo;
use Illuminate\Database\Seeder;

class AooSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Aoo::factory()->count(20)->create();
    }
}
