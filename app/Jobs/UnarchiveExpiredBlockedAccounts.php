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
        $comptesToUnarchive = Compte::where('date_fin_blocage', '<=', Carbon::now())
                                    ->where('archived', true)
                                    ->get();

        foreach ($comptesToUnarchive as $compte) {
            $compte->archived = false;
            $compte->date_debut_blocage = null;
            $compte->date_fin_blocage = null;
            $compte->save();

            // Désarchiver toutes les transactions associées à ce compte
            $compte->transactions()->update(['archived' => false]);
        }
    }
}
