<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Client;
use App\Models\Compte;
use Carbon\Carbon;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        // Create some test data
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
        Compte::factory()->create(['client_id' => $client->id, 'titulaire' => 'Jean Dupont']);

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
                         'type' => $compte->type,
                         'solde' => $compte->solde,
                         'devise' => $compte->devise,
                         'statut' => $compte->statut,
                     ],
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id',
                         'numeroCompte',
                         'type',
                         'solde',
                         'devise',
                         'statut',
                         'client_id',
                         'client_name',
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

        $this->assertSoftDeleted('comptes', ['id' => $compte->id]);
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
                     'message' => 'Seuls les comptes épargne peuvent être bloqués.',
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
}
