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

class ArchiveExpiredBlockedAccounts implements ShouldQueue
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
        $comptesToArchive = Compte::withoutGlobalScope(\App\Scopes\NonArchivedScope::class)
        ->where('date_debut_blocage', '<=', Carbon::now())
        ->where('archived', false)
                                  ->get();

        foreach ($comptesToArchive as $compte) {
            $compte->archived = true;
            $compte->save();

            // Archiver toutes les transactions associées à ce compte
            $compte->transactions()->update(['archived' => true]);
        }
    }
}
