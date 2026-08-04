<?php

namespace Database\Factories;

use App\Models\Fournisseur;
use Illuminate\Database\Eloquent\Factories\Factory;

class FournisseurFactory extends Factory
{
    protected $model = Fournisseur::class;

    public function definition(): array
    {
        return [
            'raison_sociale' => $this->faker->company(),
            'ice' => $this->faker->unique()->numerify('##############'),
            'if' => $this->faker->numerify('########'),
            'patente' => $this->faker->numerify('########'),
            'rc' => $this->faker->numerify('######'),
            'adresse' => $this->faker->address(),
            'ville' => $this->faker->city(),
            'telephone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->companyEmail(),
            'representant' => $this->faker->name(),
            'domaine_activite' => $this->faker->randomElement(['Informatique', 'BTP', 'Fournitures de bureau', 'Services de nettoyage', 'Consulting']),
        ];
    }
}
