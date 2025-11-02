<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Compte;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use App\Http\Resources\TransactionResource;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    use ApiResponseTrait;

    private TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Lister les transactions d'un compte
     */
    public function index(Request $request, string $compteId)
    {
        $filters = $request->only([
            'type', 'date_from', 'date_to', 'status', 'limit', 'include_archived'
        ]);

        // Définir une limite par défaut
        $filters['limit'] = $filters['limit'] ?? 50;

        try {
            $transactions = $this->transactionService->getTransactionsForAccount($compteId, $filters);

            return $this->paginate(
                TransactionResource::collection($transactions),
                'Transactions récupérées avec succès'
            );
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des transactions', [
                'compte_id' => $compteId,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Erreur lors de la récupération des transactions', 500);
        }
    }

    /**
     * Créer une nouvelle transaction
     */
    public function store(Request $request, string $compteId)
    {
        $validated = $request->validate([
            'type' => 'required|in:credit,debit',
            'montant' => 'required|numeric|min:100',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $transactionData = array_merge($validated, [
                'compte_id' => $compteId,
                'devise' => 'XOF', // Devise par défaut
            ]);

            $transaction = $this->transactionService->createTransaction($transactionData);

            return $this->success(
                new TransactionResource($transaction),
                'Transaction créée avec succès',
                201
            );
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la transaction', [
                'compte_id' => $compteId,
                'data' => $validated,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Erreur lors de la création de la transaction', 500);
        }
    }

    /**
     * Afficher une transaction spécifique
     */
    public function show(string $compteId, string $transactionId)
    {
        try {
            // Chercher d'abord dans les transactions locales
            $transaction = Transaction::where('compte_id', $compteId)
                ->where('id', $transactionId)
                ->first();

            if ($transaction) {
                return $this->success(
                    new TransactionResource($transaction),
                    'Transaction trouvée'
                );
            }

            // Si pas trouvée localement, chercher dans les archives
            $archivedTransactions = $this->transactionService->getArchivedTransactions($compteId);
            $transaction = $archivedTransactions->firstWhere('id', $transactionId);

            if ($transaction) {
                return $this->success(
                    new TransactionResource($transaction),
                    'Transaction archivée trouvée'
                );
            }

            return $this->error('Transaction non trouvée', 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de la transaction', [
                'compte_id' => $compteId,
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Erreur lors de la récupération de la transaction', 500);
        }
    }

    /**
     * Statistiques d'un compte
     */
    public function stats(string $compteId)
    {
        try {
            $compte = Compte::find($compteId);

            if (!$compte) {
                return $this->error('Compte non trouvé', 404);
            }

            // Vérifier autorisation : admin ou propriétaire du compte
            $user = Auth::user();
            if ($user->isClient() && (!$user->client || $user->client->id !== $compte->client_id)) {
            return $this->error('Accès non autorisé', 403);
            }

            $stats = $this->transactionService->getAccountStats($compteId);

            return $this->success($stats, 'Statistiques récupérées avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des statistiques', [
                'compte_id' => $compteId,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Erreur lors de la récupération des statistiques', 500);
        }
    }
}
