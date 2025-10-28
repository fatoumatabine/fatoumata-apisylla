<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Events\TransactionCreated;
use Illuminate\Support\Facades\Log;

class TransactionObserver
{
    /**
     * Vérifier avant la création d'une transaction
     */
    public function creating(Transaction $transaction): bool
    {
        Log::info('Vérification de la transaction avant création', [
            'compte_id' => $transaction->compte_id,
            'type' => $transaction->type,
            'montant' => $transaction->montant,
        ]);

        // Vérifier que le compte existe
        $compte = $transaction->compte;
        if (!$compte) {
            Log::error('Tentative de transaction sur un compte inexistant', [
                'compte_id' => $transaction->compte_id,
            ]);
            return false;
        }

        // Vérifier les règles métier selon le type de transaction
        if (!$this->validateBusinessRules($transaction)) {
            return false;
        }

        // Vérifier les limites de transaction
        if (!$this->validateLimits($transaction)) {
            return false;
        }

        // Vérifier l'état du compte
        if (!$this->validateAccountState($transaction)) {
            return false;
        }

        Log::info('Transaction validée avec succès');
        return true;
    }

    /**
     * Traiter après la création d'une transaction
     */
    public function created(Transaction $transaction): void
    {
        Log::info('Transaction créée avec succès, déclenchement des événements', [
            'transaction_id' => $transaction->id,
            'reference' => $transaction->reference,
        ]);

        // Déclencher l'événement pour les notifications
        event(new TransactionCreated($transaction));
    }

    /**
     * Valider les règles métier selon le type de transaction
     */
    private function validateBusinessRules(Transaction $transaction): bool
    {
        $compte = $transaction->compte;

        // Pour les débits (retraits)
        if ($transaction->isDebit()) {
            // Vérifier le solde disponible
            if ($compte->solde_disponible < $transaction->montant) {
                Log::warning('Solde insuffisant pour la transaction', [
                    'compte_id' => $compte->id,
                    'solde_disponible' => $compte->solde_disponible,
                    'montant_transaction' => $transaction->montant,
                ]);
                return false;
            }

            // Vérifier les limites selon le type de compte
            if ($compte->type === 'epargne') {
                // Pour les comptes épargne, vérifier les règles spécifiques
                if ($compte->statut === 'bloque') {
                    Log::warning('Tentative de retrait sur un compte épargne bloqué', [
                        'compte_id' => $compte->id,
                    ]);
                    return false;
                }
            }
        }

        // Pour les crédits (dépôts)
        if ($transaction->isCredit()) {
            // Les dépôts sont généralement toujours autorisés
            // Mais on peut ajouter des limites de dépôt maximum
            $maxDepotParJour = 1000000; // 1 million par jour par exemple
            $totalDepotsToday = $compte->transactions()
                ->where('type', Transaction::TYPE_CREDIT)
                ->whereDate('dateTransaction', today())
                ->sum('montant');

            if (($totalDepotsToday + $transaction->montant) > $maxDepotParJour) {
                Log::warning('Limite de dépôt journalier dépassée', [
                    'compte_id' => $compte->id,
                    'total_depots_today' => $totalDepotsToday,
                    'montant_transaction' => $transaction->montant,
                    'limite' => $maxDepotParJour,
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Valider les limites de transaction
     */
    private function validateLimits(Transaction $transaction): bool
    {
        $montant = $transaction->montant;

        // Limite minimum
        $minTransaction = 100; // 100 FCFA minimum
        if ($montant < $minTransaction) {
            Log::warning('Montant de transaction inférieur au minimum', [
                'montant' => $montant,
                'minimum' => $minTransaction,
            ]);
            return false;
        }

        // Limite maximum
        $maxTransaction = 5000000; // 5 millions maximum
        if ($montant > $maxTransaction) {
            Log::warning('Montant de transaction supérieur au maximum', [
                'montant' => $montant,
                'maximum' => $maxTransaction,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Valider l'état du compte
     */
    private function validateAccountState(Transaction $transaction): bool
    {
        $compte = $transaction->compte;

        // Vérifier si le compte est actif
        if ($compte->statut === 'ferme') {
            Log::warning('Tentative de transaction sur un compte fermé', [
                'compte_id' => $compte->id,
            ]);
            return false;
        }

        // Vérifier la devise
        if ($transaction->devise !== $compte->devise) {
            Log::warning('Devise de transaction différente de celle du compte', [
                'compte_devise' => $compte->devise,
                'transaction_devise' => $transaction->devise,
            ]);
            return false;
        }

        return true;
    }
}
