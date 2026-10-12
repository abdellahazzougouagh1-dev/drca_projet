<?php

namespace Database\Factories;

use App\Models\Consultation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsultationFactory extends Factory
{
    protected $model = Consultation::class;

    public function definition(): array
    {
        return [
            'numero_consultation' => 'CONS-' . $this->faker->year() . '-' . $this->faker->unique()->numerify('####'),
            'annee' => $this->faker->year(),
            'date_consultation' => $this->faker->date(),
            'objet_consultation' => $this->faker->sentence(),
            'description_detaillee' => $this->faker->paragraph(),
            'categorie' => $this->faker->randomElement(['Travaux', 'Fournitures', 'Services']),
            'type_prestation' => $this->faker->word(),
            'mode_engagement' => $this->faker->randomElement(['BC', 'Convention']),
            'type_budget' => $this->faker->randomElement(['Investissement', 'Fonctionnement']),
            'delai_execution' => $this->faker->numberBetween(30, 365),
            'statut_dossier' => $this->faker->randomElement(['En cours', 'Validé', 'Clôturé']),
        ];
    }
}
