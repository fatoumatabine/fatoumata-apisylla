<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'titulaire' => $this->faker->name(),
            'nci' => $this->faker->unique()->numerify('#############'), // 13 chiffres pour le NCI
            'email' => $this->faker->unique()->safeEmail(),
            'telephone' => $this->faker->phoneNumber(),
            'adresse' => $this->faker->address(),
            'password' => bcrypt('password'), // Mot de passe par défaut pour les tests
            'code' => $this->faker->randomNumber(6, true), // Code par défaut pour les tests
        ];
    }
}
