<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Compte;
use App\Models\Transaction;
use App\Jobs\ArchiveExpiredBlockedAccounts;
use App\Jobs\UnarchiveExpiredBlockedAccounts;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log; // Ajouter l'importation de Log

class AccountArchivingJobsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that ArchiveExpiredBlockedAccounts job archives accounts and their transactions.
     */
    public function test_archive_expired_blocked_accounts_job(): void
    {
        // Créer un compte bloqué dont la date de début de blocage est échue
        $compte = Compte::factory()->create([
            'date_debut_blocage' => Carbon::now()->subDay(),
            'date_fin_blocage' => Carbon::now()->addDay(),
            'archived' => false,
        ]);

        // Créer des transactions pour ce compte
        Transaction::factory()->count(3)->create([
            'compte_id' => $compte->id,
            'archived' => false,
        ]);

        // Dispatch le job
        (new ArchiveExpiredBlockedAccounts())->handle();

        // Vérifier que le compte est archivé
        $this->assertTrue($compte->fresh()->archived);

        // Vérifier que toutes les transactions du compte sont archivées
        $compte->transactions->each(function ($transaction) {
            $this->assertTrue($transaction->fresh()->archived);
        });

        // Créer un compte non bloqué ou non échu
        $compteNotArchived = Compte::factory()->create([
            'date_debut_blocage' => Carbon::now()->addDay(),
            'date_fin_blocage' => Carbon::now()->addDays(2),
            'archived' => false,
        ]);
        (new ArchiveExpiredBlockedAccounts())->handle();
        $this->assertFalse($compteNotArchived->fresh()->archived);
    }

    /**
     * Test that UnarchiveExpiredBlockedAccounts job unarchives accounts and their transactions.
     */
    public function test_unarchive_expired_blocked_accounts_job(): void
    {
        // Créer un compte archivé dont la date de fin de blocage est échue
        $compte = Compte::factory()->create([
            'date_debut_blocage' => Carbon::now()->subDays(2),
            'date_fin_blocage' => Carbon::now()->subDay(),
            'archived' => true,
        ]);
        $compte->delete(); // Soft delete le compte pour qu'il soit "archivé"
        


        // Vérifier que le compte est bien soft-deleted et archivé dans la base de données
        $this->assertDatabaseHas('comptes', [
            'id' => $compte->id,
            'archived' => true,
            'deleted_at' => $compte->deleted_at->toDateTimeString(), // Assurez-vous que deleted_at est non null
        ]);

        // Créer des transactions archivées pour ce compte
        Transaction::factory()->count(3)->create([
            'compte_id' => $compte->id,
            'archived' => true,
        ]);

        // Dispatch le job
        (new UnarchiveExpiredBlockedAccounts())->handle();

        // Vérifier que le compte est désarchivé et les dates de blocage sont nulles
        $compte = Compte::withoutGlobalScope(\App\Scopes\NonArchivedScope::class)->find($compte->id);
        $this->assertNotNull($compte); // S'assurer que le compte n'est pas null
        $this->assertFalse($compte->archived);
        $this->assertNull($compte->date_debut_blocage);
        $this->assertNull($compte->date_fin_blocage);
        $this->assertNull($compte->deleted_at); // S'assurer qu'il est restauré
        $this->assertFalse($compte->trashed()); // S'assurer qu'il n'est plus soft-deleted

        // Vérifier que toutes les transactions du compte sont désarchivées
        $compte->transactions->each(function ($transaction) {
        $this->assertFalse($transaction->fresh()->archived);
        });

        // Créer un compte archivé mais dont la date de fin de blocage n'est pas échue
        $compteStillArchived = Compte::factory()->create([
            'date_debut_blocage' => Carbon::now()->subDay(),
            'date_fin_blocage' => Carbon::now()->addDay(),
            'archived' => true,
        ]);
        $compteStillArchived->delete(); // Soft delete le compte pour qu'il soit "archivé"
        (new UnarchiveExpiredBlockedAccounts())->handle();
        $compteStillArchived = Compte::withoutGlobalScope(\App\Scopes\NonArchivedScope::class)->withTrashed()->find($compteStillArchived->id); // Ce compte devrait rester soft-deleted
        $this->assertNotNull($compteStillArchived); // S'assurer que le compte n'est pas null
        $this->assertTrue($compteStillArchived->archived);
        $this->assertNotNull($compteStillArchived->deleted_at); // S'assurer qu'il est toujours soft-deleted
    }
}
