<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Compte;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UnarchiveExpiredBlockedAccounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Début du job UnarchiveExpiredBlockedAccounts.');

        $comptesToUnarchive = Compte::withoutGlobalScope(\App\Scopes\NonArchivedScope::class) // Retirer le scope global
        ->withTrashed() // Inclure les comptes soft-deleted
        ->where('date_fin_blocage', '<=', Carbon::now())
        ->where('archived', true)
                                     ->get();

        Log::info('Comptes à désarchiver trouvés : ' . $comptesToUnarchive->count());

        foreach ($comptesToUnarchive as $compte) {
            Log::info('Désarchivage du compte ID: ' . $compte->id . ' Numero: ' . $compte->numeroCompte);
            $compte->archived = false;
            $compte->date_debut_blocage = null;
            $compte->date_fin_blocage = null;
            $compte->save();
            $compte->restore(); // Restaurer le compte soft-deleted
            Log::info('Compte restauré et mis à jour. Statut archivé: ' . ($compte->archived ? 'true' : 'false') . ' Deleted_at: ' . ($compte->deleted_at ?? 'null'));


            // Désarchiver toutes les transactions associées à ce compte
            $compte->transactions()->update(['archived' => false]);
            Log::info('Transactions associées au compte ID: ' . $compte->id . ' désarchivées.');
        }
        Log::info('Fin du job UnarchiveExpiredBlockedAccounts.');
    }
}
