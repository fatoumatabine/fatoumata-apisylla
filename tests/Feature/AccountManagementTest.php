<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Client;
use App\Models\Compte;
use Carbon\Carbon;
use App\Models\User; // Importation du modèle User
use Laravel\Passport\Passport; // Importation de Passport

class AccountManagementTest extends TestCase
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

        // Créer des données de test
        Client::factory()->count(5)->create();
        Compte::factory()->count(10)->create();
    }

    /** @test */
    public function it_can_list_all_accounts()
    {
        $response = $this->getJson('/api/v1/comptes');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Liste des comptes récupérée avec succès',
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => [
                             'id',
                             'numeroCompte',
                             'type',
                             'solde',
                             'devise',
                             'statut',
                             'date_debut_blocage',
                             'date_fin_blocage',
                             'client_id',
                             'client_name',
                         ],
                     ],
                     'pagination',
                     'links',
                 ]);
    }

    /** @test */
    public function it_can_filter_accounts_by_type()
    {
        Compte::factory()->create(['type' => 'epargne']);
        Compte::factory()->create(['type' => 'cheque']);

        $response = $this->getJson('/api/v1/comptes?type=epargne');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        // Vérifier que tous les comptes retournés sont de type epargne
        $data = $response->json('data');
        foreach ($data as $compte) {
            $this->assertEquals('epargne', $compte['type']);
        }
    }

    /** @test */
    public function it_can_search_accounts_by_holder_name()
    {
        $client = Client::factory()->create(['titulaire' => 'Jean Dupont']);
        Compte::factory()->create(['client_id' => $client->id, 'titulaire' => 'Jean Dupont', 'statut' => 'actif']);

        $response = $this->getJson('/api/v1/comptes?search=Jean');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));
        // Vérifier que le titulaire contient "Jean"
        foreach ($data as $compte) {
            $this->assertStringContainsString('Jean', $compte['titulaire']);
        }
    }

    /** @test */
    public function it_can_retrieve_a_specific_account()
    {
        $compte = Compte::with('client')->first();

        $response = $this->getJson('/api/v1/comptes/' . $compte->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Compte récupéré avec succès',
                     'data' => [
                         'id' => $compte->id,
                         'numeroCompte' => $compte->numeroCompte,
                         'titulaire' => $compte->titulaire,
                         'type' => $compte->type,
                         'solde' => $compte->solde,
                         'devise' => $compte->devise,
                         'dateCreation' => $compte->dateCreation->toIso8601String(),
                         'statut' => $compte->statut,
                         'metadata' => $compte->metadata,
                         'date_debut_blocage' => $compte->date_debut_blocage ? $compte->date_debut_blocage->toIso8601String() : null,
                         'date_fin_blocage' => $compte->date_fin_blocage ? $compte->date_fin_blocage->toIso8601String() : null,
                         'client_id' => $compte->client_id,
                         'client_name' => $compte->client->titulaire,
                         'archived' => (bool) $compte->archived,
                     ],
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'numeroCompte',
                         'titulaire',
                         'type',
                         'solde',
                         'devise',
                         'dateCreation',
                         'statut',
                         'metadata',
                         'date_debut_blocage',
                         'date_fin_blocage',
                         'client_id',
                         'client_name',
                         'archived',
                     ],
                 ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_account()
    {
        $nonExistentId = (string) \Illuminate\Support\Str::uuid();
        $response = $this->getJson('/api/v1/comptes/' . $nonExistentId);

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Compte non trouvé.',
                 ]);
    }

    /** @test */
    public function it_can_delete_an_account()
    {
        $compte = Compte::factory()->create();

        $response = $this->deleteJson('/api/v1/comptes/' . $compte->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Compte supprimé avec succès.',
                 ]);

        $this->assertDatabaseMissing('comptes', ['id' => $compte->id]);
    }

    /** @test */
    public function it_returns_404_when_deleting_nonexistent_account()
    {
        $nonExistentId = (string) \Illuminate\Support\Str::uuid();
        $response = $this->deleteJson('/api/v1/comptes/' . $nonExistentId);

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Compte non trouvé.',
                 ]);
    }

    /** @test */
    public function it_can_block_a_savings_account()
    {
        $compte = Compte::factory()->create([
            'type' => 'epargne',
            'statut' => 'actif',
        ]);

        $blockData = [
            'date_fin_blocage' => Carbon::now()->addDays(30)->toISOString(),
        ];

        $response = $this->patchJson('/api/v1/comptes/' . $compte->id . '/block', $blockData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Compte bloqué avec succès.',
                 ]);

        $this->assertDatabaseHas('comptes', [
            'id' => $compte->id,
            'statut' => 'bloque',
        ]);

        $compte->refresh();
        $this->assertNotNull($compte->date_debut_blocage);
        $this->assertNotNull($compte->date_fin_blocage);
        $this->assertEquals(Carbon::parse($blockData['date_fin_blocage'])->format('Y-m-d'), $compte->date_fin_blocage->format('Y-m-d'));
    }

    /** @test */
    public function it_returns_error_when_blocking_non_savings_account()
    {
        $compte = Compte::factory()->create([
            'type' => 'cheque',
            'statut' => 'actif',
        ]);

        $blockData = [
            'date_fin_blocage' => Carbon::now()->addDays(30)->toISOString(),
        ];

        $response = $this->patchJson('/api/v1/comptes/' . $compte->id . '/block', $blockData);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Seuls les comptes épargne actifs peuvent être bloqués.',
                 ]);
    }

    /** @test */
    public function it_returns_error_when_blocking_non_active_savings_account()
    {
        $compte = Compte::factory()->create([
            'type' => 'epargne',
            'statut' => 'bloque', // Compte épargne mais non actif
        ]);

        $blockData = [
            'date_fin_blocage' => Carbon::now()->addDays(30)->toISOString(),
        ];

        $response = $this->patchJson('/api/v1/comptes/' . $compte->id . '/block', $blockData);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Seuls les comptes épargne actifs peuvent être bloqués.',
                 ]);
    }

    /** @test */
    public function it_can_unblock_a_savings_account()
    {
        $compte = Compte::factory()->create([
            'type' => 'epargne',
            'statut' => 'bloque',
            'date_debut_blocage' => Carbon::now()->subDays(5),
            'date_fin_blocage' => Carbon::now()->addDays(25),
        ]);

        $response = $this->patchJson('/api/v1/comptes/' . $compte->id . '/unblock');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Compte débloqué avec succès.',
                 ]);

        $this->assertDatabaseHas('comptes', [
            'id' => $compte->id,
            'statut' => 'actif',
        ]);

        $compte->refresh();
        $this->assertNull($compte->date_debut_blocage);
        $this->assertNull($compte->date_fin_blocage);
    }

    /** @test */
    public function it_returns_error_when_unblocking_non_savings_account()
    {
        $compte = Compte::factory()->create([
            'type' => 'cheque',
            'statut' => 'bloque',
        ]);

        $response = $this->patchJson('/api/v1/comptes/' . $compte->id . '/unblock');

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Le compte n\'est pas bloqué ou n\'est pas un compte épargne.',
                 ]);
    }

    /** @test */
    public function it_can_list_archived_accounts()
    {
        // Créer des comptes archivés
        Compte::factory()->count(3)->create(['archived' => true]);

        $response = $this->getJson('/api/v1/comptes/archived');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Liste des comptes archivés récupérée avec succès',
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data',
                     'pagination',
                     'links',
                 ]);

        // Vérifier que tous les comptes retournés sont archivés
        $data = $response->json('data');
        foreach ($data as $compte) {
            $this->assertTrue($compte['archived']);
        }
    }

    /** @test */
    public function it_can_archive_a_blocked_expired_savings_account()
    {
        $compte = Compte::factory()->create([
            'type' => 'epargne',
            'statut' => 'bloque',
            'date_debut_blocage' => Carbon::now()->subDays(30),
            'date_fin_blocage' => Carbon::now()->subDays(1), // Date de fin de blocage échue
            'archived' => false,
        ]);

        $response = $this->patchJson('/api/v1/comptes/' . $compte->id . '/archive');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Compte archivé avec succès.',
                 ]);

        $this->assertDatabaseHas('comptes', [
            'id' => $compte->id,
            'archived' => true,
        ]);
    }

    /** @test */
    public function it_returns_error_when_archiving_non_savings_account()
    {
        $compte = Compte::factory()->create([
            'type' => 'cheque',
            'statut' => 'actif',
            'archived' => false,
        ]);

        $response = $this->patchJson('/api/v1/comptes/' . $compte->id . '/archive');

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Seuls les comptes épargne bloqués dont la date de fin de blocage est échue peuvent être archivés.',
                 ]);
    }

    /** @test */
    public function it_returns_error_when_archiving_non_blocked_savings_account()
    {
        $compte = Compte::factory()->create([
            'type' => 'epargne',
            'statut' => 'actif', // Non bloqué
            'archived' => false,
        ]);

        $response = $this->patchJson('/api/v1/comptes/' . $compte->id . '/archive');

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Seuls les comptes épargne bloqués dont la date de fin de blocage est échue peuvent être archivés.',
                 ]);
    }

    /** @test */
    public function it_returns_error_when_archiving_blocked_non_expired_savings_account()
    {
        $compte = Compte::factory()->create([
            'type' => 'epargne',
            'statut' => 'bloque',
            'date_debut_blocage' => Carbon::now()->subDays(5),
            'date_fin_blocage' => Carbon::now()->addDays(25), // Date de fin de blocage non échue
            'archived' => false,
        ]);

        $response = $this->patchJson('/api/v1/comptes/' . $compte->id . '/archive');

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Seuls les comptes épargne bloqués dont la date de fin de blocage est échue peuvent être archivés.',
                 ]);
    }
}
