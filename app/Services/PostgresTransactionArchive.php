<?php

namespace App\Services;

use App\Contracts\TransactionArchiveInterface;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostgresTransactionArchive implements TransactionArchiveInterface
{
    private string $connection;

    public function __construct(string $connection = 'archive')
    {
        $this->connection = $connection;
    }

    /**
     * Archiver une collection de transactions
     */
    public function archiveTransactions(Collection $transactions): bool
    {
        if (!$this->isConnected()) {
            Log::error('Connexion à la base d\'archivage non disponible');
            return false;
        }

        try {
            $data = $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'compte_id' => $transaction->compte_id,
                    'type' => $transaction->type,
                    'montant' => $transaction->montant,
                    'devise' => $transaction->devise,
                    'description' => $transaction->description,
                    'date_transaction' => $transaction->dateTransaction,
                    'status' => $transaction->status ?? Transaction::STATUS_COMPLETED,
                    'reference' => $transaction->reference,
                    'metadata' => json_encode($transaction->metadata),
                    'archived_at' => now(),
                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at,
                ];
            })->toArray();

            DB::connection($this->connection)->table('archived_transactions')->insert($data);

            Log::info('Transactions archivées avec succès', [
                'count' => count($data),
                'connection' => $this->connection,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'archivage des transactions', [
                'error' => $e->getMessage(),
                'connection' => $this->connection,
            ]);

            return false;
        }
    }

    /**
     * Récupérer les transactions archivées pour un compte
     */
    public function getArchivedTransactions(string $compteId, array $filters = []): Collection
    {
        if (!$this->isConnected()) {
            Log::warning('Connexion à la base d\'archivage non disponible');
            return collect();
        }

        try {
            $query = DB::connection($this->connection)
                ->table('archived_transactions')
                ->where('compte_id', $compteId);

            // Appliquer les filtres
            if (isset($filters['date_from'])) {
                $query->where('date_transaction', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('date_transaction', '<=', $filters['date_to']);
            }

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['limit'])) {
                $query->limit($filters['limit']);
            }

            $query->orderBy('date_transaction', 'desc');

            $results = $query->get();

            // Convertir en collection de modèles Transaction (lecture seule)
            return collect($results)->map(function ($row) {
                $transaction = new Transaction();
                $transaction->fill((array) $row);
                $transaction->id = $row->id;
                $transaction->exists = true; // Marquer comme existant pour la lecture
                return $transaction;
            });
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des transactions archivées', [
                'error' => $e->getMessage(),
                'compte_id' => $compteId,
                'connection' => $this->connection,
            ]);

            return collect();
        }
    }

    /**
     * Vérifier la connexion à la base d'archivage
     */
    public function isConnected(): bool
    {
        try {
            DB::connection($this->connection)->getPdo();
            return true;
        } catch (\Exception $e) {
            Log::warning('Connexion à la base d\'archivage échouée', [
                'connection' => $this->connection,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
