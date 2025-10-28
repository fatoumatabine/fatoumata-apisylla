<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Contracts\TransactionArchiveInterface;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ArchiveDailyTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60; // 1 minute

    private Carbon $targetDate;

    /**
     * Create a new job instance.
     *
     * @param Carbon|null $targetDate Date à archiver (par défaut: hier)
     */
    public function __construct(Carbon $targetDate = null)
    {
        $this->targetDate = $targetDate ?: Carbon::yesterday();
    }

    /**
     * Execute the job.
     */
    public function handle(TransactionArchiveInterface $archiveService): void
    {
        Log::info('Début de l\'archivage des transactions', [
            'date' => $this->targetDate->toDateString(),
        ]);

        // Récupérer les transactions de la journée cible
        $transactions = $this->getTransactionsToArchive();

        if ($transactions->isEmpty()) {
            Log::info('Aucune transaction à archiver pour cette date', [
                'date' => $this->targetDate->toDateString(),
            ]);
            return;
        }

        Log::info('Transactions trouvées pour archivage', [
            'count' => $transactions->count(),
            'date' => $this->targetDate->toDateString(),
        ]);

        // Archiver les transactions
        $archived = $archiveService->archiveTransactions($transactions);

        if (!$archived) {
            Log::error('Échec de l\'archivage des transactions', [
                'date' => $this->targetDate->toDateString(),
                'count' => $transactions->count(),
            ]);

            $this->fail(new \Exception('Échec de l\'archivage des transactions'));
            return;
        }

        // Supprimer les transactions de la base locale après archivage réussi
        $this->deleteArchivedTransactions($transactions);

        Log::info('Archivage des transactions terminé avec succès', [
            'date' => $this->targetDate->toDateString(),
            'count' => $transactions->count(),
        ]);
    }

    /**
     * Récupérer les transactions à archiver
     */
    private function getTransactionsToArchive(): Collection
    {
        return Transaction::whereDate('dateTransaction', $this->targetDate)
            ->where('archived', false)
            ->with('compte.client') // Charger les relations pour l'archivage
            ->get();
    }

    /**
     * Supprimer les transactions archivées de la base locale
     */
    private function deleteArchivedTransactions(Collection $transactions): void
    {
        try {
            $transactionIds = $transactions->pluck('id');

            // Marquer comme archivé d'abord (soft delete logique)
            Transaction::whereIn('id', $transactionIds)->update(['archived' => true]);

            // Supprimer physiquement (hard delete) après archivage
            Transaction::whereIn('id', $transactionIds)->delete();

            Log::info('Transactions supprimées de la base locale', [
                'count' => $transactionIds->count(),
                'ids' => $transactionIds->take(5)->toArray(), // Log seulement les 5 premiers IDs
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression des transactions locales', [
                'error' => $e->getMessage(),
                'count' => $transactions->count(),
            ]);

            // Ne pas échouer le job pour une erreur de suppression
            // Les transactions resteront marquées comme archivées
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job d\'archivage des transactions échoué', [
            'date' => $this->targetDate->toDateString(),
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Ici on pourrait envoyer une notification à l'administrateur
    }
}
