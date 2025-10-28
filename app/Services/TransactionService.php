<?php

namespace App\Services;

use App\Contracts\TransactionArchiveInterface;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TransactionService
{
    private TransactionArchiveInterface $archiveService;

    public function __construct(TransactionArchiveInterface $archiveService)
    {
        $this->archiveService = $archiveService;
    }

    /**
     * Récupérer les transactions d'un compte
     * Combine les données locales (jour actuel) et archivées (historique)
     */
    public function getTransactionsForAccount(string $compteId, array $filters = []): Collection
    {
        $transactions = collect();

        // 1. Récupérer les transactions locales (non archivées)
        $localTransactions = $this->getLocalTransactions($compteId, $filters);
        $transactions = $transactions->merge($localTransactions);

        // 2. Si on demande de l'historique, récupérer aussi les transactions archivées
        if ($this->shouldIncludeArchived($filters)) {
            $archivedTransactions = $this->getArchivedTransactions($compteId, $filters);
            $transactions = $transactions->merge($archivedTransactions);
        }

        // 3. Trier par date décroissante
        return $transactions->sortByDesc('dateTransaction');
    }

    /**
     * Récupérer les transactions locales (non archivées)
     */
    private function getLocalTransactions(string $compteId, array $filters = []): Collection
    {
        $query = Transaction::where('compte_id', $compteId)
            ->where('archived', false);

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Récupérer les transactions archivées
     */
    private function getArchivedTransactions(string $compteId, array $filters = []): Collection
    {
        if (!$this->archiveService->isConnected()) {
            Log::warning('Service d\'archivage non disponible, seules les transactions locales seront retournées');
            return collect();
        }

        return $this->archiveService->getArchivedTransactions($compteId, $filters);
    }

    /**
     * Déterminer si on doit inclure les transactions archivées
     */
    private function shouldIncludeArchived(array $filters = []): bool
    {
        // Inclure l'historique si :
        // - Explicitement demandé
        // - Recherche sur une période dépassant aujourd'hui
        // - Pas de limite de temps spécifiée (recherche générale)

        if (isset($filters['include_archived']) && $filters['include_archived']) {
            return true;
        }

        if (isset($filters['date_from']) && Carbon::parse($filters['date_from'])->isBefore(today())) {
            return true;
        }

        if (!isset($filters['date_from']) && !isset($filters['date_to'])) {
            // Recherche générale - inclure l'historique
            return true;
        }

        return false;
    }

    /**
     * Appliquer les filtres à la requête
     */
    private function applyFilters($query, array $filters = []): void
    {
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('dateTransaction', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('dateTransaction', '<=', $filters['date_to']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['limit'])) {
            $query->limit($filters['limit']);
        }

        // Tri par défaut
        $query->orderBy('dateTransaction', 'desc');
    }

    /**
     * Créer une nouvelle transaction
     */
    public function createTransaction(array $data): Transaction
    {
        $transaction = new Transaction($data);
        $transaction->save();

        return $transaction;
    }

    /**
     * Vérifier si une transaction peut être créée
     */
    public function canCreateTransaction(string $compteId, string $type, float $montant): bool
    {
        // Cette vérification est maintenant faite par l'observer
        // Mais on peut l'utiliser pour des validations préalables
        return true;
    }
}
