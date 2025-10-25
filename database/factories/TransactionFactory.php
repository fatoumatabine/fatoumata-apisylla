<?php

namespace Database\Factories;

use App\Models\Compte;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'compte_id' => Compte::factory(),
            'type' => $this->faker->randomElement(['depot', 'retrait', 'virement']),
            'montant' => $this->faker->randomFloat(2, 10, 1000),
            'devise' => $this->faker->currencyCode(),
            'description' => $this->faker->sentence(),
            'dateTransaction' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
