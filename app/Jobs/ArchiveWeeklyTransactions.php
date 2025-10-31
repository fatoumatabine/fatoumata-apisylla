<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ArchiveWeeklyTransactions implements ShouldQueue
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
        // Get the start of last week
        $startOfLastWeek = Carbon::now()->startOfWeek()->subWeek();
        $endOfLastWeek = Carbon::now()->startOfWeek()->subSecond();

        // Get transactions from last week
        $transactions = Transaction::whereBetween('date_transaction', [$startOfLastWeek, $endOfLastWeek])->get();

        if ($transactions->isEmpty()) {
            return;
        }

        // Collection name: transactions_week_YYYY_WW
        $weekNumber = $startOfLastWeek->format('W');
        $year = $startOfLastWeek->format('Y');
        $collectionName = "transactions_week_{$year}_{$weekNumber}";

        // Insert into MongoDB
        $mongoData = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'compte_id' => $transaction->compte_id,
                'type' => $transaction->type,
                'montant' => $transaction->montant,
                'devise' => $transaction->devise,
                'description' => $transaction->description,
                'date_transaction' => $transaction->date_transaction,
                'status' => $transaction->status,
                'reference' => $transaction->reference,
                'archived_at' => now(),
            ];
        });

        DB::connection('mongodb')->collection($collectionName)->insert($mongoData->toArray());

        // Mark as archived in PostgreSQL
        Transaction::whereBetween('date_transaction', [$startOfLastWeek, $endOfLastWeek])
            ->update(['archived' => true]);
    }
}
