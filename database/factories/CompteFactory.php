<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Compte>
 */
class CompteFactory extends Factory
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
            'numeroCompte' => $this->faker->unique()->randomNumber(9),
            'titulaire' => $this->faker->name(),
            'type' => $this->faker->randomElement(['epargne', 'cheque']),
            'solde' => $this->faker->randomFloat(2, 0, 100000),
            'devise' => $this->faker->currencyCode(),
            'dateCreation' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'statut' => $this->faker->randomElement(['actif', 'bloque', 'ferme']),
            'metadata' => ['derniereModification' => now()->toIso8601String(), 'version' => 1],
            'client_id' => (string) Client::factory()->create()->id,
            'deleted_at' => null, // Ensure accounts are not soft-deleted by default
        ];
    }
}
