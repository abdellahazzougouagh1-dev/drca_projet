<?php

namespace Database\Factories;

use App\Models\Marche;
use App\Models\Aoo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Marche>
 */
class MarcheFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['en_creation', 'en_approbation', 'en_execution', 'en_reception', 'clot'];
        $year = $this->faker->year();
        $numMarche = 'M-' . $this->faker->randomNumber(5) . '-' . $year;

        return [
            'aoo_id' => Aoo::factory(),
            'num_marche' => $numMarche,
            'lot_id' => null,
            'fournisseur_id' => null,
            'lot' => 'LOT ' . $this->faker->randomNumber(2),
            'titulaire' => $this->faker->company(),
            'objet_marche' => $this->faker->sentence(12),
            'qualite_gerant' => $this->faker->randomElement(['Gérant', 'Directeur', 'Responsable']),
            'exercice' => $year,
            'type_budget' => $this->faker->randomElement(['Investissement', 'Fonctionnement', 'Exploitation']),
            'code_budget' => $this->faker->word(),
            'intitule_budget' => $this->faker->sentence(5),
            'montant' => $this->faker->numberBetween(100000, 2000000),
            'date_signature' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'date_approbation' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'date_notification_marche' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'os_numero' => 'OS/' . $this->faker->randomNumber(4) . '/' . $year,
            'os_date_signature' => $this->faker->dateTimeBetween('-2 weeks', 'now'),
            'os_date_effet' => $this->faker->dateTimeBetween('-1 week', '+1 week'),
            'num_decision' => 'DEC/' . $this->faker->randomNumber(3) . '/' . $year,
            'date_decision' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'date_reunion_commission' => $this->faker->dateTimeBetween('-2 weeks', 'now'),
            'heure_reunion_commission' => $this->faker->time('H:i'),
            'lieu_reunion_commission' => 'au siège de la DRCA-RSK',
            'statut' => $this->faker->randomElement($statuses),
            'agent_suivi' => $this->faker->name(),
            'date_reception_finale' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
            'commission_reception' => [],
        ];
    }
}
