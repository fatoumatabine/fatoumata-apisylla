<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Compte;
use App\Models\Transaction;
use App\Jobs\ArchiveExpiredBlockedAccounts;
use App\Jobs\UnarchiveExpiredBlockedAccounts;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

        // Créer des transactions archivées pour ce compte
        Transaction::factory()->count(3)->create([
            'compte_id' => $compte->id,
            'archived' => true,
        ]);

        // Dispatch le job
        (new UnarchiveExpiredBlockedAccounts())->handle();

        // Vérifier que le compte est désarchivé et les dates de blocage sont nulles
        $this->assertFalse($compte->fresh()->archived);
        $this->assertNull($compte->fresh()->date_debut_blocage);
        $this->assertNull($compte->fresh()->date_fin_blocage);

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
        (new UnarchiveExpiredBlockedAccounts())->handle();
        $this->assertTrue($compteStillArchived->fresh()->archived);
    }
}
