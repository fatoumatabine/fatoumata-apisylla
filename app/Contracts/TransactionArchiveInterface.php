<?php

namespace App\Contracts;

use App\Models\Transaction;
use Illuminate\Support\Collection;

interface TransactionArchiveInterface
{
    /**
     * Archiver une collection de transactions
     *
     * @param Collection $transactions
     * @return bool
     */
    public function archiveTransactions(Collection $transactions): bool;

    /**
     * Récupérer les transactions archivées pour un compte
     *
     * @param string $compteId
     * @param array $filters
     * @return Collection
     */
    public function getArchivedTransactions(string $compteId, array $filters = []): Collection;

    /**
     * Vérifier la connexion à la base d'archivage
     *
     * @return bool
     */
    public function isConnected(): bool;
}
