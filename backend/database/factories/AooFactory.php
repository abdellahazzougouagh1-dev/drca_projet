<?php

namespace Database\Factories;

use App\Models\Aoo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Aoo>
 */
class AooFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['en_preparation', 'en_commission', 'en_analyse', 'en_attribution', 'clot'];
        $year = $this->faker->year();
        $numAoo = 'AOO/' . $this->faker->randomNumber(4) . '/' . $year;

        return [
            'num_aoo' => $numAoo,
            'objet' => $this->faker->sentence(8),
            'journal_fr' => $this->faker->word(),
            'journal_ar' => $this->faker->word(),
            'date_ouverture' => $this->faker->dateTimeBetween('-6 months', '+2 months'),
            'heure_ouverture' => $this->faker->time('H:i'),
            'nombre_lots' => $this->faker->numberBetween(1, 5),
            'budget' => $this->faker->numberBetween(50000, 5000000),
            'art' => $this->faker->word(),
            'par' => $this->faker->word(),
            'lig' => $this->faker->word(),
            'statut' => $this->faker->randomElement($statuses),
            'president_commission' => $this->faker->name(),
            'membres_commission' => [],
            'etat_avancement' => $this->faker->randomElement(['Préparation', 'Ouverture', 'Commission', 'Analyse', 'Attribution', 'Clôture']),
            'num_decision_nomination' => 'DEC/' . $this->faker->randomNumber(3) . '/' . $year,
            'date_lettre' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'lieu_ouverture' => 'Siège de la direction régionale du conseil agricole Rabat-Salé-Kénitra',
            'num_aoo_interne' => 'INT/' . $this->faker->randomNumber(4) . '/' . $year,
        ];
    }
}
