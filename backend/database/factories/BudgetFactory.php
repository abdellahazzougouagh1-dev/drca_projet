<?php

namespace Database\Factories;

use App\Models\Budget;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        $montant_estimatif_ht = $this->faker->randomFloat(2, 1000, 100000);
        $tva = $this->faker->randomElement([7, 10, 14, 20]);

        return [
            'art' => $this->faker->numerify('##'),
            'par' => $this->faker->numerify('##'),
            'lig' => $this->faker->numerify('##'),
            'code_imputation' => $this->faker->numerify('######'),
            'exercice_budgetaire' => $this->faker->year(),
            'montant_estimatif_ht' => $montant_estimatif_ht,
            'tva' => $tva,
        ];
    }
}
