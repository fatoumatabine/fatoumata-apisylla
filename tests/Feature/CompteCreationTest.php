<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Client;
use App\Models\Compte;
use App\Models\User; // Importation du modèle User
use Illuminate\Support\Facades\Event;
use App\Events\ClientCreated;
use Laravel\Passport\Passport; // Importation de Passport

class CompteCreationTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Créer un utilisateur et un jeton Passport pour l'authentification des tests
        $this->user = User::factory()->create(['role' => 'admin']); // Créer un admin pour les tests
        Passport::actingAs($this->user);

        // Assurez-vous que les événements sont faux pour éviter l'envoi réel d'e-mails/SMS pendant les tests
        Event::fake();
    }

    /** @test */
    public function it_can_create_a_new_account_with_a_new_client()
    {
        $clientData = [
            'titulaire' => $this->faker->name,
            'nci' => '1234567890123', // NCI valide pour le Sénégal
            'email' => $this->faker->unique()->safeEmail,
            'telephone' => '+22177' . $this->faker->randomNumber(7, true), // Téléphone valide pour le Sénégal
            'adresse' => $this->faker->address,
        ];

        $compteData = [
            'type' => 'epargne',
            'solde' => 50000,
            'devise' => 'XOF',
            'client' => $clientData,
        ];

        $response = $this->postJson('/api/v1/comptes', $compteData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Compte créé avec succès',
                 ]);

        $this->assertDatabaseHas('clients', [
            'email' => $clientData['email'],
            'nci' => $clientData['nci'],
        ]);

        $this->assertDatabaseHas('comptes', [
            'type' => 'epargne',
            'solde' => 50000,
            'devise' => 'XOF',
        ]);

        Event::assertDispatched(ClientCreated::class, function ($event) use ($clientData) {
            return $event->client->email === $clientData['email'];
        });
    }

    /** @test */
    public function it_can_create_a_new_account_with_an_existing_client_by_id()
    {
        $existingClient = Client::factory()->create();

        $compteData = [
            'type' => 'cheque',
            'solde' => 100000,
            'devise' => 'EUR',
            'client' => [
                'id' => $existingClient->id,
                // Les autres champs client ne sont pas nécessaires si l'ID est fourni
            ],
        ];

        $response = $this->postJson('/api/v1/comptes', $compteData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Compte créé avec succès',
                 ]);

        $this->assertDatabaseHas('comptes', [
            'type' => 'cheque',
            'solde' => 100000, // Le solde est stocké comme 'solde' dans la base de données
            'devise' => 'EUR',
            'client_id' => $existingClient->id,
        ]);

        // ClientCreated event should not be dispatched for existing clients
        Event::assertNotDispatched(ClientCreated::class);
    }

    /** @test */
    public function it_returns_validation_errors_for_invalid_data()
    {
        $invalidCompteData = [
            'type' => 'invalid_type', // Type invalide
            'solde' => 5000, // Solde inférieur au minimum (10000)
            'devise' => 'EU', // Devise de taille incorrecte
            'client' => [
                'titulaire' => '', // Titulaire manquant
                'nci' => '123', // NCI invalide
                'email' => 'invalid-email', // Email invalide
                'telephone' => '12345', // Téléphone invalide
                'adresse' => '', // Adresse manquante
            ],
        ];

        $response = $this->postJson('/api/v1/comptes', $invalidCompteData);

        $response->assertStatus(422) // Laravel retourne 422 pour les erreurs de validation
                 ->assertJsonValidationErrors([
                     'type',
                     'solde',
                     'devise',
                     'client.titulaire',
                     'client.nci',
                     'client.email',
                     'client.telephone',
                     'client.adresse',
                 ]);
    }

    /** @test */
    public function it_returns_validation_error_if_nci_already_exists_for_new_client()
    {
        Client::factory()->create(['nci' => '1234567890123']);

        $compteData = [
            'type' => 'epargne',
            'solde' => 50000,
            'devise' => 'XOF',
            'client' => [
                'titulaire' => $this->faker->name,
                'nci' => '1234567890123', // NCI existant
                'email' => $this->faker->unique()->safeEmail,
                'telephone' => '+22177' . $this->faker->randomNumber(7, true),
                'adresse' => $this->faker->address,
            ],
        ];

        $response = $this->postJson('/api/v1/comptes', $compteData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['client.nci']);
    }

    /** @test */
    public function it_returns_validation_error_if_email_already_exists_for_new_client()
    {
        Client::factory()->create(['email' => 'existing@example.com']);

        $compteData = [
            'type' => 'epargne',
            'solde' => 50000,
            'devise' => 'XOF',
            'client' => [
                'titulaire' => $this->faker->name,
                'nci' => '1234567890123',
                'email' => 'existing@example.com', // Email existant
                'telephone' => '+22177' . $this->faker->randomNumber(7, true),
                'adresse' => $this->faker->address,
            ],
        ];

        $response = $this->postJson('/api/v1/comptes', $compteData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['client.email']);
    }

    /** @test */
    public function it_returns_validation_error_if_telephone_already_exists_for_new_client()
    {
        Client::factory()->create(['telephone' => '+221771234567']);

        $compteData = [
            'type' => 'epargne',
            'solde' => 50000,
            'devise' => 'XOF',
            'client' => [
                'titulaire' => $this->faker->name,
                'nci' => '1234567890123',
                'email' => $this->faker->unique()->safeEmail,
                'telephone' => '+221771234567', // Téléphone existant
                'adresse' => $this->faker->address,
            ],
        ];

        $response = $this->postJson('/api/v1/comptes', $compteData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['client.telephone']);
    }
}
